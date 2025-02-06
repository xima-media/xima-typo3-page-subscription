<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Event;

use Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem;

class UpdateItemModifyEvent
{
    final public const NAME = 'xima_typo3_page_subscription.update_item.modify';

    public function __construct(
        protected UpdateItem $updateItem
    ) {}

    /**
     * @return \Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem
     */
    public function getUpdateItem(): UpdateItem
    {
        return $this->updateItem;
    }

    /**
     * @param \Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem $updateItem
     */
    public function setUpdateItem(UpdateItem $updateItem): void
    {
        $this->updateItem = $updateItem;
    }
}
