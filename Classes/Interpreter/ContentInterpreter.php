<?php

namespace Xima\XimaTypo3PageSubscription\Interpreter;

use Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem;
use Xima\XimaTypo3PageSubscription\Utility\UrlHelper;

class ContentInterpreter implements InterpreterInterface
{
    /**
     * @throws \Xima\XimaTypo3PageSubscription\Interpreter\InterpreterException
     */
    public function buildMetadata(mixed $object): array
    {
        if (!is_array($object)) {
            throw new InterpreterException('Object must be of type Array');
        }

        $data = [
            'table' => 'tt_content',
        ];

        if (array_key_exists('uid', $object)) {
            $data['recuid'] = $object['uid'];
        }

        if (array_key_exists('tstamp', $object)) {
            $data['tstamp'] = $object['tstamp'];
        }

        if (array_key_exists('CType', $object)) {
            $data['ctype'] = $object['CType'];
        }

        if (array_key_exists('header', $object)) {
            $data['title'] = $object['header'];
        }

        return $data;
    }

    public function generateLink(UpdateItem &$updateItem): void
    {
        $updateItem->setLink(UrlHelper::getAbsoluteUrl($updateItem->getSubscription()->getPid()) . '#c' . $updateItem->getRecuid());
    }
}
