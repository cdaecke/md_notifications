<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;

$tca = [
    'ctrl' => [
        'title' => 'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:tx_mdnotifications_domain_model_notification',
        'label' => 'feuser',
        'label_alt' => 'record_key,record_id',
        'label_alt_force' => 1,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'iconfile' => 'EXT:md_notifications/Resources/Public/Icons/user_plugin_notifications.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'record_key, record_id, feuser, record_date, data, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, starttime, endtime'],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 0,
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_mdnotifications_domain_model_notification',
                'foreign_table_where' => 'AND {#tx_mdnotifications_domain_model_notification}.{#pid}=###CURRENT_PID### AND {#tx_mdnotifications_domain_model_notification}.{#sys_language_uid} IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],

        'record_key' => [
            'exclude' => false,
            'label' => 'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:tx_mdnotifications_domain_model_notification.record_key',
            'description' => 'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:tx_mdnotifications_domain_model_notification.record_key.description',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
                'default' => '',
            ],
        ],
        'record_id' => [
            'exclude' => false,
            'label' => 'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:tx_mdnotifications_domain_model_notification.record_id',
            'description' => 'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:tx_mdnotifications_domain_model_notification.record_id.description',
            'config' => [
                'type' => 'number',
                'size' => 30,
                'required' => true,
            ],
        ],
        'feuser' => [
            'exclude' => false,
            'label' => 'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:tx_mdnotifications_domain_model_notification.feuser',
            'description' => 'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:tx_mdnotifications_domain_model_notification.feuser.description',
            'config' => [
                'type' => 'group',
                'allowed' => 'fe_users',
                'foreign_table' => 'fe_users',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
                'eval' => 'int',
                'required' => true,
                'suggestOptions' => [
                    'default' => [
                        'searchWholePhrase' => 1,
                    ],
                ],
            ],
        ],
        'record_date' => [
            'exclude' => false,
            'label' => 'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:tx_mdnotifications_domain_model_notification.record_date',
            'description' => 'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:tx_mdnotifications_domain_model_notification.record_date.description',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'size' => 20,
                'default' => time(),
            ],
        ],
        'data' => [
            'exclude' => false,
            'label' => 'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:tx_mdnotifications_domain_model_notification.data',
            'description' => 'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:tx_mdnotifications_domain_model_notification.data.description',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'eval' => 'trim',
                'default' => '',
            ],
        ],

    ],
];

if ((new Typo3Version())->getMajorVersion() < 14) {
    $tca['ctrl']['searchFields'] = 'record_key,data';

    return $tca;
}

foreach (['starttime', 'endtime', 'record_date'] as $fieldName) {
    $tca['columns'][$fieldName]['config']['searchable'] = false;
}

return $tca;
