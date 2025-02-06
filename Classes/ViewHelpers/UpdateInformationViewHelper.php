<?php

namespace Xima\XimaTypo3PageSubscription\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use Xima\XimaTypo3PageSubscription\Service\HashGenerator;
use Xima\XimaTypo3PageSubscription\Utility\UpdateInformationHelper;

class UpdateInformationViewHelper extends AbstractTagBasedViewHelper
{
    protected $tagName = 'div';

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
        $this->registerArgument('title', 'string', 'Optional title');
        $this->registerArgument('link', 'string', 'Optional link');

    }

    public function render(): string
    {
        if (!$GLOBALS['TYPO3_REQUEST']->hasHeader('X-PageSubscription-Crawler')) {
            return $this->renderChildren();
        }

        if (!$this->renderChildren()) {
            return '';
        }

        $object = $this->arguments['object'];

        $data = UpdateInformationHelper::buildData($object);
        if ($data === null) {
            return $this->renderChildren();
        }

        $title = $this->arguments['title'];
        if ($title) {
            $data['title'] = $title;
        }

        $link = $this->arguments['link'];
        if ($link) {
            $data['link'] = $link;
        }

        foreach ($data as $key => $value) {
            $this->tag->addAttribute(
                sprintf('data-%s', $key),
                $value,
            );
        }

        $hashArray = $this->hashGenerator->generate($this->renderChildren(), $data);
        foreach ($hashArray as $key => $value) {
            $this->tag->addAttribute(
                sprintf('data-%s', $key),
                $value,
            );
        }

        $this->tag->setContent($this->renderChildren());
        $this->tag->addAttribute(
            'class',
            'page-subscription--update-information',
        );
        return $this->tag->render();
    }
}
