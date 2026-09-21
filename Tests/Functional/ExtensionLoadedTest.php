<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversNothing]
final class ExtensionLoadedTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['typo3/cms-scheduler'];

    protected array $testExtensionsToLoad = ['mediadreams/md-notifications'];

    protected bool $initializeDatabase = false;

    #[Test]
    public function isLoadedWithExtensionKey(): void
    {
        self::assertTrue(ExtensionManagementUtility::isLoaded('md_notifications'));
    }

    #[Test]
    public function isLoadedWithComposerPackageName(): void
    {
        self::assertTrue(ExtensionManagementUtility::isLoaded('mediadreams/md-notifications'));
    }
}
