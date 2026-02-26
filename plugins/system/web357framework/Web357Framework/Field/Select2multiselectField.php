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

use Joomla\CMS\Language\Text;
use Web357Framework\Asset\Select2Asset;
use Web357Framework\Field\Base\ListField;

defined('_JEXEC') or die();

/**
 * Select2 Multi-Select Field
 *
 * This field extends the ListField class to provide a multi-select dropdown
 * with Select2 functionality. It registers the Select2 assets, adds Select2
 * options to the select field, and disables Joomla 3's core multiselect
 * functionality by setting `data-chosen="true"`.
 *
 * ### Example XML Definition
 *
 *  <field
 *     addfieldprefix="Web357Framework\Field"
 *     type="select2multiselect"
 *     name="field_name"
 *     label="FIELD_LABEL"
 *     description="FIELD_DESCRIPTION"
 *     default="02"
 *     class="additional-class"
 *     multiple="true"
 *     select2-options='{"closeOnSelect": false, "allowClear": true, "displayShowAll": true}'>
 *     <option value="01">OPTION_LABEL_1</option>
 *     <option value="02">OPTION_LABEL_2</option>
 *     <option value="03">OPTION_LABEL_3</option>
 *  </field>
 *
 */
class Select2multiselectField extends ListField
{
    /** @var string */
    protected $type = 'select2multiselect';

    /**
     * Method to get the field input markup.
     *
     * This method:
     * - Registers the Select2 assets.
     * - Adds the Select2 options to the select field.
     * - Disables Joomla 3's core multiselect functionality by setting `data-chosen="true"`.
     *
     * @return  string  The field input markup.
     */
    protected function getInput()
    {
        /* Register Select2Asset */
        Select2Asset::register();

        /** @var \Joomla\CMS\Form\Field\ListField $this */
        $selectHtml = parent::getInput();
        $select2Options = $this->getSelect2Options();

        return str_replace('<select', '<select data-chosen="true" data-web357-select2-options="' . htmlspecialchars(json_encode($select2Options), ENT_QUOTES, 'UTF-8') . '"', $selectHtml);
    }

    /**
     * Returns the Select2 options.
     *
     * This method retrieves the `select2-options` attribute from the field definition
     * and decodes it into an array. If the attribute is not set, it returns an empty array.
     *
     * @return  array  The Select2 options as an associative array.
     */
    protected function getSelect2Options(): array
    {
        /* Get the 'select2-options' attribute and decode it */
        $select2Options = json_decode($this->getAttribute('select2-options') ?? '{}', true);

        /* Translate the placeholder if it exists */
        $placeholder = $this->getAttribute('placeholder') ?? ($select2Options['placeholder'] ?? null);
        if ($placeholder) {
            $select2Options['placeholder'] = Text::_($placeholder);
        }

        return $select2Options;
    }
}