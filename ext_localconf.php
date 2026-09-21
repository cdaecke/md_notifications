<?php

defined('TYPO3') || die();

use Mediadreams\MdNotifications\Controller\NotificationController;
use Mediadreams\MdNotifications\Hooks\TCEmainHook;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

(static function () {
    $plugins = [
        'Count',
        'HasSeen',
        'Delete',
    ];

    foreach ($plugins as $plugin) {
        ExtensionUtility::configurePlugin(
            'MdNotifications',
            $plugin,
            [
                NotificationController::class => lcfirst($plugin),
            ],
            [
                NotificationController::class => lcfirst($plugin),
            ],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
    }

    ExtensionUtility::configurePlugin(
        'MdNotifications',
        'Notifications',
        [
            NotificationController::class => 'list',
        ],
        [
            NotificationController::class => 'list',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    // Add path for email template
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][2025] = 'EXT:md_notifications/Resources/Private/Templates/Email';

    // Hook into saving process of records
    $GLOBALS['TYPO3_CONF_VARS']
            ['SC_OPTIONS']
            ['t3lib/class.t3lib_tcemain.php']
            ['processDatamapClass']
            ['md_notifications'] = TCEmainHook::class;

    // Hook into deleting process of records
    $GLOBALS['TYPO3_CONF_VARS']
            ['SC_OPTIONS']
            ['t3lib/class.t3lib_tcemain.php']
            ['processCmdmapClass']
            ['md_notifications'] = TCEmainHook::class;

    // Add `Table garbage collection task` for notification records
    $GLOBALS['TYPO3_CONF_VARS']
            ['SC_OPTIONS']
            ['scheduler']
            ['tasks']
            [TableGarbageCollectionTask::class]
            ['options']
            ['tables']
            ['tx_mdnotifications_domain_model_notification'] = ['dateField' => 'tstamp', 'expirePeriod' => '30'];
})();
