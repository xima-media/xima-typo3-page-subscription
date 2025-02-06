<?php

namespace Xima\XimaTypo3PageSubscription\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3PageSubscription\Domain\Model\Dto\Subscription;
use Xima\XimaTypo3PageSubscription\Enumeration\Type;

class SubscriptionRepository
{
    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function check(int $pid, int $feUid, string $type, string|bool|null $elementId = null): ?int
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_ximatypo3pagesubscription_domain_model_' . $type);
        $qb->select('uid')->from('tx_ximatypo3pagesubscription_domain_model_' . $type)->where(
            $qb->expr()->eq('pid', $qb->createNamedParameter($pid)),
            $qb->expr()->eq('fe_user', $qb->createNamedParameter($feUid))
        );
        if (is_string($elementId)) {
            $qb->andWhere(
                $qb->expr()->eq('element_id', $qb->createNamedParameter($elementId))
            );
        }

        if ((((is_bool($elementId) && $elementId === false) || $elementId === null) && $type === Type::SUBSCRIPTION->value)) {
            $qb->andWhere(
                $qb->expr()->eq('element_id', $qb->createNamedParameter(''))
            );
        }

        $subscription = $qb->executeQuery()->fetchAssociative();

        return $subscription ? (int)$subscription['uid'] : null;
    }

    public function add(int $pid, int $feUid, string $type, ?string $hash = '', string|bool|null $elementId = null): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_ximatypo3pagesubscription_domain_model_' . $type);
        $data = [
            'pid' => $pid,
            'fe_user' => $feUid,
            'crdate' => time(),
            'tstamp' => time(),
        ];

        if ($type === Type::SUBSCRIPTION->value) {
            $data['last_checked'] = time();
            $data['hashes'] = $hash;
        }

        if (is_string($elementId) && $elementId !== '') {
            $data['element_id'] = $elementId;
        }

        $connection->insert(
            'tx_ximatypo3pagesubscription_domain_model_' . $type,
            $data
        );
        return $connection->lastInsertId() !== false;
    }

    public function remove(int $uid, string $type): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_ximatypo3pagesubscription_domain_model_' . $type);
        $connection->delete(
            'tx_ximatypo3pagesubscription_domain_model_' . $type,
            [
                'uid' => $uid,
            ]
        );

        return $connection->lastInsertId() !== false;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function find(int $uid): Subscription|bool
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_ximatypo3pagesubscription_domain_model_subscription');
        $result =  $qb->select('s.uid', 's.pid', 's.fe_user', 's.last_checked', 's.hashes', 's.element_id', 'f.username', 'f.email', 'p.title')
            ->from('tx_ximatypo3pagesubscription_domain_model_subscription', 's')
            ->join('s', 'fe_users', 'f', 's.fe_user = f.uid')
            ->join('s', 'pages', 'p', 's.pid = p.uid')
            ->where(
                $qb->expr()->eq('s.uid', $qb->createNamedParameter($uid))
            )
            ->executeQuery()->fetchAssociative();

        return $result ? Subscription::create($result) : false;
    }

    public function findAll(): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_ximatypo3pagesubscription_domain_model_subscription');
        $result =  $qb->select('s.uid', 's.pid', 's.fe_user', 's.last_checked', 's.hashes', 's.element_id', 'f.username', 'f.email', 'p.title')
            ->from('tx_ximatypo3pagesubscription_domain_model_subscription', 's')
            ->join('s', 'fe_users', 'f', 's.fe_user = f.uid')
            ->join('s', 'pages', 'p', 's.pid = p.uid')
            ->executeQuery()->fetchAllAssociative();

        $return = [];
        foreach ($result as $row) {
            $return[] = Subscription::create($row);
        }

        return $return;
    }

    public function countByPid(int $pid): int
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_ximatypo3pagesubscription_domain_model_subscription');
        return (int)$connection->count('uid', 'tx_ximatypo3pagesubscription_domain_model_subscription', ['pid' => $pid]);
    }

    public function updateLastChecked(int $uid, string $hashes = null): void
    {
        $data = ['last_checked' => time()];
        if ($hashes !== null) {
            $data['hashes'] = $hashes;
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_ximatypo3pagesubscription_domain_model_subscription');
        $connection->update(
            'tx_ximatypo3pagesubscription_domain_model_subscription',
            $data,
            ['uid' => $uid]
        );
    }

    public function clearAllHashes(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_ximatypo3pagesubscription_domain_model_subscription');
        $queryBuilder
            ->update('tx_ximatypo3pagesubscription_domain_model_subscription')
            ->set('hashes', '')
            ->executeStatement();
    }
}
