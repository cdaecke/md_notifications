<?php

declare(strict_types=1);

use TYPO3\CodingStandards\CsFixerConfig;

$rootPath = dirname(__DIR__, 2);
$config = CsFixerConfig::create();
$config->setCacheFile($rootPath . '/.Build/php-cs-fixer.cache');
$config->getFinder()
    ->in($rootPath)
    ->exclude([
        '.Build',
        '.git',
        '.idea',
        'var',
    ]);

return $config;
