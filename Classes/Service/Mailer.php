<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Service;

use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Xima\XimaTypo3PageSubscription\Configuration;
use Xima\XimaTypo3PageSubscription\Utility\UrlHelper;

class Mailer
{
    public function notify(array $subscriptionUpdates): bool
    {
        $result = true;
        foreach ($subscriptionUpdates as $subscriptionUpdate) {
            $subscriptionUpdates = $this->mergeUpdates($subscriptionUpdate['subscriptions']);
            $result &= $this->send($subscriptionUpdate['feUser'], $subscriptionUpdates);
        }

        return (bool)$result;
    }

    /**
     * Merges subscription updates by page ID.
     *
     * @param array $subscriptions Array of subscription updates.
     * @return array Merged subscription updates.
     */
    private function mergeUpdates(array $subscriptions): array
    {
        $merged = [];
        foreach ($subscriptions as $item) {
            $subscription = $item['subscription'];
            $page = [
                'pid' => $subscription->getPid(),
                'title' => $subscription->getTitle(),
                'link' => $subscription->getLink(),
            ];
            if (!array_key_exists($subscription->getPid(), $merged)) {
                $merged[$subscription->getPid()] = [
                    'page' => $page,
                    'updates' => $item['updates'],
                ];
            } else {
                $mergedUpdates = array_merge($merged[$subscription->getPid()]['updates'], $item['updates']);
                $merged[$subscription->getPid()]['updates'] = array_unique($mergedUpdates);
            }
        }

        return $merged;
    }

    private function send(array $feUser, array $subscriptions): bool
    {
        UrlHelper::createTypo3Request();
        $backendConfiguration = GeneralUtility::makeInstance(BackendConfigurationManager::class);
        $typoScriptArray = $backendConfiguration->getTypoScriptSetup()['module.']['tx_ximatypo3pagesubscription.'];

        $extensionConfiguration = (GeneralUtility::makeInstance(ExtensionConfiguration::class))->get(Configuration::EXT_KEY);

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        /** @var StandaloneView $view */
        $view->setTemplatePathAndFilename($extensionConfiguration['emailTemplate']);
        $view->setRequest(UrlHelper::createTypo3Request());

        $view->setTemplateRootPaths($typoScriptArray['view.']['templateRootPaths.']);
        $view->setPartialRootPaths($typoScriptArray['view.']['partialRootPaths.']);
        $view->setLayoutRootPaths($typoScriptArray['view.']['layoutRootPaths.']);

        $view->assignMultiple(
            [
                'feUser' => $feUser,
                'subscriptions' => $subscriptions,
                'countSubscriptions' => count($subscriptions),
                'countUpdates' => array_reduce($subscriptions, function ($carry, $subscription) {
                    $updateCount = count($subscription['updates']);
                    $childUpdateCount = array_reduce($subscription['updates'], fn($carry, $update) => $carry + count($update->getChildren()), 0);
                    return $carry + $updateCount + $childUpdateCount;
                }, 0),
                'baseUrl' => UrlHelper::getBaseUrl(),
                'logo' => $extensionConfiguration['emailLogo'] ?? null,
                'subscriptionsOverview' => array_key_exists('emailSubscriptionOverviewPid', $extensionConfiguration) && $extensionConfiguration['emailSubscriptionOverviewPid'] !== '' ? UrlHelper::getAbsoluteUrl((int)$extensionConfiguration['emailSubscriptionOverviewPid']) : '',
            ]
        );

        if ($feUser['email'] === '') {
            return false;
        }

        $email = new MailMessage();
        $email
            ->to(new Address($feUser['email'], $feUser['username']))
            ->subject($GLOBALS['LANG']->sL('LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang.xlf:email.subject'))
            ->html(
                $view->render()
            );
        GeneralUtility::makeInstance(MailerInterface::class)->send($email);

        return true;
    }
}
