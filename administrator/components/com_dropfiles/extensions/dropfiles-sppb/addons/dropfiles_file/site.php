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

//no direct accees
defined('_JEXEC') || die('restricted aceess');

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps -- Default joomla core class name rule

/**
 * Dropfiles file addon
 */
class SppagebuilderAddonDropfiles_file extends SppagebuilderAddons
{

    /**
     * Render file addon
     *
     * @return string
     */
    public function render()
    {

        $class = (isset($this->addon->settings->class) && $this->addon->settings->class) ? ' ' . $this->addon->settings->class : '';
        $fileUrl = (isset($this->addon->settings->fileUrl) && $this->addon->settings->fileUrl) ? $this->addon->settings->fileUrl : '';

        JLoader::register('DropfilesHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/dropfiles.php');

        $output = '';
        $styles = array();

        if ($fileUrl) {
            $styles[] = JURI::base('true') . '/components/com_dropfiles/assets/css/front_ver5.4.css' ;

            $fileUri = str_replace(JURI::root(), '', $fileUrl) ;
            $fileUri = str_replace('index.php/', '', $fileUri) ;
            $params             = JComponentHelper::getParams('com_dropfiles');
            $dropfilesUri       = $params->get('uri', 'files');
            $dropfilesUriSegs      = explode('/', $dropfilesUri);
            $totalDropfilesSegs = count($dropfilesUriSegs);
            $path               = explode('/', $fileUri);
            $valid = true;
            if ($totalDropfilesSegs < count($path)) {
                for ($index = $totalDropfilesSegs - 1; $index < $totalDropfilesSegs; $index++) {
                    if ($dropfilesUriSegs[$index] !== $path[$index]) {
                        $valid = false;
                    }
                }
                if (!isset($path[$totalDropfilesSegs + 2]) || $path[$totalDropfilesSegs + 2] === '') {
                    $valid = false;
                }
            } else {
                $valid = false;
            }

            $html = '';
            if ($valid) {
                $catid =  $path[$totalDropfilesSegs] ;
                $fileID = $path[$totalDropfilesSegs + 2];
                $html      = DropfilesHelper::displaySingleFile($catid, $fileID);
            }

            $output .= '<div class="sppb-addon sppb-addon-dropfiles-file' . $class . '">';
            if ($html) {
                foreach ($styles as $style) {
                    $output .= '<link rel="stylesheet" href="' . $style . '" />';
                }
                $output .= $html;
            } else {
                $output .= 'Empty content';
            }
            $output .= '</div>';
        } else {
            $output .= '<div class="sppb-addon sppb-addon-dropfiles-file' . $class . '">';
            $output .= '<img style="background: url(\''. JURI::root().'/components/com_dropfiles/assets/images/file_download.png\') no-repeat scroll center center #444444; height: 200px; border-radius: 10px; width: 99%;" src="'. JURI::root() . 'components/com_dropfiles/assets/images/t.gif" />';
            $output .= '<span style="font-size: 13px; text-align: center;">Please enter a Dropfiles file direct url to activate the preview</span>';
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Css for file addon
     *
     * @return string
     */
    public function css()
    {
        $addon_id = '#sppb-addon-' . $this->addon->id;

        $style = '';
        $style_sm = '';
        $style_xs = '';

        $style .= (isset($this->addon->settings->addon_text_transform) && $this->addon->settings->addon_text_transform) ? 'text-transform: ' . $this->addon->settings->addon_text_transform  . '; ' : '';

        if (isset($addon->settings->addon_font_style->italic) && $addon->settings->addon_font_style->italic) {
            $style .= 'font-style: italic;';
        }

        if (isset($addon->settings->addon_font_style->uppercase) && $addon->settings->addon_font_style->uppercase) {
            $style .= 'text-transform: uppercase;';
        }

        if (isset($addon->settings->addon_font_style->weight) && $addon->settings->addon_font_style->weight) {
            $style .= 'font-weight: ' . $addon->settings->addon_font_style->weight . ';';
        }

        $css = '';
        if ($style) {
            $css .= $addon_id . ' .sppb-addon-title {' . $style . '}';
        }

        if ($style_sm) {
            $css .= '@media (min-width: 768px) and (max-width: 991px) {';
                $css .= $addon_id . ' .sppb-addon-title {' . $style_sm . '}';
            $css .= '}';
        }

        if ($style_xs) {
            $css .= '@media (max-width: 767px) {';
                $css .= $addon_id . ' .sppb-addon-title {' . $style_xs . '}';
            $css .= '}';
        }

        return $css;
    }
}
