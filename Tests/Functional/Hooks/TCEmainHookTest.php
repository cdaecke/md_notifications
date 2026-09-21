<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Tests\Functional\Hooks;

use Mediadreams\MdNotifications\Domain\Repository\NotificationRepository;
use Mediadreams\MdNotifications\Hooks\TCEmainHook;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(TCEmainHook::class)]
final class TCEmainHookTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['typo3/cms-scheduler'];

    protected array $testExtensionsToLoad = ['mediadreams/md-notifications'];

    private ConnectionPool $connectionPool;

    private DataHandler $dataHandler;

    private TestableTCEmainHook $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(
            __DIR__ . '/../Domain/Repository/Fixtures/FrontendUsers.csv'
        );
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Pages.csv');

        $this->connectionPool = $this->get(ConnectionPool::class);
        $this->dataHandler = $this->get(DataHandler::class);
        $this->subject = new TestableTCEmainHook();
    }

    #[Test]
    public function hookCreatesUpdatesAndDeletesNotification(): void
    {
        $temporaryId = 'NEW-functional-test';
        $this->dataHandler->substNEWwithIDs[$temporaryId] = 100;

        $this->subject->processDatamap_afterDatabaseOperations(
            'new',
            'pages',
            $temporaryId,
            [
                'pid' => 10,
                'title' => 'Initial title',
                'hidden' => 0,
                'starttime' => 0,
                'endtime' => 0,
            ],
            $this->dataHandler
        );

        $createdNotifications = $this->fetchNotificationsForRecord(100);
        self::assertCount(1, $createdNotifications);
        self::assertSame(1, (int)$createdNotifications[0]['feuser']);
        self::assertSame(10, (int)$createdNotifications[0]['pid']);
        self::assertSame(
            'Initial title',
            json_decode((string)$createdNotifications[0]['data'], true, 512, JSON_THROW_ON_ERROR)['title']
        );

        $this->dataHandler->checkValue_currentRecord = ['pid' => 10];
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'pages',
            '100',
            ['title' => 'Updated title'],
            $this->dataHandler
        );

        $updatedNotifications = $this->fetchNotificationsForRecord(100);
        self::assertCount(1, $updatedNotifications);
        self::assertSame(
            'Updated title',
            json_decode((string)$updatedNotifications[0]['data'], true, 512, JSON_THROW_ON_ERROR)['title']
        );

        $this->subject->processCmdmap_deleteAction(
            'pages',
            100,
            ['pid' => 10],
            true,
            $this->dataHandler
        );

        self::assertSame([], $this->fetchNotificationsForRecord(100));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchNotificationsForRecord(int $recordId): array
    {
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable(NotificationRepository::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->select('*')
            ->from(NotificationRepository::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'record_key',
                    $queryBuilder->createNamedParameter('pages')
                ),
                $queryBuilder->expr()->eq(
                    'record_id',
                    $queryBuilder->createNamedParameter($recordId, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }
}

final class TestableTCEmainHook extends TCEmainHook
{
    /**
     * @return array<string, mixed>
     */
    protected function getSiteConfig(int $storageId): array
    {
        return [
            'md_notifications' => [
                'storagePid' => 10,
                'feGroup' => 1,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $siteConfig
     */
    protected function inCharge(array $siteConfig, int $storageId, string $recordKey): bool
    {
        return true;
    }
}
