<?php

/**
 * Standalone `Piwik\` -> `Matomo\` root-namespace migration.
 *
 * Style-preserving, format-preserving, idempotent.
 *
 * Usage:
 *   php utils/migrate-namespace/bin/migrate.php <path> [<path> ...]
 *
 * Rewrites every `Piwik\` root-namespace reference to `Matomo\` (honouring the
 * facade `Piwik\Piwik` -> `Matomo\Matomo`) in the PHP files under the given
 * paths, preserving each reference's qualification and the file's formatting.
 * Re-running on already-migrated source is a no-op.
 *
 * @internal Tooling for the `Piwik\` -> `Matomo\` root-namespace migration.
 */

declare(strict_types=1);

// Locate the project vendor/autoload.php by walking up from this script.
$dir = __DIR__;
while ($dir !== '/' && !file_exists($dir . '/vendor/autoload.php')) {
    $dir = dirname($dir);
}
if (!file_exists($dir . '/vendor/autoload.php')) {
    fwrite(STDERR, "Could not find vendor/autoload.php\n");
    exit(1);
}
require_once $dir . '/vendor/autoload.php';

use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Utils\MigrateNamespace\RootNamespaceSwapper;

$paths = array_slice($argv, 1);
if ($paths === []) {
    fwrite(STDERR, "Usage: migrate.php <path> [<path> ...]\n");
    exit(1);
}

$lexer = new Lexer([
    'usedAttributes' => [
        'comments',
        'startLine', 'endLine',
        'startTokenPos', 'endTokenPos',
    ],
]);
$parser = (new ParserFactory())->createForHostVersion($lexer);
$printer = new Standard();

$files = [];
foreach ($paths as $path) {
    if (is_file($path)) {
        $files[] = $path;
    } else {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }
}

$changed = 0;
$total = count($files);
foreach ($files as $file) {
    $oldCode = file_get_contents($file);
    if ($oldCode === false) {
        fwrite(STDERR, "Could not read $file\n");
        continue;
    }

    $oldStmts = $parser->parse($oldCode);
    if ($oldStmts === null) {
        fwrite(STDERR, "Parse error in $file — skipped\n");
        continue;
    }
    $oldTokens = $parser->getTokens();

    $traverser = new NodeTraverser(new CloningVisitor());
    $newStmts = $traverser->traverse($oldStmts);

    // Resolve names (sets `namespacedName` on class-like declarations, so the
    // swapper can detect the facade `class Piwik` in `namespace Piwik`), without
    // replacing name nodes.
    $resolver = new NodeTraverser(new NameResolver(null, [
        'preserveOriginalNames' => true,
        'replaceNodes' => false,
    ]));
    $newStmts = $resolver->traverse($newStmts);

    $swapperTraverser = new NodeTraverser(new RootNamespaceSwapper());
    $newStmts = $swapperTraverser->traverse($newStmts);

    $newCode = $printer->printFormatPreserving($newStmts, $oldStmts, $oldTokens);

    if ($newCode !== $oldCode) {
        file_put_contents($file, $newCode);
        $changed++;
    }
}

fwrite(STDERR, "Migrated $changed of $total file(s).\n");
exit(0);
