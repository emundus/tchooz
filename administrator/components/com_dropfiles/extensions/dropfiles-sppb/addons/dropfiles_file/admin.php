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

defined('_JEXEC') || die('restricted aceess');

use Joomla\CMS\Language\Text;

JLoader::register('DropfilesHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/dropfiles.php');
JLoader::register('DropfilesComponentHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/component.php');
$spVersion = DropfilesComponentHelper::getVersion('com_sppagebuilder');
$icon  = JURI::root().'plugins/sppagebuilder/dropfiles/addons/dropfiles_file/assets/images/file_icon.svg';
if (version_compare($spVersion, '4.0') >= 0) {
    $icon = '<img src="'. JURI::root() . 'plugins/sppagebuilder/dropfiles/addons/dropfiles_file/assets/images/file_icon.svg" />';
}
SpAddonsConfig::addonConfig(
    array(
        'type'=>'content',
        'addon_name'=>'dropfiles_file',
        'title'=> 'Dropfiles File',
        'icon'=> $icon,
        'category'=>'General',
        'attr'=>array(
            'general' => array(
                'fileUrl' => [
                    'type'     => 'text',
                    'title'    => 'File direct link',
                    'desc'     => 'File URL: copy the file URL from Dropfiles single file settings, ex. https://www.domain.com/file.pdf',
                    'std'=> '',
                ],
                'dropfiles_separator' => [
                    'type' => 'separator'
                ],
                'admin_label'=>array(
                    'type'=>'text',
                    'title'=>'Admin Label',
                    'desc'=>'Admin label description.',
                    'std'=> '',
                ),
                'class'=>array(
                    'type'=>'text',
                    'title'=>'CSS CLass',
                    'std'=> ''
                ),
            ),
        ),
    )
);
