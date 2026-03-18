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

defined('_JEXEC') or die();

/**
 * This interface defines the contract for asset classes. Any class implementing this interface
 * must provide a method to register and use the asset.
 */
interface AssetInterface
{

    /**
     * Registers and uses the asset
     *
     * This method is responsible for registering the asset (e.g., CSS or JS) with the system
     * and marking it for use on the page. Implementations should handle the registration
     * process, including dependencies, options, and attributes.
     *
     * @param array $params
     * @return void
     */
    public static function register(array $params = []): void;

}