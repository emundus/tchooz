<?php

/* ======================================================
 # Microsoft/Outlook 365 Mail Connect for Joomla! - v1.0.8 (pro version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2024 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html
 # Website: https://www.web357.com
 # Support: support@web357.com
 # Last modified: Tuesday 03 February 2026, 10:20:16 AM
 ========================================================= */
declare(strict_types=1);

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Asset;

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Web357Framework\Asset\AssetAbstract;

class PluginConfigurationAsset extends AssetAbstract
{

    /**
     * {@inheritdoc}
     */
    protected function registerAssets(): void
    {
        $baseUrl = rtrim(Uri::root(), '/') . '/';
        $this->webAssetManagerHelper
            ->registerStyle('web357.microsoft-outlook.css.configuration.style', $baseUrl . 'plugins/system/microsoftoutlook365mailconnect/asset/css/web357-microsoft-oulook-configuration.min.css')
            ->registerScript('web357.microsoft-outlook.js.configuration.script', $baseUrl . 'plugins/system/microsoftoutlook365mailconnect/asset/js/web357-microsoft-oulook-configuration.min.js', [], [], ['jquery'])
            ->addScriptOptions('web357.microsoft-outlook.js.configuration.script-options', [
                'actionTestEmail' => Route::_('index.php?web357controller=microsoft-outlook-controller&web357task=send-test-email', false),
                'actionRevokeToken' => Route::_('index.php?web357controller=microsoft-outlook-controller&web357task=revoke-token', false),
            ]);;
    }

}

