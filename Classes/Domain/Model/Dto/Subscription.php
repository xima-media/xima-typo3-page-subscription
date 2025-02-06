<?php

namespace Xima\XimaTypo3PageSubscription\Domain\Model\Dto;

final class Subscription
{
    private int $uid;

    private int $pid;

    private string $title;

    private int $lastChecked;

    private string $email;

    private int $feUser;

    private string $link;

    private array $hashes = [];

    private string $elementId;

    public static function create(array $row): static
    {
        $item = new Subscription();
        $item->uid = (int)$row['uid'];
        $item->pid = (int)$row['pid'];
        $item->title = (string)$row['title'];
        $item->lastChecked = (int)$row['last_checked'];
        $item->email = $row['email'];
        $item->feUser = $row['fe_user'];
        $item->setHashes($row['hashes']);
        $item->elementId = $row['element_id'];

        return $item;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getLastChecked(): int
    {
        return $this->lastChecked;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getFeUser(): int
    {
        return $this->feUser;
    }

    public function setFeUser(int $feUser): void
    {
        $this->feUser = $feUser;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getHashes(): array
    {
        return $this->hashes;
    }

    public function setHashes(array|string|null $hashes): void
    {
        if (is_string($hashes)) {
            $hashes = json_decode($hashes, true);
        }

        if (is_null($hashes)) {
            $hashes = [];
        }

        $this->hashes = $hashes;
    }

    public function addHash(string $hash): void
    {
        $this->hashes[] = $hash;
    }

    public function getElementId(): string
    {
        return $this->elementId;
    }

    public function setElementId(string $elementId): void
    {
        $this->elementId = $elementId;
    }
}
