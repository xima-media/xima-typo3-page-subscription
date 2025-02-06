<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\ViewHelpers;

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

class IconViewHelper extends AbstractViewHelper
{
    public function __construct(protected IconFactory $iconFactory) {}

    /**
     * Initialize arguments.
     *
     * @throws Exception
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('icon', 'string', 'An icon identifier');
        $this->registerArgument('src', 'string', 'A path to a file');
        $this->registerArgument('table', 'string', 'Tablename of record');
        $this->registerArgument('ctype', 'string', 'CType for tt_content record');
        $this->registerArgument('id', 'string', 'Id to set in the svg');
        $this->registerArgument('class', 'string', 'Css class(es) for the svg');
        $this->registerArgument('width', 'string', 'Width of the svg.');
        $this->registerArgument('height', 'string', 'Height of the svg.');
        $this->registerArgument('viewBox', 'string', 'Specifies the view box for the svg');
        $this->registerArgument('aria-hidden', 'string', 'Sets the visibility of the svg for screen readers');
        $this->registerArgument('title', 'string', 'Title of the svg');
        $this->registerArgument('data', 'array', 'Array of data-attributes');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function render(): string
    {
        //        if (((string)$this->arguments['src'] === '') || ((string)$this->arguments['table'] === '') || ((string)$this->arguments['icon'] === '')) {
        //            throw new \Exception('You must either specify a string src, string icon identifier or a table name.', 1728914563);
        //        }

        $attributes = [
            'id' => $this->arguments['id'],
            'class' => $this->arguments['class'],
            'width' => $this->arguments['width'],
            'height' => $this->arguments['height'],
            'viewBox' => $this->arguments['viewBox'],
            'ariaHidden' => $this->arguments['aria-hidden'],
            'title' => $this->arguments['title'],
            'data' => $this->arguments['data'],
        ];

        if ((string)$this->arguments['icon'] !== '') {
            return $this->getInlineSvg($this->iconFactory->getIcon($this->arguments['icon'])->getAlternativeMarkup('inline'), $attributes);
        }

        if ((string)$this->arguments['src'] !== '') {
            return $this->getSvgFromSrc((string)$this->arguments['src'], $attributes);
        }

        if ((string)$this->arguments['table'] !== '') {
            return $this->getIconForRecord((string)$this->arguments['table'], (string)$this->arguments['ctype'], $attributes);
        }

        return '';
    }

    private function getIconForRecord(string $table, string $ctype = '', array $attributes = [])
    {
        if (isset($GLOBALS['TCA'][$table]['ctrl']['iconfile']) && $GLOBALS['TCA'][$table]['ctrl']['iconfile'] !== '') {
            return $this->getSvgFromSrc($GLOBALS['TCA'][$table]['ctrl']['iconfile'], $attributes);
        }

        if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])
            && is_array($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])
        ) {
            if ($table === 'tt_content' && array_key_exists($ctype, $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])) {
                return $this->getInlineSvg($this->iconFactory->getIcon($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$ctype])->getAlternativeMarkup('inline'), $attributes);
            }

            if (array_key_exists('default', $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])) {
                return $this->getInlineSvg($this->iconFactory->getIcon($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['default'])->getAlternativeMarkup('inline'), $attributes);
            }
        }

        return $this->getInlineSvg($this->iconFactory->getIcon('apps-pagetree-page-default')->getAlternativeMarkup('inline'), $attributes);
    }

    private function getSvgFromSrc(string $src, array $attributes = []): string
    {
        $fullPath = GeneralUtility::getFileAbsFileName($src);

        if (!file_exists($fullPath)) {
            return $this->getInlineSvg($this->iconFactory->getIcon('apps-pagetree-drag-place-denied')->getAlternativeMarkup('inline'), $attributes);
        }

        if (pathinfo($fullPath, PATHINFO_EXTENSION) !== 'svg') {
            throw new \Exception('You must provide a svg file.', 1630401474);
        }

        $svgContent = file_get_contents($fullPath);
        if (!$svgContent) {
            throw new \Exception('The svg file must not be empty.', 1630401503);
        }

        return $this->getInlineSvg($svgContent, $attributes);
    }

    /**
     * @param string $svgContent
     * @param array $tags
     * @return string
     * @throws \DOMException
     */
    private function getInlineSvg(
        string $svgContent,
        array $tags = []
    ): string {
        $svgElement = simplexml_load_string($svgContent);
        if (!$svgElement instanceof \SimpleXMLElement) {
            return '';
        }

        $domXml = dom_import_simplexml($svgElement);
        $ownerDocument = $domXml->ownerDocument;
        if (!$ownerDocument instanceof \DOMDocument) {
            return '';
        }

        if ($tags['title']) {
            $titleElement = $ownerDocument->createElement('title', htmlspecialchars((string)$tags['title']));
            if (!$titleElement instanceof \DOMElement) {
                return '';
            }

            $domXml->prepend($titleElement);
        }

        $tags['id'] = htmlspecialchars(trim((string)$tags['id']));
        if ($tags['id'] !== '') {
            $domXml->setAttribute('id', $tags['id']);
        }

        $tags['class'] = htmlspecialchars(trim((string)$tags['class']));
        if ($tags['class'] !== '') {
            $domXml->setAttribute('class', $tags['class']);
        }

        if ((int)$tags['height'] > 0) {
            $domXml->setAttribute('height', (string)$tags['height']);
        }

        if ((int)$tags['width'] > 0) {
            $domXml->setAttribute('width', (string)$tags['width']);
        }

        if ($tags['ariaHidden']) {
            $domXml->setAttribute('aria-hidden', $tags['ariaHidden']);
        }

        $tags['viewBox'] = htmlspecialchars(trim((string)$tags['viewBox']));
        if ($tags['viewBox'] !== '') {
            $domXml->setAttribute('viewBox', $tags['viewBox']);
        }

        if (is_array($tags['data'])) {
            foreach ($tags['data'] as $dataAttributeKey => $dataAttributeValue) {
                $dataAttributeKey = htmlspecialchars(trim((string)$dataAttributeKey));
                $dataAttributeValue = htmlspecialchars(trim((string)$dataAttributeValue));
                if ($dataAttributeKey !== '' && $dataAttributeValue !== '') {
                    $domXml->setAttribute('data-' . $dataAttributeKey, $dataAttributeValue);
                }
            }
        }

        return (string)$ownerDocument->saveXML($ownerDocument->documentElement);
    }
}
