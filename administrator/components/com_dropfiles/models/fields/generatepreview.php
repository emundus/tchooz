<?php
/**
 * Dropfiles
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 *
 * @package   Dropfiles
 * @copyright Copyright (C) 2013 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2013 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license   GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') || die;

jimport('joomla.form.formfield');

/**
 * Form Field class for the Joomla Framework.
 */
class JFormFieldGeneratepreview extends JFormField
{
    /**
     * Type
     *
     * @var string
     */
    protected $type = 'generatepreview';

    /**
     * Get label
     *
     * @return string
     */
    protected function getLabel()
    {
        $label = JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_LABEL') ?
            JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_LABEL') : 'JoomUnited previewer server';

        return $label;
    }

    /**
     * Add field File preview server
     *
     * @return string
     */
    protected function getInput()
    {
        $params = JComponentHelper::getParams('com_dropfiles');
        $class = $this->element['class'] ? ' ' . (string)$this->element['class'] . '' : '';
        $name = $this->element['name'] ? $this->element['name'] : 'auto_generate_preview';
        $val = $params->get('auto_generate_preview', 0);
        $checked = (int) $val === 1 ? 'checked' : '';

        $html = '<div class="ju-switch-button">';
        $html .= '<label class="switch">';
        $html .= '<input type="checkbox" class="' . $class . '" ' . $checked . ' name="jform[' . $name . ']" id="jform_auto_generate_preview" value="1" data-val="' . $val . '">';
        $html .= '<span class="slider"></span></label></div>';

        $html .= $this->showGenerateButton($val);

        return $html;
    }

    /**
     * Generate indexer button
     *
     * @param string $show Show indexer or not by default
     *
     * @return string
     */
    public function showGenerateButton($show)
    {
        $confirmText = JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_CONFIRM') ?
            JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_CONFIRM') : 'You are about to launch a generate preview image of all your files. It requires that you let this tab open until the end of the process. Click OK to launch';
        $generateLabel = JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_BUTTON_LABEL') ?
            JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_BUTTON_LABEL') : 'Generate preview';
        $errorTitle = JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_ERROR_LABEL') ?
            JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_ERROR_LABEL') : 'Mainly due to file format not supported by the previewer or because the file size is over 10MB. but no worries, we\'ll try to use the Google previewer for those files instead';
        $secureDesc = JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_SECURE_DESC') ?
            JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_SECURE_DESC') : 'Your preview file will have the same access limitation as the downloadable file, meaning that if the file is under access limitation, non authorized users won\'t be able to access to the preview';
        $secureLabel = JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_SECURE_LABEL') ?
            JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_SECURE_LABEL') : 'Secure generated file';
        $showLogLabel = JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_SHOW_LOGS_LABEL') ?
            JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_SHOW_LOGS_LABEL') : 'Show log';
        $hideLogLabel = JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_HIDE_LOGS_LABEL') ?
            JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_HIDE_LOGS_LABEL') : 'Hide log';
        $previewLog = JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_PREVIEW_LOG_LABEL') ?
            JText::sprintf('COM_DROPFILES_CONFIG_PREVIEW_SERVER_PREVIEW_LOG_LABEL') : 'Preview generation log';
        $style = !$show ? 'display:none' : '';

        $html = '<div class="dropfiles-process-switcher generate_preview_wrapper" style="' . $style . '">';
        $html .= '<button ';
        $html .= 'data-confirm="' . $confirmText . '" ';
        $html .= 'id="dropfiles_generate_preview" type="button" class="ju-button ju-material-button">';
        $html .= $generateLabel;
        $html .= '</button>';
        $html .= '<div class="dropfiles_sub_control">';
        $html .= '<label rel="ref_secure_preview_file" title="' . $secureDesc . '">';
        $html .= '<input type="checkbox" id="ref_secure_preview_file" rel="secure_preview_file" onChange="jQuery(\'input#jform_secure_preview_file\').val(jQuery(this).is(\':checked\') ? 1 : 0)" />&nbsp;' . $secureLabel . '</label>';
        $html .= '<script>jQuery(document).ready(function() {jQuery(\'input[rel=secure_preview_file]\').prop(\'checked\', jQuery(\'input#jform_secure_preview_file\').val() === \'1\' ? true : false);})</script>';
        $html .= '<span id="dropfiles_generate_error_message" title="' . $errorTitle . '"></span><span id="dropfiles_show_log" data-show-label="' . $showLogLabel . '" data-hide-label="'. $hideLogLabel .'" data-clicked="0">' . $showLogLabel . '</span>';
        $html .= '</div>';
        $html .= '<div id="dropfiles_generate_preview-logs" data-label="' . $previewLog . '"></div>';
        $html .= '</div>';

        return $html;
    }
}
