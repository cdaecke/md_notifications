<?php

declare(strict_types=1);

use a9f\Fractor\Configuration\FractorConfiguration;
use a9f\Fractor\ValueObject\Indent;
use a9f\FractorXliff\Configuration\XliffProcessorOption;
use a9f\Typo3Fractor\Set\Typo3LevelSetList;

$rootPath = dirname(__DIR__, 2);

return FractorConfiguration::configure()
    ->withPaths([
        $rootPath . '/Configuration',
        $rootPath . '/Resources',
    ])
    ->withSkip([
        $rootPath . '/.Build',
        $rootPath . '/var',
    ])
    ->withOptions([
        XliffProcessorOption::INDENT_CHARACTER => Indent::STYLE_TAB,
        XliffProcessorOption::INDENT_SIZE => 1,
    ])
    ->withSets([
        Typo3LevelSetList::UP_TO_TYPO3_14,
    ]);
