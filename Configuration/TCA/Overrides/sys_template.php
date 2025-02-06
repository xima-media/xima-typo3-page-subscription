<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die('Access denied.');

ExtensionManagementUtility::addStaticFile(
    \Xima\XimaTypo3PageSubscription\Configuration::EXT_KEY,
    'Configuration/TypoScript',
    'Page Watch'
);
