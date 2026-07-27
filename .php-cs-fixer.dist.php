<?php

declare(strict_types=1);

/**
 * PHP-CS-Fixer configuration for `daycry/taxid`.
 *
 * Baseline ruleset is `@PSR12`, plus a small set of conservative, widely
 * accepted extras (short array syntax, ordered/deduplicated imports,
 * `declare(strict_types=1)` enforcement). Covers `src/` and `tests/`, plus
 * this config file itself.
 *
 * Usage:
 *   composer cs        # dry-run, shows diff (see composer.json)
 *   vendor/bin/php-cs-fixer fix   # apply fixes
 */

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->append([__FILE__]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'declare_strict_types' => true,
        'trailing_comma_in_multiline' => true,
        'single_quote' => true,
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
