<?php


declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Exception\Configuration\InvalidConfigurationException;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/packages',
        __DIR__ . '/src',
    ])
    ->withPhpVersion(PhpVersion::PHP_85)
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true
    )
->withSets([
    SetList::CODE_QUALITY,
    SetList::CODING_STYLE,
]);