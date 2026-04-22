<?php

/* ======================================================
 # Microsoft/Outlook 365 Mail Connect for Joomla! - v1.0.9 (pro version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x, v5.x, v6.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2026 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html
 # Website: https://www.web357.com
 # Support: support@web357.com
 # Last modified: Tuesday 14 April 2026, 10:47:44 AM
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
            ->registerStyle('web357.microsoft-outlook.css.configuration.style', $baseUrl . 'plugins/system/microsoftoutlook365mailconnect/asset/css/web357-microsoft-oulook-configuration.css')
            ->registerScript('web357.microsoft-outlook.js.configuration.script', $baseUrl . 'plugins/system/microsoftoutlook365mailconnect/asset/js/web357-microsoft-oulook-configuration.js', [], [], ['jquery'])
            ->addScriptOptions('web357.microsoft-outlook.js.configuration.script-options', [
                'actionTestEmail' => Route::_('index.php?web357controller=microsoft-outlook-controller&web357task=send-test-email', false),
                'actionRevokeToken' => Route::_('index.php?web357controller=microsoft-outlook-controller&web357task=revoke-token', false),
            ]);;
    }

}

