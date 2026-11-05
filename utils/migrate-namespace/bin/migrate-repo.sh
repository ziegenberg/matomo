#!/usr/bin/env bash
#
# migrate-repo.sh - canonical driver for the Piwik\ -> Matomo\ namespace migration
#
# Wraps utils/migrate-namespace/bin/migrate.php with the canonical path set used
# for the core migration (issue 05), plus the canonical text-file set (issue 11:
# phpstan.neon, .github/scripts/build-package.sh, .github/workflows/release-preview.yml;
# composer.json is deliberately excluded - see TEXT_PATHS below), and provides
# the maintenance workflow: tool improvements are re-run and fixup-committed
# into the mechanical commit.
#
# Bootstrap of a repo (once, sequentially - no retroactive split needed):
#   1. migrate-repo.sh run             # mechanical tool output
#   2. commit it                       # the mechanical commit (C1)
#   3. do the hand-written follow-up
#   4. commit it                       # the manual commit (C2)
# From then on, tool changes only need fixup commits into C1.
#
# Usage:
#   migrate-repo.sh run [--dry-run] [--check] [--allowlist PATH]...
#       Run the swapper over the canonical path set.
#   migrate-repo.sh baseline
#       Regenerate the PHPStan baseline (phpstan analyse --generate-baseline).
#   migrate-repo.sh fixup [--c1 REV]
#       Re-run the swapper with --skip-allowlisted over the canonical path set
#       and, when anything changed, create a fixup commit for the mechanical
#       commit (found via .git/migrate-namespace-c1 if present, else via the
#       mechanical commit message; override with --c1).
#       Squash with: git rebase -i --autosquash <c1>~1
#   migrate-repo.sh check
#       Alias for run --check: source-clean verification; exit 1 on leftovers.
#   migrate-repo.sh help
#       Show this usage.
#
# Environment:
#   MIGRATE_PATHS   Space-separated path list (default: the canonical core set).

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
MIGRATE_BIN="$REPO_ROOT/utils/migrate-namespace/bin/migrate.php"
WRAPPER="utils/migrate-namespace/bin/migrate-repo.sh"

# Canonical path set for the core migration (issue 05): exactly the paths the
# swapper was originally run over. Override with MIGRATE_PATHS for submodules.
CANONICAL_PATHS=(
    core
    config
    misc/cron
    js/tracker.php
    piwik.php
    console
    DIObject.php
    bootstrap-phpstan.php
    .phpstorm.meta.php
    libs/Authenticator/TwoFactorAuthenticator.php
    libs/HTML/QuickForm2/Renderer.php
    libs/Zend/Session.php
    plugins/CoreConsole/Commands/BuildTracker.php
)

# Text files the shared value-aware rule is run over (issue 11). composer.json
# is deliberately excluded: its `Piwik\Plugins\` / `Piwik\Tests\` PSR-4 roots are
# the retained dual-root contract through 7.0 (remove them in the issue-06/07
# follow-up, then add composer.json here and re-run).
TEXT_PATHS=(
    phpstan.neon
    .github/scripts/build-package.sh
    .github/workflows/release-preview.yml
)

# Title of the mechanical commit; fixup finds C1 via the recorded hash in
# .git/migrate-namespace-c1 or, failing that, this message.
DEFAULT_MECHANICAL_MSG="Migrate core, root entrypoints, config and text files to Matomo\ namespace via migration tool"

usage() {
    sed -n '3,/^set -euo pipefail$/p' "$0" | sed '$d' | sed 's/^# \{0,1\}//'
}

paths() {
    if [[ -n "${MIGRATE_PATHS:-}" ]]; then
        read -ra _paths <<<"$MIGRATE_PATHS"
    else
        _paths=("${CANONICAL_PATHS[@]}")
    fi
}

run_tool() {
    local flags=()
    local extra_paths=()
    while [[ $# -gt 0 ]]; do
        case "$1" in
        --dry-run | --check | --skip-allowlisted)
            flags+=("$1")
            shift
            ;;
        --allowlist)
            flags+=("$1" "$2")
            shift 2
            ;;
        *)
            echo "migrate-repo.sh: unknown option: $1" >&2
            exit 2
            ;;
        esac
    done
    paths
    # Text-file mode is part of the canonical path set only; MIGRATE_PATHS
    # still fully overrides both the paths and the text extensions.
    if [[ -z "${MIGRATE_PATHS:-}" ]]; then
        flags+=(--text-extensions neon,yml,yaml,sh,json)
        extra_paths=("${TEXT_PATHS[@]}")
    fi
    php "$MIGRATE_BIN" "${flags[@]}" "${_paths[@]}" "${extra_paths[@]}"
}

regenerate_baseline() {
    php "$REPO_ROOT/vendor/bin/phpstan" analyse -c "$REPO_ROOT/phpstan.neon" --generate-baseline
}

fixup() {
    local c1=""
    while [[ $# -gt 0 ]]; do
        case "$1" in
        --c1) c1="$2"; shift 2 ;;
        *) echo "migrate-repo.sh: unknown option: $1" >&2; exit 2 ;;
        esac
    done
    if [[ -z "$c1" && -f "$REPO_ROOT/.git/migrate-namespace-c1" ]]; then
        c1="$(cat "$REPO_ROOT/.git/migrate-namespace-c1")"
    fi
    if [[ -z "$c1" ]]; then
        local title
        title="$(printf '%s\n' "$DEFAULT_MECHANICAL_MSG" | head -1)"
        c1="$(git log -1 --format=%H --fixed-strings --grep="$title" 2>/dev/null || true)"
    fi
    if [[ -z "$c1" || ! "$c1" =~ ^[0-9a-f]{7,40}$ ]]; then
        echo "migrate-repo.sh: cannot determine the mechanical commit; pass --c1 <rev>" >&2
        exit 1
    fi
    # The fixup delta must be exactly the tool output, so the tree must be
    # clean (apart from the wrapper itself on its first run) before running.
    local dirty
    dirty="$(git status --porcelain | grep -v "^?? $WRAPPER$" || true)"
    if [[ -n "$dirty" ]]; then
        echo "migrate-repo.sh: working tree is not clean; commit or stash first:" >&2
        echo "$dirty" >&2
        exit 1
    fi
    echo "mechanical commit: $(git rev-parse --short "$c1")"
    paths
    if [[ -z "${MIGRATE_PATHS:-}" ]]; then
        php "$MIGRATE_BIN" --skip-allowlisted --text-extensions neon,yml,yaml,sh,json "${_paths[@]}" "${TEXT_PATHS[@]}"
    else
        php "$MIGRATE_BIN" --skip-allowlisted "${_paths[@]}"
    fi
    if [[ -z "$(git diff --name-only)" ]]; then
        echo "idempotent: no changes; nothing to fix up"
        return 0
    fi
    git add -u
    git commit --fixup "$c1"
    echo "created fixup commit; squash with: git rebase -i --autosquash $(git rev-parse --short "$c1")~1"
}

cmd="${1:-run}"
shift || true
case "$cmd" in
run) run_tool "$@" ;;
check) run_tool --check "$@" ;;
baseline) regenerate_baseline ;;
fixup) fixup "$@" ;;
help | -h | --help) usage ;;
*) usage >&2; exit 2 ;;
esac
