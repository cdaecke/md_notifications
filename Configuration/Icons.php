<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'tx_mdnotifications_notifications' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:md_notifications/Resources/Public/Icons/user_plugin_notifications.svg',
    ],
];
