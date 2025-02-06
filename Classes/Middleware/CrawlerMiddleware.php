<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CrawlerMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getHeaderLine('X-PageSubscription-Crawler') !== '' && $request->getHeaderLine('X-PageSubscription-Crawler') !== '0') {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter'] = false;
        }

        return $handler->handle($request);
    }
}
