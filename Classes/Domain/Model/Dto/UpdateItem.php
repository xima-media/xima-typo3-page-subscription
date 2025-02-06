<?php

namespace Xima\XimaTypo3PageSubscription\Domain\Model\Dto;

final class UpdateItem implements \Stringable
{
    private ?int $recuid = null;

    private string $table;

    private ?string $ctype = null;

    private ?string $label = null;

    private int $tstamp;

    private ?int $type = null;

    private ?string $title = null;

    private ?string $link = null;

    private ?string $identifier = null;

    private ?string $hash = null;

    private ?string $interpreter = null;

    private array $children = [];

    private Subscription $subscription;

    public function __toString(): string
    {
        $attributes = [
            $this->recuid,
            $this->table,
            $this->ctype,
            $this->label,
            $this->tstamp,
            $this->type,
            $this->title,
            $this->link,
            $this->identifier,
            $this->hash,
            $this->interpreter,
            implode(',', $this->children),
        ];

        return implode(' ', array_filter($attributes, fn($attr) => $attr !== null && $attr !== ''));
    }

    public function getRecuid(): ?int
    {
        return $this->recuid;
    }

    public function setRecuid(int|string $recuid): void
    {
        $this->recuid = (int)$recuid;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    public function getCtype(): ?string
    {
        return $this->ctype;
    }

    public function setCtype(string $ctype): void
    {
        $this->ctype = $ctype;
    }

    /**
     * @return \Xima\XimaTypo3PageSubscription\Domain\Model\Dto\Subscription
     */
    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }

    /**
     * @param \Xima\XimaTypo3PageSubscription\Domain\Model\Dto\Subscription $subscription
     */
    public function setSubscription(Subscription $subscription): void
    {
        $this->subscription = $subscription;
    }

    public function getTstamp(): int
    {
        return $this->tstamp;
    }

    public function setTstamp(int|string $tstamp): void
    {
        $this->tstamp = (int)$tstamp;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function addChild(self $child): void
    {
        $this->children[] = $child;
    }

    public function removeChild(self $child): void
    {
        $key = array_search($child, $this->children, true);
        if ($key !== false) {
            unset($this->children[$key]);
        }
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): void
    {
        $this->link = $link;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): void
    {
        $this->hash = $hash;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getInterpreter(): ?string
    {
        return $this->interpreter;
    }

    public function setInterpreter(?string $interpreter): void
    {
        $this->interpreter = $interpreter;
    }
}
