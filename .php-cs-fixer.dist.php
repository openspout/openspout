<?php

$config = (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PHP74Migration' => true,
        '@PHPUnit84Migration:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        'php_unit_strict' => false,
        'php_unit_test_class_requires_covers' => false,
    ])
;

$config->getFinder()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;

return $config;