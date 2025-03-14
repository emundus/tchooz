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
class JFormFieldDropboxbtn extends JFormField
{
    /**
     * Type
     *
     * @var string
     */
    protected $type = 'Dropboxbtn';

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
     * Field connect dropbox button
     *
     * @throws Exception Fire if errors
     *
     * @return string
     */
    protected function getInput()
    {
        $path_dropfilesdropbox = JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesDropbox.php';
        JLoader::register('DropfilesDropbox', $path_dropfilesdropbox);
        // Initialize JavaScript field attributes.
        $dropbox = new DropfilesDropbox();

        if (!class_exists('DropfilesModelOptions')) {
            JLoader::register('DropfilesModelOptions', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/options.php');
        }

        $options = new DropfilesModelOptions();
        $dropboxWatchChanges = $options->get_option('dropbox_watch_changes');
        $dropboxCallbackUrl = JURI::root() . 'index.php?option=com_dropfiles&task=frontdropbox.listener';
        $params = JComponentHelper::getParams('com_dropfiles');
        $connectionMode = $params->get('dropboxConnectMethod', 'none');
        $html = '
        <style>
        .btn-dropbox {
            background: #1d6cb0 none repeat scroll 0 0 !important;
            border: medium none !important;
            border-radius: 2px !important;
            box-shadow: none !important;
            height: auto !important;
            padding: 5px 20px !important;
            text-shadow: none !important;
            width: auto !important;
            color: #fff !important;
        }
        .dropbox_node_head > h3{
                font-weight: bold;
                padding: 8px 0 7px 15px;
                background: ;
                background-color: #23282D;
                border-color: #bce8f1;
                color: #eee;
                font-size: 13px;
            }
        </style>
        ';
        if ($dropbox->checkAuth()) {
            $url = $dropbox->getAuthorizeDropboxUrl();
            $html .= '<p><a id="dropboxconnect" class="btn btn-primary btn-dropbox" href="#" ';
            $html .= ' onclick="window.open(\'' . $url . '\',\'foo\',\'width=600,height=600\');return false;">';
            $html .= ' <img src="' . JURI::root() . '/components/com_dropfiles/assets/images/dropbox_icon_colored.png';
            $html .= '" alt="" /> <span class="btn-title">' . JText::_('COM_DROPFILES_CONNECT_DROPBOX') . '</span></a>';
            $html .= JText::_('COM_DROPFILES_DROPBOX_CONNECT_PART2_FIRST') . JText::sprintf('COM_DROPFILES_DROPBOX_CONNECT_PART1_2', $dropbox->getRedirectUrl());
            $html .= '</p>';
        } else {
            $html .= '<a class="btn btn-primary btn-dropbox" ';
            $html .= ' href="index.php?option=com_dropfiles&task=config.logoutDropbox">';
            $html .= ' <img src="' . JURI::root() . '/components/com_dropfiles/assets/images/dropbox_icon_colored.png';
            $html .= '" alt="" /> ';
            $html .= '<span class="btn-title">' . JText::_('COM_DROPFILES_DISCONNECT_CONNECT_DROPBOX') . '</span></a>';
            $html .= '<a id="dropfiles_btn_dropbox_changes" class="btn btn-success btn-dropbox-changes" href="#" title="'. JText::_('COM_DROPFILES_DROPBOX_WATCH_CHANGES_TOOLTIP') .'">';

            if ($dropboxWatchChanges) {
                $html .= JText::_('COM_DROPFILES_DROPBOX_STOP_WATCH_CHANGES');
            } else {
                $html .= JText::_('COM_DROPFILES_DROPBOX_WATCH_CHANGES');
            }

            $html .= '</a>';

            if (!$dropboxWatchChanges && strval($connectionMode) === 'manual') {
                $html .= '<div class="dropfiles-float-message dropfiles-dropbox-watch-change-message" style="padding:0; margin-top:0; min-height: 0;">';
                $html .= '<div class="alert-message"><strong>'. JText::_('COM_DROPFILES_DROPBOX_WATCH_CHANGE_WARNING') .'</strong> '. $dropboxCallbackUrl .'</div>';
                $html .= '</div>';
            }
        }

        return $html;
    }
}
