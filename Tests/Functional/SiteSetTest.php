<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Site\SiteSettingsFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversNothing]
final class SiteSetTest extends FunctionalTestCase
{
    private const SITE_SET_NAME = 'mediadreams/md-notifications';

    protected array $coreExtensionsToLoad = ['typo3/cms-scheduler'];

    protected array $testExtensionsToLoad = ['mediadreams/md-notifications'];

    protected bool $initializeDatabase = false;

    #[Test]
    public function isRegisteredWithTypoScriptAndExpectedSettings(): void
    {
        $setRegistry = $this->get(SetRegistry::class);
        $siteSet = $setRegistry->getSet(self::SITE_SET_NAME);

        self::assertNotNull($siteSet);
        self::assertArrayNotHasKey(self::SITE_SET_NAME, $setRegistry->getInvalidSets());
        self::assertSame(
            'LLL:EXT:md_notifications/Configuration/Sets/MdNotifications/labels.xlf:label',
            $siteSet->label
        );
        self::assertNotNull($siteSet->typoscript);

        $siteSettings = $this->get(SiteSettingsFactory::class)->createSettings([self::SITE_SET_NAME]);

        self::assertSame(
            'EXT:md_notifications/Resources/Private/Templates/',
            $siteSettings->get('md_notifications.view.templateRootPath')
        );
        self::assertSame(
            'EXT:md_notifications/Resources/Private/Partials/',
            $siteSettings->get('md_notifications.view.partialRootPath')
        );
        self::assertSame(
            'EXT:md_notifications/Resources/Private/Layouts/',
            $siteSettings->get('md_notifications.view.layoutRootPath')
        );
    }
}
