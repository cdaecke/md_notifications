<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Hooks;

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
use Mediadreams\MdNotifications\Utility\RootlineUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TCE main hook
 *
 * This will hook into saving, updating and deleting process of records and
 * adds notifications according to site configuration
 */
class TCEmainHook
{
    /**
     * Add notification information for new record
     *
     * @param string $action The performed action
     * @param string $table The table name of the record
     * @param string $recordUid Temporary id of the record, eg `NEW67b5f96849921638839656`
     * @param array<string, mixed> $fieldArray The data array, which holds all information on the record
     * @param DataHandler $pObj Parent Object
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    public function processDatamap_afterDatabaseOperations(
        string $action,
        string $table,
        string $recordUid,
        array $fieldArray,
        DataHandler &$pObj
    ): void {
        if ($action === 'new') {
            $siteConfig = $this->getSiteConfig($fieldArray['pid']);
            if ($this->inCharge($siteConfig, $fieldArray['pid'], $table)) {
                // get uid of new record
                $recordId = $pObj->substNEWwithIDs[$recordUid];

                if ($recordId <= 0) {
                    $this->enqueueFlashmessage('Notification for record could not be saved!');
                    return;
                }

                $this->saveNotificationInfo($recordId, $table, $fieldArray, $siteConfig);
            }
        } elseif ($action === 'update') {
            // TODO: Check, if there is another way, to get the `pid` of the record
            $pid = (int)$pObj->checkValue_currentRecord['pid'];
            $siteConfig = $this->getSiteConfig($pid);
            if ($this->inCharge($siteConfig, $pid, $table)) {
                $this->updateNotificationInfo($recordUid, $table, $fieldArray);
            }
        }
    }

    /**
     *  Delete notification if record gets deleted
     *
     * @param string $table Table name of record
     * @param int $id Id of record
     * @param array<string, mixed> $recordToDelete Array of all data of record
     * @param bool $recordWasDeleted
     * @param DataHandler $pObj Parent Object
     * @throws SiteNotFoundException
     */
    public function processCmdmap_deleteAction(
        string $table,
        int $id,
        array $recordToDelete,
        bool $recordWasDeleted,
        DataHandler &$pObj
    ): void {
        $siteConfig = $this->getSiteConfig($recordToDelete['pid']);
        if ($this->inCharge($siteConfig, $recordToDelete['pid'], $table)) {
            $databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tx_mdnotifications_domain_model_notification');

            $databaseConnection->delete(
                'tx_mdnotifications_domain_model_notification',
                ['record_key' => $table, 'record_id' => $id]
            );
        }
    }

    /**
     * Save notification info for the record
     *
     * @param int $recordUid Uid of record
     * @param string $recordKey The key of the record (database table name)
     * @param array<string, mixed> $fieldArray Data of news entry
     * @param array<string, mixed> $siteConfig Site configuration
     * @throws \Doctrine\DBAL\Exception
     */
    protected function saveNotificationInfo(
        int $recordUid,
        string $recordKey,
        array $fieldArray,
        array $siteConfig = []
    ): void {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        // find users
        $queryBuilderFeusers = $connectionPool->getQueryBuilderForTable('fe_users');
        $feuserData = $queryBuilderFeusers
            ->select('fe_users.uid')
            ->from('fe_users');

        // if $feGroup is set, just find users with given group
        $frontendGroup = $siteConfig['md_notifications']['feGroup'] ?? null;
        if (is_int($frontendGroup) && $frontendGroup > 0) {
            $feuserData = $feuserData->where(
                $queryBuilderFeusers->expr()->inSet(
                    'usergroup',
                    $queryBuilderFeusers->createNamedParameter($frontendGroup, Connection::PARAM_INT)
                )
            );
        }

        // finally get data
        $feuserData = $feuserData
            ->executeQuery()
            ->fetchAllAssociative();

        // if there is some data, prepare and save it
        if (count($feuserData) > 0) {
            // prepare data to save
            $timestamp = time();
            $dataArray = [];
            foreach ($feuserData as $data) {
                $dataArray[] = [
                    'pid'           => $siteConfig['md_notifications']['storagePid'] ?? 0,
                    'record_key'    => $recordKey,
                    'record_id'     => $recordUid,
                    'record_date'   => $timestamp,
                    'feuser'        => $data['uid'],
                    'data'          => $this->jsonEncode($fieldArray),
                    'tstamp'        => $timestamp,
                    'crdate'        => $timestamp,
                    'hidden'        => $fieldArray['hidden'],
                    'starttime'     => $fieldArray['starttime'] ?? 0,
                    'endtime'       => $fieldArray['endtime'] ?? 0,
                ];
            }

            $colNamesArray = ['pid', 'record_key', 'record_id', 'record_date', 'feuser', 'data', 'tstamp', 'crdate', 'hidden', 'starttime', 'endtime'];

            $dbConnectionNotification = $connectionPool->getConnectionForTable('tx_mdnotifications_domain_model_notification');
            $dbConnectionNotification->bulkInsert(
                'tx_mdnotifications_domain_model_notification',
                $dataArray,
                $colNamesArray
            );
        }
    }

    /**
     * Update notification info for record
     *
     * @param string $recordId Id of record
     * @param string $recordKey Key of record (table name)
     * @param array<string, mixed> $fieldArray Modified data of the record
     */
    protected function updateNotificationInfo(string $recordId, string $recordKey, array $fieldArray): void
    {
        // Get record data in order to update it
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($recordKey);
        $queryBuilder->getRestrictions()
            ->removeAll();

        $recordData = $queryBuilder
            ->select('*')
            ->from($recordKey)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter((int)$recordId, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        foreach ($fieldArray as $key => $value) {
            if (isset($recordData[$key])) {
                // Set changed values in field `data`
                $recordData[$key] = $value;
            }
        }

        // Prepare data for update
        $arrayUpdateData = [
            'tstamp' => time(),
            'data' => $this->jsonEncode($recordData),
        ];

        if (isset($fieldArray['hidden'])) {
            $arrayUpdateData = array_merge($arrayUpdateData, ['hidden' => $fieldArray['hidden']]);
        }

        if (isset($fieldArray['starttime'])) {
            $arrayUpdateData = array_merge($arrayUpdateData, ['starttime' => $fieldArray['starttime']]);
        }

        if (isset($fieldArray['endtime'])) {
            $arrayUpdateData = array_merge($arrayUpdateData, ['endtime' => $fieldArray['endtime']]);
        }

        $databaseConnection = $connectionPool->getConnectionForTable('tx_mdnotifications_domain_model_notification');
        $databaseConnection->update(
            'tx_mdnotifications_domain_model_notification',
            $arrayUpdateData,
            ['record_key' => $recordKey, 'record_id' => $recordId]
        );
    }

    /**
     * Get site configuration
     *
     * @param int $storageId
     * @return array<string, mixed>
     * @throws SiteNotFoundException
     */
    protected function getSiteConfig(int $storageId): array
    {
        if ($storageId > 0) {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $site = $siteFinder->getSiteByPageId($storageId);
            return $site->getConfiguration();
        }

        return [];
    }

    /**
     * Check, if given record should be respected
     *
     * @param array<string, mixed> $siteConfig Array with settings from site configuration
     * @param int $storageId Storage Id of record
     * @param string $recordKey Record key (table name)
     * @return bool
     */
    protected function inCharge(array $siteConfig, int $storageId, string $recordKey): bool
    {
        if (isset($siteConfig['md_notifications'][$recordKey])) {
            $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class);
            return $rootlineUtility->isInRootline($storageId, $siteConfig['md_notifications'][$recordKey]);
        }

        return false;
    }

    /**
     * Show flash message
     *
     * @param string $message
     * @throws Exception
     */
    protected function enqueueFlashmessage(string $message): void
    {
        /** @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            'EXT:md_notifications',
            ContextualFeedbackSeverity::WARNING,
            true
        );

        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        /** @var FlashMessageQueue $defaultFlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Get a formatted JSON representation of a value
     *
     * @param mixed $value
     * @return string|false
     */
    protected function jsonEncode(mixed $value): string|false
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
