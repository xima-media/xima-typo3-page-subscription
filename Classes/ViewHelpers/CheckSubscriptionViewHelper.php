<?php

namespace Xima\XimaTypo3PageSubscription\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use Xima\XimaTypo3PageSubscription\Domain\Repository\SubscriptionRepository;
use Xima\XimaTypo3PageSubscription\Enumeration\Type;

class CheckSubscriptionViewHelper extends AbstractTagBasedViewHelper
{
    public function __construct(protected SubscriptionRepository $subscriptionRepository)
    {
        parent::__construct();
    }

    /**
     * Register arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('pid', 'integer', 'Page ID');
        $this->registerArgument('type', 'string', 'Optional type', false, Type::SUBSCRIPTION->value);
        $this->registerArgument('elementId', 'string', 'Optional element id');
    }

    public function render(): bool
    {
        $pid = $this->arguments['pid'] ?? $this->getRequest()->getAttribute('routing')->getPageId();
        $elementId = $this->arguments['elementId'];
        $feUser = $GLOBALS['TSFE']->fe_user->user;
        if (!$feUser) {
            return false;
        }

        return (bool)$this->subscriptionRepository->check($pid, $feUser['uid'], $this->arguments['type'], $elementId);
    }

    /**
     * @see https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/RequestLifeCycle/Typo3Request.html
     * @return \Psr\Http\Message\ServerRequestInterface|null
     */
    private function getRequest(): ServerRequestInterface|null
    {
        if ((new (Typo3Version::class))->getMajorVersion() <= 12) {
            // Todo: remove on dropping TYPO3 v12 support
            return $this->renderingContext->getRequest();
        }

        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            return $this->renderingContext->getAttribute(ServerRequestInterface::class);
        }

        return null;
    }
}
