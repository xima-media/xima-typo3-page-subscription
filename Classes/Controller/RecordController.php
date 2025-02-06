<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Xima\XimaTypo3PageSubscription\Configuration;
use Xima\XimaTypo3PageSubscription\Domain\Repository\SubscriptionRepository;
use Xima\XimaTypo3PageSubscription\Enumeration\Type;

#[AsController]
class RecordController extends ActionController
{
    public function __construct(protected SubscriptionRepository $subscriptionRepository) {}

    public function subscriptionsAction(): ResponseInterface
    {
        if ($this->request->hasArgument('unsubscribe')) {
            $uid = (int)$this->request->getArgument('unsubscribe');
            $this->subscriptionRepository->remove($uid, Type::SUBSCRIPTION->value);
            $this->addFlashMessage(
                $GLOBALS['LANG']->sL('LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang.xlf:plugin.subscriptions.message.removed')
            );
        }

        $data = $this->request->getAttribute('currentContentObject')->data;
        $ignoreElementIds = $data['tx_ximatypo3pagesubscription_ignore_element_ids'];
        $elementFilter = $data['tx_ximatypo3pagesubscription_filter_element_ids'];

        if ($elementFilter) {
            $result = $this->getElements($elementFilter);
        } elseif ($ignoreElementIds) {
            $result = $this->getRecords(Type::SUBSCRIPTION, true);
        } else {
            $result = $this->getRecords(Type::SUBSCRIPTION);
        }

        $this->view->assignMultiple([
            'subscriptions' => $result,
            'data' => $data,
        ]);

        return $this->htmlResponse();
    }

    public function favoritesAction(): ResponseInterface
    {
        if ($this->request->hasArgument('unsave')) {
            $uid = (int)$this->request->getArgument('unsave');
            $this->subscriptionRepository->remove($uid, Type::FAVORITE->value);
            $this->addFlashMessage(
                $GLOBALS['LANG']->sL('LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang.xlf:plugin.favorites.message.removed')
            );
        }

        $this->view->assignMultiple([
            'favorites' => $this->getRecords(Type::FAVORITE),
        ]);

        return $this->htmlResponse();
    }

    private function getRecords(Type $type, bool $ignoreElementIds = false): mixed
    {
        $feUser = $GLOBALS['TSFE']->fe_user->user;
        if (!$feUser) {
            return [];
        }

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_ximatypo3pagesubscription_domain_model_' . $type->value);
        $qb->select('s.pid', 'p.title')
            ->from('tx_ximatypo3pagesubscription_domain_model_' . $type->value, 's')
            ->join(
                's',
                'pages',
                'p',
                $qb->expr()->eq('p.uid', 's.pid')
            )
            ->where(
                $qb->expr()->eq(
                    's.fe_user',
                    $qb->createNamedParameter($feUser['uid'])
                )
            );

        if ($type === Type::SUBSCRIPTION) {
            $qb->addSelect('s.element_id');
        }

        if ($ignoreElementIds) {
            $qb->andWhere(
                $qb->expr()->eq(
                    's.element_id',
                    $qb->createNamedParameter('')
                )
            );
        }

        return $qb->executeQuery()->fetchAllAssociative() ?: [];
    }

    private function getElements(?string $elementFilter = null): mixed
    {
        $feUser = $GLOBALS['TSFE']->fe_user->user;
        if (!$feUser) {
            return [];
        }

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_ximatypo3pagesubscription_domain_model_subscription');
        $qb->select('s.pid', 'p.title', 's.element_id')
            ->from('tx_ximatypo3pagesubscription_domain_model_subscription', 's')
            ->join(
                's',
                'pages',
                'p',
                $qb->expr()->eq('p.uid', 's.pid')
            )
            ->where(
                $qb->expr()->eq(
                    's.fe_user',
                    $qb->createNamedParameter($feUser['uid'])
                ),
                $qb->expr()->neq(
                    's.element_id',
                    $qb->createNamedParameter('')
                )
            );
        if ($elementFilter) {
            $qb->andWhere(
                $qb->expr()->like(
                    's.element_id',
                    $qb->createNamedParameter('%' . $elementFilter . '%')
                )
            );
        }

        return $qb->executeQuery()->fetchAllAssociative() ?: [];
    }
}
