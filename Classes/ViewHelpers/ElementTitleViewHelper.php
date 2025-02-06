<?php

namespace Xima\XimaTypo3PageSubscription\ViewHelpers;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use Xima\XimaTypo3PageSubscription\Service\HashGenerator;

class ElementTitleViewHelper extends AbstractTagBasedViewHelper
{
    public function __construct(protected HashGenerator $hashGenerator)
    {
        parent::__construct();
    }

    /**
     * Register arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('elementId', 'string', 'Element identifier', true);
    }

    public function render(): string
    {
        $elementIdentifier = $this->arguments['elementId'];
        // eg. 'sys_file--90' or 'tt_content--textmedia--123'
        $parts = explode('--', (string)$elementIdentifier);

        if (count($parts) >= 2) {
            $table = $parts[0];
            $uid = end($parts);
        }

        $record = BackendUtility::getRecord($table, $uid);
        if ($record === null) {
            return '';
        }

        $title = $record[$GLOBALS['TCA'][$table]['ctrl']['label']] ?? $record['title'];

        return $title !== '' ? $title : '[' . $GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title') . ']';
    }
}
