<?php

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return new PhpCsFixer\Config()
    ->setRules([
        '@PER-CS2.0' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
    ])
    ->setFinder($finder);