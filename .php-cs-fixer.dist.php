<?php

declare(strict_types=1);

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$config = (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PHP83Migration' => true,
        '@PHP80Migration:risky' => true,
        '@PHPUnit100Migration:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        'comment_to_phpdoc' => false,
        'final_internal_class' => false,
        'global_namespace_import' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'php_unit_strict' => false,
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_line_span' => ['property' => 'single'],
        'self_static_accessor' => true,
        'use_arrow_functions' => false,
    ])
;

$config->getFinder()
    ->in(__DIR__.'/benchmarks')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;

return $config;
