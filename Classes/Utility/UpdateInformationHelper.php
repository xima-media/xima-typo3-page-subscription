<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3PageSubscription\Configuration;
use Xima\XimaTypo3PageSubscription\Interpreter\ContentInterpreter;
use Xima\XimaTypo3PageSubscription\Interpreter\InterpreterException;
use Xima\XimaTypo3PageSubscription\Interpreter\InterpreterInterface;

class UpdateInformationHelper
{
    public static function buildData(object|array $object): ?array
    {
        $data = [];

        if (is_array($object) && array_key_exists('CType', $object)) {
            $interpreter = GeneralUtility::makeInstance(ContentInterpreter::class);
            $data = $interpreter->buildMetadata($object);
            $data['interpreter'] = ContentInterpreter::class;
        }

        if (is_object($object) && array_key_exists($object::class, $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerInterpreter'])) {
            $interpreter = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerInterpreter'][$object::class]);
            if (!$interpreter instanceof InterpreterInterface) {
                throw new InterpreterException(sprintf('Interpreter "%s" must implement InterpreterInterface', $data['interpreter']), 1729012214);
            }

            $data = $interpreter->buildMetadata($object);
            $data['interpreter'] = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerInterpreter'][$object::class];
        }

        if ($data === []) {
            return null;
        }

        self::verifyInterpreterMetadata($data);
        return $data;
    }

    private static function verifyInterpreterMetadata(array $data): void
    {
        $validationArray = [
            'recuid',
            'title',
            'tstamp',
            'table',
        ];

        $validationResult =  array_values(array_intersect($validationArray, array_keys($data)));

        if ($validationResult !== $validationArray) {
            throw new InterpreterException(sprintf('Interpreter "%s" must return an array with the following keys: recuid, title, tstamp, table', $data['interpreter']), 1729009818);
        }
    }
}
