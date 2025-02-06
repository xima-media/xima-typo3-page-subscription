<?php

namespace Xima\XimaTypo3PageSubscription\Interpreter;

use Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem;

class NewsInterpreter implements InterpreterInterface
{
    /**
     * @throws \Xima\XimaTypo3PageSubscription\Interpreter\InterpreterException
     */
    public function buildMetadata(mixed $object): array
    {
        if (!$object instanceof \GeorgRinger\News\Domain\Model\NewsDefault && !$object instanceof \GeorgRinger\News\Domain\Model\NewsInternal && !$object instanceof \GeorgRinger\News\Domain\Model\NewsExternal) {
            throw new InterpreterException('Object must be of type News');
        }

        return [
            'recuid' => $object->getUid(),
            'title' => $object->getTitle(),
            'tstamp' => $object->getTstamp()->getTimestamp(),
            'table' => 'tx_news_domain_model_news',
        ];
    }

    public function generateLink(UpdateItem &$updateItem): void {}
}
