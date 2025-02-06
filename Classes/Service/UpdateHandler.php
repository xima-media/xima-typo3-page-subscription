<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Service;

use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3PageSubscription\Domain\Model\Dto\Subscription;
use Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem;
use Xima\XimaTypo3PageSubscription\Event\UpdateItemModifyEvent;
use Xima\XimaTypo3PageSubscription\Utility\UrlHelper;

readonly class UpdateHandler
{
    public function __construct(protected EventDispatcher $eventDispatcher) {}

    public function handle(Subscription $subscription, UpdateItem $updateItem): ?UpdateItem
    {
        $handleResult = $this->handleUpdate($subscription, $updateItem);
        if ($handleResult === false) {
            return null;
        }

        $this->eventDispatcher->dispatch(new UpdateItemModifyEvent($updateItem));
        return $updateItem;
    }

    /**
     * This function verifies the updates for a subscription.
     * Therefor it checks the hashes of the subscription and the updateItem (and their children).
     * If the hashes are not different, the updateItem will be removed, so that only modified changes will be returned in the end.
     * Also it handles the subscriptions with an elementId, so that only the updates for the given elementId will be returned.
     *
     * @param \Xima\XimaTypo3PageSubscription\Domain\Model\Dto\Subscription $subscription
     * @param \Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem $updateItem
     * @param \Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem|null $parentItem
     * @return bool
     */
    private function handleUpdate(Subscription $subscription, UpdateItem &$updateItem, ?UpdateItem $parentItem = null): bool
    {
        $updateItem->setSubscription($subscription);
        $return = false;
        $checkResult = $this->checkHashes($subscription, $updateItem, $parentItem);
        $this->updateCTypeLabel($updateItem);

        if ($updateItem->getInterpreter()) {
            $interpreter = GeneralUtility::makeInstance($updateItem->getInterpreter());
            $interpreter->generateLink($updateItem);

            if ($updateItem->getLink() !== null && empty(parse_url((string)$updateItem->getLink())['scheme'])) {
                $updateItem->setLink(UrlHelper::getBaseUrl($subscription->getPid()) . $updateItem->getLink());
            }
        }

        if ($updateItem->getChildren() !== []) {
            $return = true;
        }

        foreach ($updateItem->getChildren() as $child) {
            $handleResult = $this->handleUpdate($subscription, $child, $updateItem);
            if ($handleResult === false) {
                $updateItem->removeChild($child);
            }
        }

        if ($updateItem->getChildren() === [] && !$checkResult) {
            return false;
        }

        if ($checkResult) {
            return true;
        }

        return $return;
    }

    private function checkHashes(Subscription $subscription, UpdateItem &$updateItem, ?UpdateItem $parentItem = null): bool
    {
        if ($subscription->getHashes() === []) {
            $updateItem->setType(RecordHistoryStore::ACTION_ADD);
            return true;
        }

        $hashesArray = [];
        if ($parentItem instanceof UpdateItem) {
            if (array_key_exists($parentItem->getIdentifier(), $subscription->getHashes())) {
                $hashesArray = $subscription->getHashes()[$parentItem->getIdentifier()]['children'];
            } elseif ($updateItem->getIdentifier() === $subscription->getElementId()) {
                $hashesArray = $subscription->getHashes();
            }
        } else {
            $hashesArray = $subscription->getHashes();
        }

        /*
         * If the updateItem is not in the previous hashesArray, it is a new item.
         */
        if (!in_array($updateItem->getIdentifier(), array_keys($hashesArray))) {
            $updateItem->setType(RecordHistoryStore::ACTION_ADD);
            return true;
        }

        /*
         * If the hash of the updateItem is different from the hash in the hashesArray, it is a modified item.
         */
        if ($updateItem->getHash() !== $hashesArray[$updateItem->getIdentifier()]['hash']) {
            $updateItem->setType(RecordHistoryStore::ACTION_MODIFY);
            return true;
        }

        return false;
    }

    private function updateCTypeLabel(UpdateItem &$updateItem): void
    {
        if ($updateItem->getTable() === 'tt_content') {
            foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $item) {
                if ($item['value'] === $updateItem->getCtype()) {
                    $updateItem->setLabel($this->getLanguageService()->sL($item['label']));
                    break;
                }
            }
        } else {
            $updateItem->setLabel($updateItem->getTitle());
        }
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
