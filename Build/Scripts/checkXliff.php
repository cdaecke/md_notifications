<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__, 2);
$patterns = [
    $rootPath . '/Resources/Private/Language/*.xlf',
    $rootPath . '/Configuration/Sets/*/*.xlf',
];
$files = [];
foreach ($patterns as $pattern) {
    $matches = glob($pattern);
    if ($matches !== false) {
        array_push($files, ...$matches);
    }
}
sort($files);

if ($files === []) {
    fwrite(STDERR, 'No XLIFF files found.' . PHP_EOL);
    exit(1);
}

$hasErrors = false;
libxml_use_internal_errors(true);

foreach ($files as $file) {
    $document = new DOMDocument();
    if (!$document->load($file, LIBXML_NONET)) {
        $hasErrors = true;
        foreach (libxml_get_errors() as $error) {
            fwrite(STDERR, sprintf('%s:%d: %s', $file, $error->line, $error->message));
        }
    }
    libxml_clear_errors();
}

exit($hasErrors ? 1 : 0);
