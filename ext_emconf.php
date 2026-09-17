<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Notifications',
    'description' => 'Notify frontend users about the creation of new data records. Every data type can be configured easily, for example pages (blog posts) or tx_news records.',
    'category' => 'fe',
    'author' => 'Christoph Daecke',
    'author_email' => 'typo3@mediadreams.org',
    'state' => 'stable',
    'version' => '1.0.4',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.5.99',
            'typo3' => '13.4.0-14.3.99',
            'extbase' => '13.4.0-14.3.99',
            'fluid' => '13.4.0-14.3.99',
            'frontend' => '13.4.0-14.3.99',
            'scheduler' => '13.4.0-14.3.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
