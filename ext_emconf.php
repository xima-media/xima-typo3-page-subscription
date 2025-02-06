<?php

/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Page Subscription',
    'description' => 'This extension provides an update subscription functionality for frontend user regarding changes on content elements on a page.',
    'category' => 'plugin',
    'author' => 'Konrad Michalik',
    'author_email' => 'konrad.michalik@xima.de',
    'author_company' => 'XIMA Media GmbH',
    'state' => 'alpha',
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-13.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
