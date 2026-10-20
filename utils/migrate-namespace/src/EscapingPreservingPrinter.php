<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace;

use PhpParser\Node\Scalar;
use PhpParser\PrettyPrinter\Standard;

/**
 * Standard printer that emits the raw literal for string nodes when one is
 * available.
 *
 * Standard::pScalar_String() re-escapes string values canonically, so
 * 'Piwik\\Plugins\\%s' would be reprinted as 'Matomo\Plugins\%s' (the doubled
 * backslashes collapse) and double-quoted strings would gain doubled
 * backslashes. The swapper records the swapped raw token span on changed
 * strings, so emitting that span keeps the diff minimal and preserves the
 * original quoting and escaping byte-for-byte.
 *
 * The rawValue attribute is only consulted for nodes that are actually
 * reprinted (the format-preserving printer splices unchanged nodes from the
 * original token stream), and the swapper always overwrites it on changed
 * strings, so the parser-set rawValue on unchanged strings is never stale.
 */
final class EscapingPreservingPrinter extends Standard
{
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Overrides PhpParser\PrettyPrinter\Standard::pScalar_String, whose name uses php-parser's snake-case printer convention.
    protected function pScalar_String(Scalar\String_ $node): string
    {
        $rawValue = $node->getAttribute('rawValue');

        if (is_string($rawValue)) {
            return $rawValue;
        }

        return parent::pScalar_String($node);
    }

    /**
     * Reprints comments using their original indentation.
     *
     * The parent implementation joins comment lines with the current printer
     * indent level, so reprinting a docblock whose indentation does not match
     * the enclosing statement (e.g. a column-0 docblock inside an indented
     * if-block) would shift every line. Keeping the indentation of the
     * original token stream makes changed docblocks byte-identical apart from
     * the swap. Outside format-preserving printing (full reprint fallback)
     * the parent behavior is kept, since the original token positions are
     * meaningless there.
     */
    protected function pComments(array $comments): string
    {
        if ($this->origTokens === null) {
            return parent::pComments($comments);
        }

        $result = '';

        foreach ($comments as $index => $comment) {
            $indent = '';
            $startPos = $comment->getStartTokenPos();

            if ($startPos >= 0) {
                $indent = str_repeat(' ', $this->origTokens->getIndentationBefore($startPos));
            }

            if ($index > 0) {
                $result .= "\n" . $indent;
            }

            $result .= str_replace("\n", "\n" . $indent, $comment->getReformattedText());
        }

        return $result;
    }
}
