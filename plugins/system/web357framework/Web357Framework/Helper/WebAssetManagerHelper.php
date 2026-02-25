<?php
/* ======================================================
 # Web357 Framework for Joomla! - v2.0.0 (free version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2024 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html
 # Website: https://www.web357.com
 # Support: support@web357.com
 # Last modified: Monday 27 October 2025, 03:04:38 PM
 ========================================================= */

namespace Web357Framework\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die();

/**
 *
 * A helper class for managing web assets (CSS, JS) in Joomla.
 * This class provides a simplified interface for registering assets
 * with Joomla's WebAssetManager.
 *
 */
class WebAssetManagerHelper
{
    /** @var string */
    protected static $baseMediaPath;

    /** @var string */
    protected static $joomlaMajorClassname;

    public function __construct()
    {
        if (!static::$baseMediaPath) {
            static::$baseMediaPath = (version_compare(JVERSION, '4.3', '>=') ? '' : Uri::root(true) . '/') . 'media';
        }
        if (!static::$joomlaMajorClassname) {
            $versionParts = explode('.', JVERSION);
            static::$joomlaMajorClassname = 'web357-j' . $versionParts[0] . 'x';
        }
    }

    /**
     * Gets the full media path by appending the provided URL to the base media path.
     *
     * @param string $url The URL to append to the base media path.
     * @return string The full media path.
     */
    public function getBaseMediaPath(string $url): string
    {
        return static::$baseMediaPath . $url;
    }

    /**
     * Returns Joomla Class
     * @param string $additionalClasses
     * @return string
     */
    public function getJoomlaVersionClass(string $additionalClasses = ''): string
    {
        return rtrim(static::$joomlaMajorClassname . ' ' . $additionalClasses, ' ');
    }

    /**
     * Registers and uses a script asset.
     *
     * @param string $assetName The name of the asset.
     * @param string $uri The URI for the asset.
     * @param array $options Additional options for the asset.
     * @param array $attributes Attributes for the asset.
     * @param array $dependencies
     * @return WebAssetManagerHelper
     */
    public function registerScript(string $assetName, string $uri = '', array $options = [], array $attributes = [], array $dependencies = [])
    {
        if (version_compare(JVERSION, '4.3', '>=')) {
            /** @var \Joomla\CMS\WebAsset\WebAssetManager $assetManager */
            $assetManager = Factory::getApplication()->getDocument()->getWebAssetManager();
            $assetManager->registerAndUseScript($assetName, $uri, $options, $attributes, $dependencies);
        } else {
            /* @TODO (Joomla3 compatibility): Dependency injection */
            /** @var \Joomla\CMS\Document\HtmlDocument $htmlDocument */
            $htmlDocument = Factory::getApplication()->getDocument();
            $htmlDocument->addScript($uri, $options, $attributes);
        }
        return $this;
    }

    /**
     * Registers and uses a style asset.
     *
     * @param string $assetName The name of the asset.
     * @param string $uri The URI for the asset.
     * @param array $options Additional options for the asset.
     * @param array $attributes Attributes for the asset.
     * @param array $dependencies
     * @return WebAssetManagerHelper
     */
    public function registerStyle(string $assetName, string $uri = '', array $options = [], array $attributes = [], array $dependencies = [])
    {
        if (version_compare(JVERSION, '4.3', '>=')) {
            /** @var \Joomla\CMS\WebAsset\WebAssetManager $assetManager */
            $assetManager = Factory::getApplication()->getDocument()->getWebAssetManager();
            $assetManager->registerAndUseStyle($assetName, $uri, $options, $attributes);
        } else {
            /* @TODO (Joomla3 compatibility): Dependency injection */
            /** @var \Joomla\CMS\Document\HtmlDocument $htmlDocument */
            $htmlDocument = Factory::getApplication()->getDocument();
            $htmlDocument->addStyleSheet($uri, $options, $attributes);
        }
        return $this;
    }

    /**
     * Registers options to JS (available from Joomla.getOptions('web35.select2'))
     *
     * @param string $key Name in Storage
     * @param mixed $options Scrip options as array or string
     * @param bool $merge Whether merge with existing (true) or replace (false)
     * @return WebAssetManagerHelper
     */
    public function addScriptOptions($key, $options, $merge = true)
    {
        /** @var \Joomla\CMS\Document\HtmlDocument $htmlDocument */
        $htmlDocument = version_compare(JVERSION, '4.3', '>=') ? Factory::getApplication()->getDocument() : Factory::getDocument();
        $htmlDocument->addScriptOptions($key, $options, $merge);
        return $this;
    }

    /**
     * Registers and uses an inline script.
     *
     * @param string $content The content of the script.
     * @param array $options Additional options for the script.
     * @param array $attributes Attributes for the script.
     * @param array $dependencies
     * @return WebAssetManagerHelper
     */
    public function addInlineScript($content, array $options = [], array $attributes = [], array $dependencies = [])
    {
        if (version_compare(JVERSION, '4.3', '>=')) {
            /** @var \Joomla\CMS\WebAsset\WebAssetManager $assetManager */
            $assetManager = Factory::getApplication()->getDocument()->getWebAssetManager();
            $assetManager->addInlineScript($content, $options, $attributes, $dependencies);
        } else {
            /* @TODO (Joomla3 compatibility): Dependency injection */
            /** @var \Joomla\CMS\Document\HtmlDocument $htmlDocument */
            $htmlDocument = Factory::getApplication()->getDocument();
            $htmlDocument->addScriptDeclaration($content);
        }
    }
}