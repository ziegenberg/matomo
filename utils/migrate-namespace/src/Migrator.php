<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

/**
 * Per-file orchestration: parse, build the alias map, clone + swap, and
 * format-preserve print.
 *
 * The swapped tree is printed with printFormatPreserving against the pristine
 * original AST, so only the changed name tokens are reprinted and everything
 * else (whitespace, comments, layout) is copied from the original token
 * stream.
 */
final class Migrator
{
    private Parser $parser;
    private Standard $printer;

    public function __construct(?Parser $parser = null, ?Standard $printer = null)
    {
        $this->parser = $parser ?? (new ParserFactory())->createForHostVersion();
        $this->printer = $printer ?? new Standard();
    }

    /**
     * Rewrites Piwik\ -> Matomo\ in code names only.
     *
     * @throws \PhpParser\Error When the code cannot be parsed.
     */
    public function migrate(string $code): string
    {
        $stmts = $this->parser->parse($code);

        if ($stmts === null) {
            // Parser returned null only for empty input.
            return $code;
        }

        $aliasMap = RootNamespaceSwapper::buildAliasMap($stmts);
        $tokens = $this->parser->getTokens();

        $traverser = new NodeTraverser(
            new CloningVisitor(),
            new RootNamespaceSwapper($aliasMap)
        );

        // traverse() returns clones while $stmts still holds the pristine
        // originals; the originals are the required $origStmts argument.
        $newStmts = $traverser->traverse($stmts);

        return $this->printer->printFormatPreserving($newStmts, $stmts, $tokens);
    }
}
