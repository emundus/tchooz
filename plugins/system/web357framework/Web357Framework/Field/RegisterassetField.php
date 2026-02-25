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

namespace Web357Framework\Field;

use Web357Framework\Field\Base\ListField;

defined('_JEXEC') or die();

/**
 * Register Asset Field
 *
 * A custom form field type for registering assets dynamically.
 * This field iterates over `<asset>` elements defined in the XML form definition,
 * checks if the corresponding class exists, and registers the asset if it does.
 *
 * ### Example XML Definition
 *
 * <field
 *      addfieldprefix="Web357Framework\Field" 
 *      type="registerasset" >
 *      <asset>\Web357Framework\Asset\Select2Asset</asset>
 * </field>
 */
class RegisterassetField extends ListField
{
    /** @var string */
    protected $type = 'registerasset';

    /**
     * Iterates over `<asset>` elements defined in the XML form definition,
     * checks if the corresponding class exists, and registers the asset if it does.
     */
    protected function getInput()
    {
        foreach ($this->element->xpath('asset') as $assetClass) {
            $assetClass = (string)$assetClass;
            if (class_exists($assetClass)) {
                $assetClass::register();
            }
        }
    }

    protected function getLabel()
    {
    }
}