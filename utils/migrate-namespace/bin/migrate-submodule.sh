#!/usr/bin/env bash
#
# migrate-submodule.sh — migrate one bundled submodule plugin
# repo from `Piwik\` to `Matomo\` end-to-end (fork -> clone -> swap -> validate
# -> commit -> push), deterministically and idempotently.
#
# Repeatability layer around the standalone
# RootNamespaceSwapper (utils/migrate-namespace/bin/migrate.php), which does the
# style-preserving `Piwik\` -> `Matomo\` prefix swap. The swapper only processes
# `.php` files; this script also fixes the non-PHP config fallout
# (`phpstan.neon` `universalObjectCratesClasses`) and validates the result.
#
# Usage:
#   migrate-submodule.sh <matomo-org-repo>
#
#   <matomo-org-repo>   The matomo-org GitHub repo name, e.g. plugin-Provider,
#                       plugin-Bandwidth, tag-manager, plugin-TasksTimetable.
#
# Environment:
#   GITHUB_USER   GitHub user to fork to (default: `gh api user --jq .login`).
#   CLONE_PARENT  Parent dir for the clone (default: sibling of the matomo root).
#   BASE_BRANCH   Upstream branch to migrate from (default: prepare6x).
#   PUSH          Push to origin after migrating (default: 1). Set PUSH=0 to
#                 migrate + commit locally without pushing.
#
# Idempotence: re-running on an already-migrated repo is a no-op. The script
# always starts from the upstream base (BASE_BRANCH), migrates deterministically,
# and skips commit/push when the result already matches origin/BASE_BRANCH.
#
# Exit codes: 0 on success (including already-migrated no-op), non-zero on any
# failure (lint error, leftover `Piwik\`, facade collision, push failure).

set -euo pipefail

# --- config -------------------------------------------------------------------

REPO="${1:?Usage: migrate-submodule.sh <matomo-org-repo>}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# utils/migrate-namespace/bin/ -> utils/migrate-namespace/ -> utils/ -> matomo root
MATOMO_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"
SWAPPER="$SCRIPT_DIR/migrate.php"

GITHUB_USER="${GITHUB_USER:-$(gh api user --jq .login 2>/dev/null || echo "")}"
if [ -z "$GITHUB_USER" ]; then
    echo "ERROR: could not resolve GITHUB_USER (run 'gh auth login' or set GITHUB_USER)" >&2
    exit 1
fi
CLONE_PARENT="${CLONE_PARENT:-$(dirname "$MATOMO_ROOT")}"
BASE_BRANCH="${BASE_BRANCH:-prepare6x}"
PUSH="${PUSH:-1}"

UPSTREAM_URL="https://github.com/matomo-org/${REPO}.git"
FORK_URL="git@github.com:${GITHUB_USER}/${REPO}.git"
CLONE_DIR="${CLONE_PARENT}/${REPO}"
COMMIT_MSG_FILE="$(mktemp)"
trap 'rm -f "$COMMIT_MSG_FILE"' EXIT

# --- helpers ------------------------------------------------------------------

step() { printf '\n\033[1m>> %s\033[0m\n' "$*"; }
info() { printf '   %s\n' "$*"; }
err()  { printf '   \033[31mERROR: %s\033[0m\n' "$*" >&2; }
ok()   { printf '   \033[32m✓ %s\033[0m\n' "$*"; }

# --- steps --------------------------------------------------------------------

step "Migrate $REPO (matomo-org/$REPO -> $GITHUB_USER/$REPO, branch $BASE_BRANCH)"
info "matomo root: $MATOMO_ROOT"
info "clone dir:   $CLONE_DIR"
info "upstream:    $UPSTREAM_URL"
info "fork:        $FORK_URL"

# 1. Ensure the fork exists on GITHUB_USER (idempotent: ignore "already exists").
step "1/8  ensure fork exists ($GITHUB_USER/$REPO)"
if gh repo fork "matomo-org/$REPO" --clone=false 2>/dev/null; then
    ok "fork created"
else
    ok "fork already exists (or fork pending)"
fi

# 2. Clone (or reuse) the fork, and ensure the upstream remote points at matomo-org.
step "2/8  clone / update fork at $CLONE_DIR"
if [ -d "$CLONE_DIR" ]; then
    ok "clone exists, reusing"
    git -C "$CLONE_DIR" remote set-url origin "$FORK_URL"
else
    git clone "$FORK_URL" "$CLONE_DIR"
    ok "cloned"
fi
git -C "$CLONE_DIR" remote get-url upstream >/dev/null 2>&1 \
    || git -C "$CLONE_DIR" remote add upstream "$UPSTREAM_URL"
git -C "$CLONE_DIR" remote set-url upstream "$UPSTREAM_URL"
ok "remotes: origin=$GITHUB_USER/$REPO  upstream=matomo-org/$REPO"

# 3. Reset to the upstream base (deterministic start: always migrate the base).
#    `checkout -B` sets the branch but preserves uncommitted changes; `reset
#    --hard` guarantees a clean un-migrated base every run.
step "3/8  fetch + reset to upstream/$BASE_BRANCH"
git -C "$CLONE_DIR" fetch upstream "$BASE_BRANCH"
git -C "$CLONE_DIR" fetch origin "$BASE_BRANCH" 2>/dev/null || true
git -C "$CLONE_DIR" checkout -B "$BASE_BRANCH" "upstream/$BASE_BRANCH"
git -C "$CLONE_DIR" reset --hard "upstream/$BASE_BRANCH"
ok "at upstream/$BASE_BRANCH (un-migrated base, clean)"

# 4. Run the style-preserving swapper across the clone.
step "4/8  run RootNamespaceSwapper"
php "$SWAPPER" "$CLONE_DIR"
ok "swapper done"

# 5. Fix non-PHP config fallout: phpstan.neon universalObjectCratesClasses.
#    The swapper only processes .php; the .neon still references Piwik\ classes.
step "5/8  fix phpstan.neon (Piwik\ -> Matomo\ in universalObjectCratesClasses)"
if [ -f "$CLONE_DIR/phpstan.neon" ]; then
    before=$(grep -c -- '- Piwik\\' "$CLONE_DIR/phpstan.neon" || true)
    if [ "$before" -gt 0 ]; then
        sed -i 's/- Piwik\\/- Matomo\\/g' "$CLONE_DIR/phpstan.neon"
        ok "fixed $before Piwik\\ ref(s) in phpstan.neon"
    else
        ok "phpstan.neon has no Piwik\\ refs (already Matomo\)"
    fi
else
    ok "no phpstan.neon (nothing to fix)"
fi

# 6. Validate: lint every PHP file (fail fast on a parse error).
step "6/8  lint all PHP files"
lint_fail=0
while IFS= read -r -d '' f; do
    if ! php -l "$f" >/dev/null 2>&1; then
        err "lint: $f"
        php -l "$f" >&2 || true
        lint_fail=1
    fi
done < <(find "$CLONE_DIR" -name '*.php' -not -path '*/vendor/*' -print0)
if [ "$lint_fail" -ne 0 ]; then
    exit 1
fi
ok "all PHP files lint clean"

# 7. Validate: no leftover Piwik\ namespace refs (excluding Piwik_ class-name
#    prefixes, which are unrelated legacy names like PiwikTracker).
step "7a/8 scan for leftover Piwik\ namespace refs"
leftover=$(grep -rn 'Piwik\\' "$CLONE_DIR" --include='*.php' 2>/dev/null \
    | grep -v '/vendor/' | grep -v 'Piwik_' || true)
if [ -n "$leftover" ]; then
    err "leftover Piwik\ namespace refs:"
    printf '   %s\n' "$leftover" >&2
    exit 1
fi
ok "no leftover Piwik\ refs"

# 7b. Validate: no facade-import collisions. Renaming `use Piwik\Piwik;` to
#     `use Matomo\Matomo;` can collide with a local `class Matomo` or another
#     `Matomo` short-name import (compile-time fatal). The swapper cannot detect
#     file-local name collisions, so this guard catches them.
step "7b/8 scan for facade-import collisions (use Matomo\Matomo + class/import Matomo)"
collision_files=""
while IFS= read -r -d '' f; do
    # Only files that import the facade as `use Matomo\Matomo;` can collide.
    grep -q '^use Matomo\\Matomo;' "$f" || continue
    collision=0
    # A class declaration named Matomo in the same file shadows the import.
    grep -qE 'class Matomo\b' "$f" && collision=1
    # A *different* use import whose short name is also Matomo (e.g.
    # `use ...\SiteContentDetection\Matomo;`) shadows the facade import. Exclude
    # `use Matomo\Matomo;` itself from this check.
    grep -E '^use [A-Za-z\\]+\\Matomo;' "$f" | grep -qv '^use Matomo\\Matomo;' && collision=1
    if [ "$collision" -eq 1 ]; then
        collision_files="$collision_files
$f"
    fi
done < <(grep -rl '^use Matomo\\Matomo;' "$CLONE_DIR" --include='*.php' 2>/dev/null \
    | grep -v '/vendor/' | tr '\n' '\0')
if [ -n "$collision_files" ]; then
    err "facade-import collision (use Matomo\\Matomo shadows a local class/import):"
    printf '   %s\n' "$collision_files" >&2
    exit 1
fi
ok "no facade-import collisions"

# 8. Commit + push (idempotent: skip when the migrated tree matches origin).
step "8/8  commit + push"

# Stage the migration and compute its tree object (without committing) so we can
# compare the migrated tree to origin's tree. `git write-tree` reads the index.
git -C "$CLONE_DIR" add -A
migrated_tree=$(git -C "$CLONE_DIR" write-tree)
origin_tree=$(git -C "$CLONE_DIR" rev-parse "origin/$BASE_BRANCH^{tree}" 2>/dev/null || echo "")

if [ -n "$origin_tree" ] && [ "$migrated_tree" = "$origin_tree" ]; then
    # Already migrated + pushed: leave the clone clean at the migrated commit.
    git -C "$CLONE_DIR" reset -q --hard "origin/$BASE_BRANCH"
    ok "already migrated (tree matches origin/$BASE_BRANCH)"
    exit 0
fi

# Commit the migration on top of the upstream base.
git -C "$CLONE_DIR" config --local commit.gpgsign false
cat > "$COMMIT_MSG_FILE" <<'EOF'
Rename Piwik\ root namespace to Matomo\ (#20760)

Migrate to the canonical `Matomo\` root namespace (`namespace Matomo\Plugins\...`),
including all references to core (`Matomo\` instead of `Piwik\`) and the facade
(`Matomo::` / `use Matomo\Matomo` instead of `Piwik::` / `use Piwik\Piwik`).

Applied via the standalone style-preserving RootNamespaceSwapper (matomo core
tooling), which preserves each reference's qualification and the file
formatting, so the rename is a minimal, reviewable diff. Also updates
`phpstan.neon` (`universalObjectCratesClasses` `Piwik\` -> `Matomo\`).

The `Piwik\` alias layer (matomo core) keeps this plugin working under the old
namespace through the 6.x release line; the alias layer is removed in 7.0.
EOF
git -C "$CLONE_DIR" commit -F "$COMMIT_MSG_FILE" --no-verify
ok "committed migration"

if [ "$PUSH" = "1" ]; then
    # Push (force-with-lease) to the fork: the migration is a deterministic
    # rewrite of the base, so a stale origin commit is replaced.
    git -C "$CLONE_DIR" push --force-with-lease origin "$BASE_BRANCH"
    ok "pushed to origin/$BASE_BRANCH"
else
    ok "PUSH=0, skipping push (commit is local only)"
fi

step "done: $REPO migrated"
