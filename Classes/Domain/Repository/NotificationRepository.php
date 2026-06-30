<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Domain\Repository;

/**
 * This file is part of the "Notifications" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 *
 * (c) 2025 Christoph Daecke <typo3@mediadreams.org>
 */

use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * The repository for Notifications
 */
class NotificationRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    const TABLE_NAME = 'tx_mdnotifications_domain_model_notification';

    /**
     * Request-scoped cache to avoid repeated DB queries for hasSeen() within the same request.
     * Keyed by feuser UID and record_key, holds all record_ids the user has a notification for.
     */
    private FrontendInterface $runtimeCache;

    public function injectCacheManager(CacheManager $cacheManager): void
    {
        $this->runtimeCache = $cacheManager->getCache('runtime');
    }

    /**
     * Set default ordering for repository
     */
    protected $defaultOrderings = [
        'recordDate' => QueryInterface::ORDER_DESCENDING,
        'uid' => QueryInterface::ORDER_DESCENDING,
    ];

    /**
     * Get list of notification records for given user
     *
     *
     * @param int $feuserUid
     * @param string $recordKeys Comma separated string of table names, eg. `pages, tx_news_domain_model_news`
     * @return QueryResult
     */
    public function getList(int $feuserUid, string $recordKeys = ''): QueryResult
    {
        $query = $this->createQuery();
        $constraints[] = $query->equals('feuser', $feuserUid);

        if (!empty($recordKeys)) {
            $types = GeneralUtility::trimExplode(',', $recordKeys);

            $orStatements = [];
            foreach ($types as $type) {
                $orStatements[] = $query->equals('record_key', $type);
            }

            if (count($orStatements) > 0) {
                $constraints[] = $query->logicalOr(...$orStatements);
            }
        }

        $query->matching($query->logicalAnd(...$constraints));

        //$queryParser = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser::class);
        //debug($queryParser->convertQueryToDoctrineQueryBuilder($query)->getSQL());
        //debug($queryParser->convertQueryToDoctrineQueryBuilder($query)->getParameters());

        return $query->execute();
    }

    /**
     * Indicates whether the user has a notification for the given record.
     *
     * On the first call for a feuser+recordKey combination, all matching record_ids are loaded
     * in a single query and stored in the RuntimeCache. Subsequent calls within the same request
     * return immediately from the cache, reducing N DB queries (e.g. one per news list item)
     * to a single query per record type per request.
     *
     * @param string $recordKey The record key (table name)
     * @param int $recordUid Uid of the record
     * @param int $feuserUid Frontend user Uid
     * @return int 1 if a notification exists, 0 otherwise
     */
    public function hasSeen(string $recordKey, int $recordUid, int $feuserUid): int
    {
        $cacheKey = 'md_notifications_' . $feuserUid . '_' . md5($recordKey);
        $seenIds = $this->runtimeCache->get($cacheKey);

        if ($seenIds === false) {
            $seenIds = $this->loadSeenRecordIds($recordKey, $feuserUid);
            $this->runtimeCache->set($cacheKey, $seenIds);
        }

        return in_array($recordUid, $seenIds) ? 1 : 0;
    }

    /**
     * Loads all record_ids for which the given user has a notification of the given record type.
     * TYPO3's DefaultRestrictionContainer is applied automatically (deleted, hidden, starttime, endtime).
     *
     * @param string $recordKey The record key (table name)
     * @param int $feuserUid Frontend user Uid
     * @return array<int> Flat array of record_ids
     */
    private function loadSeenRecordIds(string $recordKey, int $feuserUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(static::TABLE_NAME);

        return $queryBuilder
            ->select('record_id')
            ->from(static::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'record_key',
                    $queryBuilder->createNamedParameter($recordKey, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'feuser',
                    $queryBuilder->createNamedParameter($feuserUid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchFirstColumn();
    }

    /**
     * Get number of notifications for user
     *
     * @param int $feuserUid Frontend user Uid
     * @param string|null $recordKeys Comma separated string of table names, eg. `pages, tx_news_domain_model_news`
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function countItems(int $feuserUid, string $recordKeys = null): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(static::TABLE_NAME);

        $queryBuilder = $queryBuilder
            ->count('uid')
            ->from(static::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'feuser',
                    $queryBuilder->createNamedParameter($feuserUid, Connection::PARAM_INT)
                )
            );

        if ($recordKeys !== null) {
            $types = GeneralUtility::trimExplode(',', $recordKeys);

            $orStatements = [];
            foreach ($types as $type) {
                $orStatements[] = $queryBuilder->expr()->eq(
                    'record_key',
                    $queryBuilder->createNamedParameter($type, Connection::PARAM_STR)
                );
            }

            if (count($orStatements) > 0) {
                $queryBuilder->andWhere($queryBuilder->expr()->or(...$orStatements));
            }
        }

        return $queryBuilder->executeQuery()->fetchOne();
    }

    /**
     * Delete entry for given record and user and invalidate the RuntimeCache for that
     * feuser+recordKey combination so that a subsequent hasSeen() call in the same request
     * reflects the deletion instead of returning stale cached data.
     *
     * @param string $recordKey The record key (table name)
     * @param int $recordUid Uid of the record
     * @param int $feuserUid Uid of feuser record
     * @return void
     */
    public function deleteEntry(string $recordKey, int $recordUid, int $feuserUid): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(static::TABLE_NAME);

        $connection->delete(static::TABLE_NAME, [
            'record_key' => $recordKey,
            'record_id' => $recordUid,
            'feuser' => $feuserUid,
        ]);

        $cacheKey = 'md_notifications_' . $feuserUid . '_' . md5($recordKey);
        $this->runtimeCache->remove($cacheKey);
    }

    /**
     * Get all users which have notifications in selected Storage Pids.
     * Number of notifications is added in field `notificationItems`
     *
     * @param array $storageIds Comma separated list of storage Ids
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getUsersWithNotifications(array $storageIds): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(static::TABLE_NAME);

        $queryBuilder = $queryBuilder->select(
                'notifications.record_key',
                'notifications.record_id',
                'notifications.record_date',
                'notifications.data',
                'u.*'
            )
            ->from(static::TABLE_NAME, 'notifications')
            ->leftJoin(
                'notifications',
                'fe_users',
                'u',
                $queryBuilder->expr()->eq('u.uid', 'notifications.feuser')
            )
            ->where(
                $queryBuilder->expr()->in(
                    'notifications.pid',
                    $queryBuilder->createNamedParameter($storageIds, ArrayParameterType::INTEGER)
                )
            );

        $dbResult = $queryBuilder->executeQuery()
            ->fetchAllAssociative();

        $result = [];
        foreach ($dbResult as $item) {
            $result[$item['uid']]['user'] = $item;
            $result[$item['uid']]['notification_records'][] = [
                'record_key' => $item['record_key'],
                'record_id' => $item['record_id'],
                'record_date' => $item['record_date'],
                'record_data' => json_decode($item['data'], true),
            ];
        }

        return $result;
    }
}
