<?php

declare(strict_types=1);

$remoteHeaderUrl = 'https://raw.githubusercontent.com/momo-framework/.github/refs/heads/main/LICENSE_HEADER';
$context = stream_context_create(['http' => ['timeout' => 2]]);
$header = @file_get_contents($remoteHeaderUrl, false, $context);

$rules = [
    '@PER-CS2.0'           => true,
    'strict_param'         => true,
    'array_syntax'         => ['syntax' => 'short'],
    'declare_strict_types' => true,
];

if ($header !== false && !empty(trim($header))) {
    $rules['header_comment'] = [
        'header'       => trim($header),
        'comment_type' => 'PHPDoc',
        'location'     => 'after_open',
        'separate'     => 'both',
    ];
}

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder($finder);