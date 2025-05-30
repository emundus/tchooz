<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_templates
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Templates\Administrator\Helper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Template Helper class.
 *
 * @since  3.2
 */
abstract class TemplateHelper
{
    /**
     * Checks if the file is an image
     *
     * @param   string  $fileName  The filename
     *
     * @return  boolean
     *
     * @since   3.2
     */
    public static function getTypeIcon($fileName)
    {
        // Get file extension
        return strtolower(substr($fileName, strrpos($fileName, '.') + 1));
    }

    /**
     * Checks if the file can be uploaded
     *
     * @param   array   $file  File information
     * @param   string  $err   An error message to be returned
     *
     * @return  boolean
     *
     * @since   3.2
     */
    public static function canUpload($file, $err = '')
    {
        $params = ComponentHelper::getParams('com_templates');

        if (empty($file['name'])) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_TEMPLATES_ERROR_UPLOAD_INPUT'), 'error');

            return false;
        }

        // Media file names should never have executable extensions buried in them.
        $executable = [
            'exe', 'phtml','java', 'perl', 'py', 'asp','dll', 'go', 'jar',
            'ade', 'adp', 'bat', 'chm', 'cmd', 'com', 'cpl', 'hta', 'ins', 'isp',
            'jse', 'lib', 'mde', 'msc', 'msp', 'mst', 'pif', 'scr', 'sct', 'shb',
            'sys', 'vb', 'vbe', 'vbs', 'vxd', 'wsc', 'wsf', 'wsh',
        ];
        $explodedFileName = explode('.', $file['name']);

        if (\count($explodedFileName) > 2) {
            foreach ($executable as $extensionName) {
                if (\in_array($extensionName, $explodedFileName)) {
                    $app = Factory::getApplication();
                    $app->enqueueMessage(Text::_('COM_TEMPLATES_ERROR_EXECUTABLE'), 'error');

                    return false;
                }
            }
        }

        if ($file['name'] !== File::makeSafe($file['name']) || preg_match('/\s/', File::makeSafe($file['name']))) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_TEMPLATES_ERROR_WARNFILENAME'), 'error');

            return false;
        }

        $format = strtolower(File::getExt($file['name']));

        $imageTypes   = explode(',', $params->get('image_formats', 'gif,bmp,jpg,jpeg,png,webp'));
        $sourceTypes  = explode(',', $params->get('source_formats', 'txt,less,ini,xml,js,php,css,scss,sass,json'));
        $fontTypes    = explode(',', $params->get('font_formats', 'woff,woff2,ttf,otf'));
        $archiveTypes = explode(',', $params->get('compressed_formats', 'zip'));

        $allowable = array_merge($imageTypes, $sourceTypes, $fontTypes, $archiveTypes);

        if ($format == '' || $format == false || (!\in_array($format, $allowable))) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_TEMPLATES_ERROR_WARNFILETYPE'), 'error');

            return false;
        }

        if (\in_array($format, $archiveTypes)) {
            $zip = new \ZipArchive();

            if ($zip->open($file['tmp_name']) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry     = $zip->getNameIndex($i);
                    $endString = substr($entry, -1);

                    if ($endString != DIRECTORY_SEPARATOR) {
                        $explodeArray = explode('.', $entry);
                        $ext          = end($explodeArray);

                        if (!\in_array($ext, $allowable)) {
                            $app = Factory::getApplication();
                            $app->enqueueMessage(Text::_('COM_TEMPLATES_FILE_UNSUPPORTED_ARCHIVE'), 'error');

                            return false;
                        }
                    }
                }
            } else {
                $app = Factory::getApplication();
                $app->enqueueMessage(Text::_('COM_TEMPLATES_FILE_ARCHIVE_OPEN_FAIL'), 'error');

                return false;
            }
        }

        // Max upload size set to 10 MB for Template Manager
        $maxSize = (int) ($params->get('upload_limit') * 1024 * 1024);

        if ($maxSize > 0 && (int) $file['size'] > $maxSize) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_TEMPLATES_ERROR_WARNFILETOOLARGE'), 'error');

            return false;
        }

        $xss_check = file_get_contents($file['tmp_name'], false, null, -1, 256);
        $html_tags = [
            'abbr', 'acronym', 'address', 'applet', 'area', 'audioscope', 'base', 'basefont', 'bdo', 'bgsound', 'big', 'blackface', 'blink', 'blockquote',
            'body', 'bq', 'br', 'button', 'caption', 'center', 'cite', 'code', 'col', 'colgroup', 'comment', 'custom', 'dd', 'del', 'dfn', 'dir', 'div',
            'dl', 'dt', 'em', 'embed', 'fieldset', 'fn', 'font', 'form', 'frame', 'frameset', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'hr', 'html',
            'iframe', 'ilayer', 'img', 'input', 'ins', 'isindex', 'keygen', 'kbd', 'label', 'layer', 'legend', 'li', 'limittext', 'link', 'listing',
            'map', 'marquee', 'menu', 'meta', 'multicol', 'nobr', 'noembed', 'noframes', 'noscript', 'nosmartquotes', 'object', 'ol', 'optgroup', 'option',
            'param', 'plaintext', 'pre', 'rt', 'ruby', 's', 'samp', 'script', 'select', 'server', 'shadow', 'sidebar', 'small', 'spacer', 'span', 'strike',
            'strong', 'style', 'sub', 'sup', 'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'title', 'tr', 'tt', 'ul', 'var', 'wbr', 'xml',
            'xmp', '!DOCTYPE', '!--',
        ];

        foreach ($html_tags as $tag) {
            // A tag is '<tagname ', so we need to add < and a space or '<tagname>'
            if (stristr($xss_check, '<' . $tag . ' ') || stristr($xss_check, '<' . $tag . '>')) {
                $app = Factory::getApplication();
                $app->enqueueMessage(Text::_('COM_TEMPLATES_ERROR_WARNIEXSS'), 'error');

                return false;
            }
        }

        return true;
    }
}
