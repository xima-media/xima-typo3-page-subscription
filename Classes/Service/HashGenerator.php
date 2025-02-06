<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Service;

use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use Xima\XimaTypo3PageSubscription\Event\HashGenerationModifyEvent;

readonly class HashGenerator
{
    public function __construct(protected EventDispatcher $eventDispatcher) {}

    public function generate(string $content, array $data): array
    {
        return [
            'identifier' => $this->generateIdentifier($data),
            'hash' => $this->generateHash($content, $data),
        ];
    }

    public function generateIdentifier(array $data): string
    {
        $identifierParts = [$data['table']];

        if (array_key_exists('ctype', $data) && $data['ctype'] !== '') {
            $identifierParts[] = $data['ctype'];
        }

        $identifierParts[] = $data['recuid'];
        return implode('--', $identifierParts);
    }

    private function generateHash(string $content, array $data): string
    {
        $contentParts = [];
        $contentParts['text'] = preg_replace('/\s+/', '', strip_tags($content));

        // Extract href attributes from links
        preg_match_all('/<a[^>]+href="([^"]+)"/i', $content, $hrefMatches);
        $contentParts['href'] = implode('', $hrefMatches[1]);

        // Extract src attributes from images
        preg_match_all('/<img[^>]+src="([^"]+)"/i', $content, $srcMatches);
        $contentParts['src'] = implode('', $srcMatches[1]);

        // Extract data-page-subscription-additional-data attributes from all HTML elements
        preg_match_all('/<[^>]+data-page-subscription-additional-data="([^"]+)"/i', $content, $dataMatches);
        $contentParts['data-page-subscription-additional-data'] = implode('', $dataMatches[1]);

        $this->eventDispatcher->dispatch(new HashGenerationModifyEvent($content, $data, $contentParts));

        return md5(serialize(implode('', $contentParts)));
    }
}
