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

// No direct access
defined('_JEXEC') || die;
jimport('joomla.filesystem.file');

/**
 * Class DropfilesResizeImage
 */
class DropfilesResizeImage
{
    /**
     * Load image
     *
     * @param string $filename File name
     * @param string $type     File type
     *
     * @return mixed
     */
    public static function loadImage($filename, $type)
    {
        if ($type == IMAGETYPE_JPEG) {
            $img = imagecreatefromjpeg($filename);
        } elseif ($type == IMAGETYPE_PNG) {
            $img = imagecreatefrompng($filename);
        } elseif ($type == IMAGETYPE_GIF) {
            $img = imagecreatefromgif($filename);
        }

        return $img;
    }

    /**
     * Process resize image
     *
     * @param string|integer $new_width  New file width
     * @param string|integer $new_height New file height
     * @param string|integer $image      Instance
     * @param string|integer $width      File width
     * @param string|integer $height     File height
     *
     * @return mixed
     */
    public static function resizeImage($new_width, $new_height, $image, $width, $height)
    {
        $new_image = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        return $new_image;
    }

    /**
     * Process resize image to width
     *
     * @param string|integer $new_width New file width
     * @param string|integer $image     Instance
     * @param string|integer $width     File width
     * @param string|integer $height    File height
     *
     * @return mixed
     */
    public static function resizeImageToWidth($new_width, $image, $width, $height)
    {
        $ratio = $new_width / $width;
        $new_height = $height * $ratio;
        return self::resizeImage($new_width, $new_height, $image, $width, $height);
    }

    /**
     * Process resize image to height
     *
     * @param string|integer $new_height New file height
     * @param string|integer $image      Instance
     * @param string|integer $width      File width
     * @param string|integer $height     File height
     *
     * @return mixed
     */
    public static function resizeImageToHeight($new_height, $image, $width, $height)
    {
        $ratio = $new_height / $height;
        $new_width = $width * $ratio;
        return self::resizeImage($new_width, $new_height, $image, $width, $height);
    }

    /**
     * Process scale image
     *
     * @param string|integer $scale  Image scale
     * @param string|integer $image  Instance
     * @param string|integer $width  File width
     * @param string|integer $height File height
     *
     * @return mixed
     */
    public static function scaleImage($scale, $image, $width, $height)
    {
        $new_width = $width * $scale;
        $new_height = $height * $scale;
        return self::resizeImage($new_width, $new_height, $image, $width, $height);
    }

    /**
     * Save file
     *
     * @param string|integer $new_image    New file
     * @param string|integer $new_filename New name
     * @param string|integer $new_type     Target file type
     * @param string|integer $quality      File quality
     *
     * @return mixed
     */
    public static function saveImage($new_image, $new_filename, $new_type = 'png', $quality = 80)
    {
        if ($new_type == 'jpeg') {
            imagejpeg($new_image, $new_filename, $quality);
        } elseif ($new_type == 'png') {
            imagepng($new_image, $new_filename, $quality);
        } elseif ($new_type == 'gif') {
            imagegif($new_image, $new_filename, $quality);
        }
    }

    /**
     * Convert PNG image
     *
     * @param string         $imgPath Current file path
     * @param string         $pngPath Disc file path
     * @param string|integer $quality The number of quality
     *
     * @return mixed|void
     */
    public static function convertPNG($imgPath, $pngPath, $quality = null)
    {

        if (empty($imgPath) || $imgPath === '') {
            return false;
        }

        $imgContent = file_get_contents($imgPath);
        $createdImg = imagecreatefromstring($imgContent);
        imagepng($createdImg, $pngPath, $quality);

        return true;
    }

    public function resizeThumbnail($path, $fileId, $w = '200', $h = '200')
    {
        if (!$path || $path === '') {
            return '';
        }

        JLoader::register('DropfilesHelperfolder', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/dropfilesHelperFolder.php');
        $folderHelper = new DropfilesHelperfolder();
        $saveFilePath = $folderHelper->getThumbnailsPath();
        $fileName = $saveFilePath . strval($fileId) . '_' . strval(md5($path)) . '.png';
        $originSize = array();
        list($originSize['width'], $originSize['height'], $originSize['type']) = getimagesize($path);
        $param = $w . 'x' . $h;
        $jImg = new JImage(JPath::clean($path));
        $newThumbnail = $jImg->createThumbs(array($param));
        $newThumbnailPath = $newThumbnail[0]->getPath();

        if (is_file($newThumbnailPath) && intval($originSize['type']) === 3) {
            JFile::copy($newThumbnailPath, $fileName);
            return $fileName;
        } elseif (is_file($newThumbnailPath)) {
            self::convertPNG($newThumbnailPath, $fileName);
            return $fileName;
        }

        return '';
    }
}
