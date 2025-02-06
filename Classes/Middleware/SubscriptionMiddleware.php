<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Xima\XimaTypo3PageSubscription\Configuration;
use Xima\XimaTypo3PageSubscription\Domain\Repository\SubscriptionRepository;
use Xima\XimaTypo3PageSubscription\Enumeration\Type;
use Xima\XimaTypo3PageSubscription\Service\Crawler;
use Xima\XimaTypo3PageSubscription\Utility\SubscriptionUtility;

class SubscriptionMiddleware implements MiddlewareInterface
{
    public function __construct(protected readonly SubscriptionRepository $subscriptionRepository, protected readonly Crawler $crawler) {}

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $params = $request->getQueryParams();
        if (
            !($response instanceof NullResponse)
            && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
            && isset($params['type'])
            && $params['type'] === Configuration::TYPE_CHECK
        ) {
            $postParams = $request->getParsedBody();
            if (!is_array($postParams) || !isset($postParams['action']) || !isset($postParams['type'])) {
                return new JsonResponse(['error' => 'No action provided'], 400);
            }

            if (!in_array($postParams['type'], array_column(Type::cases(), 'value'))) {
                return new JsonResponse(['error' => 'Invalid type provided'], 400);
            }

            /* @phpstan-ignore-next-line */
            $pid = isset($postParams['pid']) ? (int)$postParams['pid'] : $request->getAttribute('routing')->getPageId();

            // get current fe user
            $feUser = $GLOBALS['TSFE']->fe_user->user;
            if (!$feUser) {
                return new JsonResponse(['error' => 'No frontend user found'], 403);
            }

            $elementId = false;
            if (isset($postParams['elementId'])) {
                $elementId = $postParams['elementId'];
            }

            if ($postParams['type'] === Type::FAVORITE->value) {
                $elementId = null;
            }

            $result = match ($postParams['action']) {
                'check' => (bool)$this->subscriptionRepository->check($pid, $feUser['uid'], $postParams['type'], $elementId),
                'toggle' => $this->toggle($pid, $feUser['uid'], $postParams['type'], $elementId),
                default => false,
            };

            return new JsonResponse([
                'result' =>
                    match ($result) {
                        'add' => true,
                        'remove' => false,
                        default => (bool)$result,
                    },
                'pid' => $pid,
                'feUid' => $feUser['uid'],
                'action' => $postParams['action'],
                'type' => $postParams['type'],
                'info' => $result,
            ]);
        }

        return $response;
    }

    private function toggle(int $pid, int $feUid, string $type, string|bool|null $elementId = null): string|bool
    {
        $alreadySubscribed = $this->subscriptionRepository->check($pid, $feUid, $type, $elementId);
        if ($alreadySubscribed) {
            return $this->subscriptionRepository->remove($alreadySubscribed, $type) ? 'remove' : false;
        }

        if ($type === Type::SUBSCRIPTION->value) {
            return $this->addNewSubscription($pid, $feUid, $type, $elementId) ? 'add' : false;
        }

        return $this->subscriptionRepository->add($pid, $feUid, $type, $elementId);
    }

    private function addNewSubscription(int $pid, int $feUid, string $type, string|bool|null $elementId): bool
    {
        $elements = $this->crawler->checkPageForElements($pid);
        if ($elementId !== null && is_string($elementId) && $elementId !== '') {
            $elements = $this->crawler->filterByElementId($elements, $elementId);
        }

        $hashes = SubscriptionUtility::getHashArray($elements);

        return $this->subscriptionRepository->add($pid, $feUid, $type, $hashes, $elementId);
    }
}
