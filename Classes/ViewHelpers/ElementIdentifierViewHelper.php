<?php

namespace Xima\XimaTypo3PageSubscription\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use Xima\XimaTypo3PageSubscription\Service\HashGenerator;
use Xima\XimaTypo3PageSubscription\Utility\UpdateInformationHelper;

class ElementIdentifierViewHelper extends AbstractTagBasedViewHelper
{
    public function __construct(protected HashGenerator $hashGenerator)
    {
        parent::__construct();
    }

    /**
     * Register arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('object', 'mixed', 'Object for update subscription', true);
    }

    public function render(): string
    {
        $object = $this->arguments['object'];
        $data = UpdateInformationHelper::buildData($object);
        return $this->hashGenerator->generateIdentifier([
            'table' => $data['table'],
            'ctype' => array_key_exists('ctype', $data) ? $data['ctype'] : '',
            'recuid' => $data['recuid'],
        ]);
    }
}
