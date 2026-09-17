<?php

declare(strict_types=1);

use TYPO3\TestingFramework\Core\Testbase;

require dirname(__DIR__, 2) . '/.Build/vendor/autoload.php';

$testbase = new Testbase();
$testbase->defineOriginalRootPath();
$testbase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/tests');
$testbase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/transient');
