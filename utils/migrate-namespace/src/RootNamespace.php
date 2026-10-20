<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace;

/**
 * Root-namespace migration constants and the shared value-aware swap rule.
 *
 * The migration renames the PHP root namespace Piwik\ -> Matomo\ in code
 * names, string literals, and PHPDoc comments. The facade class Piwik\Piwik ->
 * Matomo\Matomo is the one class whose short name changes; every other class
 * keeps its short name and only the root prefix is swapped.
 *
 * swapQualified() applies the same boundary rule to free text: a "Piwik"
 * occurrence is the root of a qualified name when it is preceded by the start
 * of the text, a non-identifier character, or a backslash run preceded by
 * such a boundary (so \Piwik\Foo swaps, but x\Piwik\Foo and the second Piwik
 * of Piwik\Piwik\Foo do not), and followed by one-or-more backslashes and an
 * identifier start (so Piwik\Foo, Piwik\\Foo regex-escaped, and
 * Piwik\DataTable\Filter\ all match, while bare 'Piwik', 'PiwikPro',
 * 'Piwik_Key', PiwikFoo\Bar, URLs, and PIWIK_* do not).
 */
final class RootNamespace
{
    public const OLD_ROOT = 'Piwik';
    public const NEW_ROOT = 'Matomo';
    public const OLD_PREFIX = 'Piwik\\';
    public const NEW_PREFIX = 'Matomo\\';
    public const FACADE_OLD = 'Piwik\\Piwik';
    public const FACADE_NEW = 'Matomo\\Matomo';

    /** Occurrence is the root of a qualified name; swap the root only. */
    private const OCCURRENCE_ROOT = 1;

    /**
     * Occurrence is the root of the facade: the segment after the backslash
     * run is exactly "Piwik" and it is the last segment; both segments swap.
     */
    private const OCCURRENCE_FACADE = 2;

    /**
     * True when $name is exactly the facade FQCN (Piwik\Piwik), the one class
     * whose short name changes during the migration.
     */
    public static function isFacadeName(string $name): bool
    {
        return $name === self::FACADE_OLD;
    }

    /**
     * Swaps the qualified Piwik\ roots in $text; returns $text unchanged when
     * nothing matches.
     *
     * Only the root segment is swapped (the backslash run after it is
     * preserved verbatim), except for the facade Piwik\Piwik, where the
     * segment after the backslash run is exactly "Piwik" and ends the
     * qualified name: there both segments are swapped.
     */
    public static function swapQualified(string $text): string
    {
        $occurrences = self::qualifiedOccurrences($text);

        if ($occurrences === []) {
            return $text;
        }

        $result = '';
        $copied = 0;

        foreach ($occurrences as $occurrence) {
            $pos = $occurrence['pos'];
            $result .= substr($text, $copied, $pos - $copied);

            if ($occurrence['kind'] === self::OCCURRENCE_FACADE) {
                // Piwik\Piwik -> Matomo\Matomo: both segments, run verbatim.
                $runEnd = self::runEnd($text, $pos + strlen(self::OLD_ROOT));
                $segmentEnd = self::segmentEnd($text, $runEnd);
                $result .= self::NEW_ROOT
                    . substr($text, $pos + strlen(self::OLD_ROOT), $runEnd - $pos - strlen(self::OLD_ROOT))
                    . self::NEW_ROOT;
                $copied = $segmentEnd;
            } else {
                $result .= self::NEW_ROOT;
                $copied = $pos + strlen(self::OLD_ROOT);
            }
        }

        return $result . substr($text, $copied);
    }

    /**
     * Swaps the root inside a raw string literal so the emitted source matches
     * the swapped value while preserving the original quoting and escaping.
     *
     * The raw literal contains doubled backslashes where the value has escape
     * expansions, so occurrences are paired by ordinal with the value's
     * occurrences: an occurrence is swapped only when the corresponding value
     * occurrence is a qualified root (or the facade's second segment).
     *
     * Returns null when the raw cannot be paired with the value (escape
     * sequences producing Piwik text); callers then fall back to canonical
     * re-printing, which is still value-correct.
     */
    public static function swapRawLiteral(string $raw, string $value): ?string
    {
        $valueOccurrences = self::qualifiedOccurrences($value);
        $valuePositions = self::piwikPositions($value);
        $rawPositions = self::piwikPositions($raw);

        if (count($valuePositions) !== count($rawPositions)) {
            return null;
        }

        $result = '';
        $copied = 0;
        $pendingFacadeSegment = false;

        foreach ($rawPositions as $index => $rawPos) {
            $swap = false;

            if ($pendingFacadeSegment) {
                // Second segment of the facade: Piwik\Piwik -> Matomo\Matomo.
                $swap = true;
                $pendingFacadeSegment = false;
            } elseif (isset($valueOccurrences[$index])) {
                $swap = true;

                if ($valueOccurrences[$index]['kind'] === self::OCCURRENCE_FACADE) {
                    $pendingFacadeSegment = true;
                }
            }

            if (!$swap) {
                continue;
            }

            $result .= substr($raw, $copied, $rawPos - $copied) . self::NEW_ROOT;
            $copied = $rawPos + strlen(self::OLD_ROOT);
        }

        return $result . substr($raw, $copied);
    }

    /**
     * Every qualified-root occurrence of "Piwik" in $text, in order.
     *
     * @return list<array{pos: int, kind: self::OCCURRENCE_ROOT|self::OCCURRENCE_FACADE}>
     */
    private static function qualifiedOccurrences(string $text): array
    {
        $occurrences = [];

        foreach (self::piwikPositions($text) as $pos) {
            if (!self::isQualifiedRootOccurrence($text, $pos)) {
                continue;
            }

            $runEnd = self::runEnd($text, $pos + strlen(self::OLD_ROOT));
            $segmentEnd = self::segmentEnd($text, $runEnd);

            if (
                $segmentEnd - $runEnd === strlen(self::OLD_ROOT)
                && substr($text, $runEnd, strlen(self::OLD_ROOT)) === self::OLD_ROOT
                && (!isset($text[$segmentEnd])
                    || ($text[$segmentEnd] !== '\\' && !self::isIdentifierChar($text[$segmentEnd])))
            ) {
                $occurrences[] = ['pos' => $pos, 'kind' => self::OCCURRENCE_FACADE];
            } else {
                $occurrences[] = ['pos' => $pos, 'kind' => self::OCCURRENCE_ROOT];
            }
        }

        return $occurrences;
    }

    /**
     * Positions of every "Piwik" occurrence in $text, in order.
     *
     * @return list<int>
     */
    private static function piwikPositions(string $text): array
    {
        $positions = [];
        $offset = 0;

        while (($pos = strpos($text, self::OLD_ROOT, $offset)) !== false) {
            $positions[] = $pos;
            $offset = $pos + strlen(self::OLD_ROOT);
        }

        return $positions;
    }

    /**
     * True when the occurrence at $pos is the root of a backslash-qualified
     * name: the left boundary must be the start of the text, a non-identifier
     * character, or a backslash run preceded by such a boundary, and the
     * right context must be one-or-more backslashes followed by an identifier
     * start.
     */
    private static function isQualifiedRootOccurrence(string $text, int $pos): bool
    {
        $after = $pos + strlen(self::OLD_ROOT);

        if (!isset($text[$after]) || $text[$after] !== '\\') {
            return false;
        }

        $runEnd = self::runEnd($text, $after);

        if (!isset($text[$runEnd]) || !self::isIdentifierStart($text[$runEnd])) {
            return false;
        }

        if ($pos === 0) {
            return true;
        }

        $prev = $text[$pos - 1];

        if ($prev !== '\\') {
            return !self::isIdentifierChar($prev);
        }

        $before = $pos - 1;

        while ($before >= 0 && $text[$before] === '\\') {
            $before--;
        }

        return $before < 0 || !self::isIdentifierChar($text[$before]);
    }

    /**
     * End offset (exclusive) of the backslash run starting at $from.
     */
    private static function runEnd(string $text, int $from): int
    {
        $i = $from;
        $length = strlen($text);

        while ($i < $length && $text[$i] === '\\') {
            $i++;
        }

        return $i;
    }

    /**
     * End offset (exclusive) of the identifier segment starting at $from.
     */
    private static function segmentEnd(string $text, int $from): int
    {
        $i = $from;
        $length = strlen($text);

        while ($i < $length && self::isIdentifierChar($text[$i])) {
            $i++;
        }

        return $i;
    }

    private static function isIdentifierChar(string $char): bool
    {
        return ($char >= 'a' && $char <= 'z')
            || ($char >= 'A' && $char <= 'Z')
            || ($char >= '0' && $char <= '9')
            || $char === '_';
    }

    private static function isIdentifierStart(string $char): bool
    {
        return ($char >= 'a' && $char <= 'z')
            || ($char >= 'A' && $char <= 'Z')
            || $char === '_';
    }
}
