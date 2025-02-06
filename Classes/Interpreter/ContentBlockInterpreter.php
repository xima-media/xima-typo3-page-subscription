<?php

namespace Xima\XimaTypo3PageSubscription\Interpreter;

use Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem;
use Xima\XimaTypo3PageSubscription\Utility\UrlHelper;

class ContentBlockInterpreter implements InterpreterInterface
{
    /**
     * @throws \Xima\XimaTypo3PageSubscription\Interpreter\InterpreterException
     */
    public function buildMetadata(mixed $object): array
    {
        if (!$object instanceof \TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockData) {
            throw new InterpreterException('Object must be of type ContentBlockData');
        }

        return [
            'recuid' => $object->__get('uid'),
            'title' => $object->__get('header'),
            'tstamp' => $object->__get('updateDate'),
            'ctype' => $object->__get('CType'),
            'table' => 'tt_content',
        ];
    }

    public function generateLink(UpdateItem &$updateItem): void
    {
        $updateItem->setLink(UrlHelper::getAbsoluteUrl($updateItem->getSubscription()->getPid()) . '#c' . $updateItem->getRecuid());
    }
}
