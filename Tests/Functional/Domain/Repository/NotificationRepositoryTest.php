<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Tests\Functional\Domain\Repository;

use Mediadreams\MdNotifications\Domain\Model\Notification;
use Mediadreams\MdNotifications\Domain\Repository\NotificationRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(NotificationRepository::class)]
#[CoversClass(Notification::class)]
final class NotificationRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['typo3/cms-scheduler'];

    protected array $testExtensionsToLoad = ['mediadreams/md-notifications'];

    private NotificationRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->get(NotificationRepository::class);
        $querySettings = $this->get(QuerySettingsInterface::class);
        $querySettings->setRespectStoragePage(false);
        $this->subject->setDefaultQuerySettings($querySettings);
    }

    #[Test]
    public function countItemsForNoRecordsReturnsZero(): void
    {
        self::assertSame(0, $this->subject->countItems(1));
    }

    #[Test]
    public function countItemsCanBeFilteredByRecordKeys(): void
    {
        $this->importNotificationFixtures();

        self::assertSame(3, $this->subject->countItems(1));
        self::assertSame(2, $this->subject->countItems(1, 'pages'));
        self::assertSame(1, $this->subject->countItems(1, 'tx_news_domain_model_news'));
        self::assertSame(3, $this->subject->countItems(1, 'pages, tx_news_domain_model_news'));
        self::assertSame(1, $this->subject->countItems(2));
    }

    #[Test]
    public function getListFiltersByFrontendUserAndRecordKey(): void
    {
        $this->importNotificationFixtures();

        $result = $this->subject->getList(1, 'pages');

        self::assertCount(2, $result);
        $result->rewind();
        $firstNotification = $result->current();
        self::assertInstanceOf(Notification::class, $firstNotification);
        self::assertSame(101, $firstNotification->getRecordId());
    }

    #[Test]
    public function hasSeenUsesEnabledRecordsForTheSelectedUser(): void
    {
        $this->importNotificationFixtures();

        self::assertSame(1, $this->subject->hasSeen('pages', 100, 1));
        self::assertSame(0, $this->subject->hasSeen('pages', 102, 1));
        self::assertSame(1, $this->subject->hasSeen('pages', 100, 2));
        self::assertSame(0, $this->subject->hasSeen('pages', 999, 1));
    }

    #[Test]
    public function deleteEntryDeletesOnlyMatchingNotificationAndInvalidatesHasSeenCache(): void
    {
        $this->importNotificationFixtures();
        self::assertSame(1, $this->subject->hasSeen('pages', 100, 1));

        $this->subject->deleteEntry('pages', 100, 1);

        self::assertSame(0, $this->subject->hasSeen('pages', 100, 1));
        self::assertSame(1, $this->subject->hasSeen('pages', 101, 1));
        self::assertSame(1, $this->subject->hasSeen('pages', 100, 2));

        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable(NotificationRepository::TABLE_NAME);
        $remainingMatchingRecords = $queryBuilder
            ->count('uid')
            ->from(NotificationRepository::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'record_key',
                    $queryBuilder->createNamedParameter('pages')
                ),
                $queryBuilder->expr()->eq(
                    'record_id',
                    $queryBuilder->createNamedParameter(100, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();

        self::assertSame(1, (int)$remainingMatchingRecords);
    }

    #[Test]
    public function getUsersWithNotificationsGroupsNotificationDataByFrontendUser(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUsers.csv');
        $this->importNotificationFixtures();

        $result = $this->subject->getUsersWithNotifications([10]);

        $userIds = array_keys($result);
        sort($userIds);
        self::assertSame([1, 2], $userIds);
        self::assertSame('first@example.test', $result[1]['user']['email']);
        self::assertCount(2, $result[1]['notification_records']);
        $titles = array_column(
            array_column($result[1]['notification_records'], 'record_data'),
            'title'
        );
        sort($titles);
        self::assertSame(['First page', 'Second page'], $titles);
        self::assertCount(1, $result[2]['notification_records']);
    }

    private function importNotificationFixtures(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Notifications.csv');
    }
}
