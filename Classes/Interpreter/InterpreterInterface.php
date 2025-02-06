<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Interpreter;

use Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem;

interface InterpreterInterface
{
    public function buildMetadata(mixed $object): array;

    public function generateLink(UpdateItem &$updateItem): void;
}
