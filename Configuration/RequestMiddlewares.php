<?php

return [
    'frontend' => [
        'xima/page_subscription_subscription' => [
            'target' => \Xima\XimaTypo3PageSubscription\Middleware\SubscriptionMiddleware::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
        'xima/page_subscription_crawler' => [
            'target' => \Xima\XimaTypo3PageSubscription\Middleware\CrawlerMiddleware::class,
            'before' => [
                'typo3/cms-core/verify-host-header',
            ],
        ],
    ],
];
