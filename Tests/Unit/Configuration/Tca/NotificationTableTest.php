<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Tests\Unit\Configuration\Tca;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Information\Typo3Version;

final class NotificationTableTest extends TestCase
{
    #[Test]
    public function searchConfigurationMatchesInstalledTypo3Version(): void
    {
        /** @var array{ctrl: array<string, mixed>, columns: array<string, array{config: array<string, mixed>}>} $tca */
        $tca = require dirname(__DIR__, 4) . '/Configuration/TCA/tx_mdnotifications_domain_model_notification.php';

        self::assertArrayNotHasKey('searchable', $tca['columns']['record_key']['config']);
        self::assertArrayNotHasKey('searchable', $tca['columns']['data']['config']);

        if ((new Typo3Version())->getMajorVersion() < 14) {
            self::assertSame('record_key,data', $tca['ctrl']['searchFields']);
            self::assertArrayNotHasKey('searchable', $tca['columns']['starttime']['config']);
            self::assertArrayNotHasKey('searchable', $tca['columns']['endtime']['config']);
            self::assertArrayNotHasKey('searchable', $tca['columns']['record_date']['config']);

            return;
        }

        self::assertArrayNotHasKey('searchFields', $tca['ctrl']);
        self::assertFalse($tca['columns']['starttime']['config']['searchable']);
        self::assertFalse($tca['columns']['endtime']['config']['searchable']);
        self::assertFalse($tca['columns']['record_date']['config']['searchable']);
    }
}
