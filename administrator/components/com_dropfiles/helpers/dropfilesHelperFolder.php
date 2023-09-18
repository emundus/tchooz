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
 * @since     1.6
 */


defined('_JEXEC') || die;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * DropfilesHelperfolder class
 */
class DropfilesHelperfolder
{
    /**
     * Get preview storage path
     *
     * @return mixed|void
     */
    public static function getPreviewsPath()
    {
        $path = self::getBasePath() . 'previews';

        if (!file_exists($path)) {
            self::CreateSecureFolder($path);
        }

        return self::trailingslashit($path);
    }

    /**
     * Get plugin custom template base path
     *
     * @return string
     */
    public static function getBasePath()
    {
        return JPATH_ROOT . '/media/com_dropfiles/';
    }

    /**
     * Create secure folder
     *
     * @param string $path Path to folder need to created
     *
     * @return boolean
     */
    public static function CreateSecureFolder($path) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- CREATE folder only
    {
        if (file_exists($path . 'index.html') && file_exists($path . '.htaccess')) {
            return true;
        }

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
            $data = '<html><body bgcolor="#FFFFFF"></body></html>';
            $file = fopen($path . DIRECTORY_SEPARATOR . 'index.html', 'w');
            fwrite($file, $data);
            fclose($file);
            $data = "Options -Indexes\nOrder deny,allow\nDeny from all\n<Files ~ \".(jpg|png|svg|css)$\">\nAllow from all\n</Files>";
            $file = fopen($path . DIRECTORY_SEPARATOR . '.htaccess', 'w');
            fwrite($file, $data);
            fclose($file);

            return true;
        }

        return false;
    }

    /**
     * Get thumbnail storage path
     *
     * @return mixed|void
     */
    public static function getThumbnailsPath()
    {
        $path = self::getBasePath() . 'thumbnails';

        if (!file_exists($path)) {
            self::CreateSecureFolder($path);
        }

        return self::trailingslashit($path);
    }

    /**
     * Appends a trailing slash.
     *
     * Will remove trailing forward and backslashes if it exists already before adding
     * a trailing forward slash. This prevents double slashing a string or path.
     *
     * The primary use of this is for paths and thus should be used for paths. It is
     * not restricted to paths and offers no specific path support.
     *
     * @param string $string What to add the trailing slash to.
     *
     * @return string String with trailing slash added.
     */
    public static function trailingslashit($string)
    {
        return self::untrailingslashit($string) . '/';
    }

    /**
     * Removes trailing forward slashes and backslashes if they exist.
     *
     * The primary use of this is for paths and thus should be used for paths. It is
     * not restricted to paths and offers no specific path support.
     *
     * @param string $string What to remove the trailing slashes from.
     *
     * @return string String without the trailing slashes.
     */
    public static function untrailingslashit($string)
    {
        return rtrim($string, '/\\');
    }

    /**
     * Delete a directory RECURSIVELY
     *
     * @param string $dir Directory path
     *
     * @return void
     * @since  version
     */
    public static function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (filetype($dir . '/' . $object) === 'dir') {
                        self::rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}
