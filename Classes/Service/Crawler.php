<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Service;

use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3PageSubscription\Configuration;
use Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem;
use Xima\XimaTypo3PageSubscription\Utility\UrlHelper;

class Crawler
{
    /**
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     * @throws \TYPO3\CMS\Frontend\Typolink\UnableToLinkException
     */
    public function checkPageForElements(int $pageId, bool $cli = false): array
    {
        $result = [];
        $url = UrlHelper::getAbsoluteUrl($pageId);
        if ($cli && !$GLOBALS['BE_USER']->user) {
            Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
            Bootstrap::initializeBackendAuthentication();
        }

        $additionalParameter = (bool)(GeneralUtility::makeInstance(ExtensionConfiguration::class))->get(Configuration::EXT_KEY)['forceUncachedPageForCrawler'] ? '?no_cache=1' : '';

        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $response = $requestFactory->request(
            $url . $additionalParameter,
            'GET',
            [
                'headers' => [
                    'X-PageSubscription-Crawler' => true,
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                ],
            ]
        );

        $content = $response->getBody()->getContents();

        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $nodes = $this->fetchDomTags($dom);
        foreach ($nodes as $node) {
            $updateItem = $this->parseUpdateItem($node['node'], $node['children']);
            if ($updateItem->getRecuid()) {
                $result[] = $updateItem;
            }
        }

        return $result;
    }

    public function filterByElementId(array $updateItems, string $elementId): array
    {
        $filteredItems = [];

        foreach ($updateItems as $item) {
            if ($item->getIdentifier() === $elementId) {
                $filteredItems[] = $item;
            } elseif (!empty($item->getChildren())) {
                $filteredChildren = $this->filterByElementId($item->getChildren(), $elementId);
                if ($filteredChildren !== []) {
                    $filteredItems = array_merge($filteredItems, $filteredChildren);
                }
            }
        }

        return $filteredItems;
    }

    /**
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     * @throws \TYPO3\CMS\Frontend\Typolink\UnableToLinkException
     */
    private function fetchDomTags(\DOMDocument $dom): array
    {
        $class = 'page-subscription--update-information';
        $nodes = [];

        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $this->traverseDom($body, $class, $nodes);
        }

        return $nodes;
    }

    private function traverseDom(\DOMNode $node, string $class, array &$nodes, ?array &$parentNodeWithClass = null): void
    {
        if ($node->nodeType === XML_ELEMENT_NODE && $node->nodeName === 'div') {
            $hasClass = $node->hasAttributes() && $node->attributes->getNamedItem('class') && str_contains($node->attributes->getNamedItem('class')->nodeValue, $class);

            if ($hasClass) {
                $currentNode = [
                    'node' => $node,
                    'children' => [],
                ];

                if ($parentNodeWithClass !== null) {
                    $parentNodeWithClass['children'][] = &$currentNode;
                } else {
                    $nodes[] = &$currentNode;
                }

                $parentNodeWithClass = &$currentNode;
            }
        }

        foreach ($node->childNodes as $childNode) {
            $this->traverseDom($childNode, $class, $nodes, $parentNodeWithClass);
        }
    }

    private function parseUpdateItem(\DOMNode $node, array $children = []): UpdateItem
    {
        $updateItem = new UpdateItem();
        foreach ($node->attributes as $attr) {
            if (str_starts_with($attr->name, 'data-')) {
                $field = substr($attr->name, 5);
                if (method_exists($updateItem, 'set' . ucfirst($field))) {
                    $updateItem->{'set' . ucfirst($field)}($attr->value);
                }
            }
        }

        if ($children !== []) {
            foreach ($children as $child) {
                $updateItem->addChild($this->parseUpdateItem($child['node'], $child['children']));
            }
        }

        return $updateItem;
    }
}
