<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Event;

class HashGenerationModifyEvent
{
    final public const NAME = 'xima_typo3_page_subscription.hash_generation.modify';

    public function __construct(
        protected string $content,
        protected array $data,
        protected array $contentParts,
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getContentParts(): array
    {
        return $this->contentParts;
    }

    public function setContentParts(array $contentParts): void
    {
        $this->contentParts = $contentParts;
    }
}
