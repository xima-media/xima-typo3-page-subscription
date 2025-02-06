<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Xima\XimaTypo3PageSubscription\Configuration;

defined('TYPO3') || die();

call_user_func(function () {
    foreach ([
        'subscriptions' => ['icon' => 'actions-eye', 'showFields' => 'tx_ximatypo3pagesubscription_ignore_element_ids, tx_ximatypo3pagesubscription_filter_element_ids'],
        'favorites' => ['icon' => 'actions-star', 'showFields' => ''],
    ] as $pluginName => $config) {
        $pluginSignature = ExtensionUtility::registerPlugin(
            Configuration::EXT_NAME,
            $pluginName,
            'LLL:EXT:' . Configuration::EXT_KEY . sprintf('/Resources/Private/Language/locallang_be.xlf:plugin.%s.label', $pluginName)
        );

        $GLOBALS['TCA']['tt_content']['types'][$pluginSignature]['showitem'] = '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;general,
                header,
                ' . $config['showFields'] . ',
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;;access,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                rowDescription,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        ';

        $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$pluginSignature] = $config['icon'];
    }

    ExtensionManagementUtility::addTCAcolumns(
        'tt_content',
        [
            'tx_ximatypo3pagesubscription_ignore_element_ids' => [
                'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tt_content.ignore_element_ids',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                ],
            ],
            'tx_ximatypo3pagesubscription_filter_element_ids' => [
                'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tt_content.filter_element_ids',
                'config' => [
                    'type' => 'input',
                ],
            ],
        ]
    );
});
