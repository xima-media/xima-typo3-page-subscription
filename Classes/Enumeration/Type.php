<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Enumeration;

enum Type: string
{
    case SUBSCRIPTION = 'subscription';
    case FAVORITE = 'favorite';
}
