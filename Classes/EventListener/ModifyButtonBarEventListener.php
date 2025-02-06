<?php

namespace Xima\XimaTypo3PageSubscription\EventListener;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3PageSubscription\Configuration;
use Xima\XimaTypo3PageSubscription\Domain\Repository\SubscriptionRepository;

final readonly class ModifyButtonBarEventListener
{
    public function __construct(private IconFactory $iconFactory, private SubscriptionRepository $subscriptionRepository) {}

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        $extensionConfiguration = (GeneralUtility::makeInstance(ExtensionConfiguration::class))->get(Configuration::EXT_KEY);
        if ((bool)$extensionConfiguration['beSubscriptionCount'] === false) {
            return;
        }

        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        $pageId = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);

        $count = $this->subscriptionRepository->countByPid($pageId);

        if ($count <= 0) {
            return;
        }

        $buttons = $event->getButtons();
        $buttons['right'] ??= [];

        $button = $event->getButtonBar()->makeFullyRenderedButton();
        $button->setHtmlSource(
            '<span title="' . $GLOBALS['LANG']->sL('LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:button.subscriptions.help') . '">' .
            $this->iconFactory->getIcon('actions-eye', Icon::SIZE_SMALL)->render() . ' ' . $count .
            '</span>'
        );

        $buttons['right'][] = [$button];
        $event->setButtons($buttons);
    }
}
