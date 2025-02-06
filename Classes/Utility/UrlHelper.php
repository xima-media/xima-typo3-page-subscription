<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;

class UrlHelper
{
    /**
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     * @throws \TYPO3\CMS\Frontend\Typolink\UnableToLinkException
     */
    public static function getAbsoluteUrl(?int $pageId = null): string
    {
        if ($pageId === null) {
            return '';
        }

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $linkFactory = GeneralUtility::makeInstance(LinkFactory::class);
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $site = $siteFinder->getSiteByPageId($pageId);
        $siteLanguage = $site->getLanguageById(0); // ToDo: get correct language
        self::createTypo3Request($site, $siteLanguage);
        $typolinkConfiguration = [
            'parameter' => $pageId,
        ];
        $typolinkConfiguration['forceAbsoluteUrl'] = true;

        return $linkFactory->create('', $typolinkConfiguration, $contentObjectRenderer)->getUrl();
    }

    public static function getBaseUrl(?int $pid = null): string
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $allSites = $siteFinder->getAllSites();
        $site = $pid ? $siteFinder->getSiteByPageId($pid) : reset($allSites);
        return $site->getBase()->getScheme() . '://' . $site->getBase()->getHost();
    }

    /**
     * Create the TYPO3_REQUEST global to prevent creation from ServerRequestFactory which creates it
     * based on TYPO3_REQUEST_URL, which is not available/correct through CLI
     * @param \TYPO3\CMS\Core\Site\Entity\Site $site
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $siteLanguage
     */
    public static function createTypo3Request(?Site $site = null, ?SiteLanguage $siteLanguage = null): ServerRequestInterface
    {
        if (!$site instanceof Site || !$siteLanguage instanceof SiteLanguage) {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $array = $siteFinder->getAllSites();
            $site = reset($array);
            $siteLanguage = self::getDefaultLanguage();
        }

        $request = new ServerRequest(new Uri((string)$siteLanguage->getBase()));
        $request = $request->withAttribute('site', $site);
        $request = $request->withAttribute('language', $siteLanguage);
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withQueryParams(['id' => $site->getRootPageId()]);

        $GLOBALS['TYPO3_REQUEST'] = $request;
        return $request;
    }

    public static function getDefaultLanguage(): SiteLanguage
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $array = $siteFinder->getAllSites();
        $site = reset($array);
        return $site->getLanguageById(0);
    }
}
