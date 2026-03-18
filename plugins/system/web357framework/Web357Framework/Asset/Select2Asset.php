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

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die();

class Select2Asset extends AssetAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function registerAssets(): void
    {
        /* current version: Select2 4.1.0-rc.0 */
        $this->webAssetManagerHelper
            ->registerStyle('web357.framework.css.select2.style', $this->webAssetManagerHelper->getBaseMediaPath('/plg_system_web357framework/js/libs/select2/select2.min.css'))
            ->registerStyle('web357.framework.css.select2.web357', $this->webAssetManagerHelper->getBaseMediaPath('/plg_system_web357framework/js/libs/select2/web357-select-2.min.css'))
            ->registerScript('web357.framework.js.select2.script', $this->webAssetManagerHelper->getBaseMediaPath('/plg_system_web357framework/js/libs/select2/select2.full.min.js'), [], [], ['jquery'])
            ->registerScript('web357.framework.js.select2.web357', $this->webAssetManagerHelper->getBaseMediaPath('/plg_system_web357framework/js/libs/select2/web357-select-2.min.js'), [], [], ['web357.framework.js.select2.script'])
            ->addScriptOptions('web35.select2', [
                'containerClass' => $this->webAssetManagerHelper->getJoomlaVersionClass('web357-select2-container'),
                'dropdownClass' => $this->webAssetManagerHelper->getJoomlaVersionClass('web357-select2-dropdown'),
                'selectOptionText' => Text::_('JGLOBAL_TYPE_OR_SELECT_SOME_OPTIONS'),
                'selectAllText' => Text::_('JGLOBAL_SELECTION_ALL'),
            ]);
    }

}