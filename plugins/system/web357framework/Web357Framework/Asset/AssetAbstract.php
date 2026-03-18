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

namespace Web357Framework\Asset;

use Web357Framework\Helper\WebAssetManagerHelper;

defined('_JEXEC') or die();

/**
 *
 * This abstract class provides a base implementation for asset classes.
 * It implements the AssetInterface and can be extended by concrete asset classes
 * to provide specific functionality for registering and using assets.
 *
 */
abstract class AssetAbstract implements AssetInterface
{

    /** @var WebAssetManagerHelper */
    protected $webAssetManagerHelper;

    /** @var array */
    protected static $registered = [];

    /**
     * Asset initialization
     * @return void
     */
    public function init(): void
    {
        $this->webAssetManagerHelper = new WebAssetManagerHelper();
        $this->registerAssets();
    }

    /**
     * Implements the register functionality
     * @return void
     */
    abstract protected function registerAssets(): void;

    /**
     * Register Asset to Joomla!
     * @param array $params
     * @return void
     */
    public static function register(array $params = []): void
    {
        if (!isset(static::$registered[static::class])) {
            static::$registered[static::class] = true;
            (new static($params))->init();
        }
    }

}