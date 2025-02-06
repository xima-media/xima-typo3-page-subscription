<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Utility;

use Xima\XimaTypo3PageSubscription\Domain\Model\Dto\Subscription;

class SubscriptionUtility
{
    public static function compareHashes(Subscription $subscription, array $updates): bool
    {
        $subscriptionHashes = json_encode($subscription->getHashes());

        $updateHashes = self::getHashArray($updates);
        return $subscriptionHashes === $updateHashes;
    }

    public static function getHashArray(array $updates, bool $json = true): array|string|bool
    {
        $updateHashes = [];
        foreach ($updates as $update) {
            $updateHashes[$update->getIdentifier()] = ['hash' => $update->getHash(), 'children' => []];

            if ($update->getChildren() !== []) {
                $updateHashes[$update->getIdentifier()]['children'] =  self::getHashArray($update->getChildren(), false);
            }
        }

        if ($json) {
            $updateHashes = json_encode($updateHashes);
        }

        return $updateHashes;
    }
}
