<?php

namespace Xima\XimaTypo3PageSubscription\Interpreter;

use TYPO3\CMS\Core\Resource\File;
use Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem;

class FileInterpreter implements InterpreterInterface
{
    /**
     * @throws \Xima\XimaTypo3PageSubscription\Interpreter\InterpreterException
     */
    public function buildMetadata(mixed $object): array
    {
        if (!$object instanceof File) {
            throw new InterpreterException('Object must be of type File');
        }

        return [
            'recuid' => $object->getUid(),
            'title' => $object->getName(),
            'tstamp' => $object->getModificationTime(),
            'table' => 'sys_file',
            'link' => $object->getPublicUrl(),
        ];
    }

    public function generateLink(UpdateItem &$updateItem): void {}
}
