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
class JFormFieldServerfolder extends JFormField
{
    /**
     * Type
     *
     * @var string
     */
    protected $type = 'serverfolder';

    /**
     * Get label
     *
     * @return string
     */
    protected function getLabel()
    {
        return '';
    }

    /**
     * Field server folder
     *
     * @throws Exception Fire if errors
     *
     * @return string
     */
    protected function getInput()
    {
        // Initialize some field attributes.
        $app = JFactory::getApplication();
        $params = JComponentHelper::getParams('com_dropfiles');
        $allowedext_list = '7z,ace,bz2,dmg,gz,rar,tgz,zip,csv,doc,docx,html,key,keynote,odp,ods,odt,pages,pdf,pps,ppt,'
            . 'pptx,rtf,tex,txt,xls,xlsx,xml,bmp,exif,gif,ico,jpeg,jpg,png,psd,tif,tiff,aac,aif,aiff,alac,amr,au,cdda,'
            . 'flac,m3u,m4a,m4p,mid,mp3,mp4,mpa,ogg,pac,ra,wav,wma,3gp,asf,avi,flv,m4v,mkv,mov,mpeg,mpg,'
            . 'rm,swf,vob,wmv';
        $allowed_ext = explode(',', $params->get('allowedext', $allowedext_list));
        $allowed_ext = (is_array($allowed_ext) && !empty($allowed_ext)) ? implode(', ', $allowed_ext) : $allowed_ext;
        $class = $this->element['class'] ? ' ' . (string)$this->element['class'] . '' : '';
        $content  = '<div id="import-server-folders" class="ju-settings-option full-width import-server">';
        $content .= '<p class="description" style="padding: 10px 0">'. JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_SERVER_FOLDER_DESC') .'</p>';
        $content .= '<span class="text-orange" style="word-break: break-all;">'. $allowed_ext .'</span>';
        $content .= '<div class="dropfiles_row_full">';
        $content .= '<div id="dropfiles_foldertree" class="dropfiles-no-padding"></div>';
        $content .= '<div class="dropfiles-process-bar-full process_import_ftp_full" style=""><div class="dropfiles-process-bar process_import_ftp" data-w="0"></div></div>';
        $content .= '<button type="button" id="import-server-folders-btn" class="ju-button no-background orange-outline-button waves-effect waves-light">';
        $content .= '<label>'. JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_SERVER_FOLDER_IMPORT_FOLDER') .'</label>';
        $content .= '<span class="spinner" style="display:none; margin: 0 0 0 5px; vertical-align: middle"></span>';
        $content .= '</button>';
        $content .= '<span class="dropfiles_info_import" style="display: none">'. JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_SERVER_FOLDER_IMPORTED') .'</span>';
        $content .= '</div>';
        $content .= '</div>';

        return $content;
    }
}
