<?php

use Xima\XimaTypo3PageSubscription\Configuration;

return [
    'ctrl' => [
        'title' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:favorite',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'actions-star',
        ],
        'hideTable' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'pid' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'fe_user' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
