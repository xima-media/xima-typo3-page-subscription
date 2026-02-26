<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Xima\XimaTypo3PageSubscription\Configuration;
use Xima\XimaTypo3PageSubscription\Controller\RecordController;

defined('TYPO3') || die();

foreach ([
    Configuration::PLUGIN_NAME_SUBSCRIPTIONS => 'subscriptions',
    Configuration::PLUGIN_NAME_FAVORITES => 'favorites',
] as $pluginName => $actions) {
    ExtensionUtility::configurePlugin(
        Configuration::EXT_NAME,
        $actions,
        [
            RecordController::class => $actions,
        ],
        [
            RecordController::class => $actions,
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['xtps'] = ['Xima\\XimaTypo3PageSubscription\\ViewHelpers'];

/*
 * Register Interpreter
 */
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerInterpreter'] = [];

// Content
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerInterpreter'][\TYPO3\CMS\Core\Resource\File::class] = \Xima\XimaTypo3PageSubscription\Interpreter\FileInterpreter::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerInterpreter'][\TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockData::class] = \Xima\XimaTypo3PageSubscription\Interpreter\ContentBlockInterpreter::class;

// News
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerInterpreter'][\GeorgRinger\News\Domain\Model\NewsDefault::class] = \Xima\XimaTypo3PageSubscription\Interpreter\NewsInterpreter::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerInterpreter'][\GeorgRinger\News\Domain\Model\NewsInternal::class] = \Xima\XimaTypo3PageSubscription\Interpreter\NewsInterpreter::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerInterpreter'][\GeorgRinger\News\Domain\Model\NewsExternal::class] = \Xima\XimaTypo3PageSubscription\Interpreter\NewsInterpreter::class;
