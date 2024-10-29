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

/**
 * DropfilesFilesHelper class
 */
class DropfilesFilesHelper
{

    /**
     * Convert bytes too file size format
     *
     * @param integer $bytes     Bytes
     * @param integer $precision Precision
     *
     * @return string
     * @since  version
     */
    public static function bytesToSize($bytes, $precision = 2)
    {
        $sz = array('COM_DROPFILES_FIELD_FILE_BYTE',
            'COM_DROPFILES_FIELD_FILE_KILOBYTE',
            'COM_DROPFILES_FIELD_FILE_MEGABYTE',
            'COM_DROPFILES_FIELD_FILE_GIGABYTE',
            'COM_DROPFILES_FIELD_FILE_TERRABYTE',
            'COM_DROPFILES_FIELD_FILE_PETABYTE');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf('%.' . $precision . 'f', $bytes / pow(1000, $factor)) . ' ' . JText::_($sz[$factor]);
    }

    /**
     * Include JS Helper
     *
     * @return void
     * @since  version
     */
    public static function includeJSHelper()
    {
        $doc = JFactory::getDocument();
        $doc->addScript(JURI::root() . 'components/com_dropfiles/assets/js/helper.js');
        if (DropfilesBase::isJoomla40()) {
            JHtml::_('behavior.core');
        } else {
            JHtml::_('behavior.framework', true);
        }
        JText::script('COM_DROPFILES_FIELD_FILE_BYTE');
        JText::script('COM_DROPFILES_FIELD_FILE_KILOBYTE');
        JText::script('COM_DROPFILES_FIELD_FILE_MEGABYTE');
        JText::script('COM_DROPFILES_FIELD_FILE_GIGABYTE');
        JText::script('COM_DROPFILES_FIELD_FILE_TERRABYTE');
        JText::script('COM_DROPFILES_FIELD_FILE_PETABYTE');
        JText::script('COM_DROPFILES_DOWNLOAD_SELECTED');
        JText::script('COM_DROPFILES_DOWNLOAD_ALL');
        JText::script('COM_DROPFILES_LIGHT_BOX_LOADING_STATUS');
    }

    /**
     * Generate download url
     *
     * @param integer $id           File id
     * @param integer $id_category  Category id
     * @param string  $categoryname Category name
     * @param boolean $token        Token string
     * @param string  $filename     File name
     * @param boolean $forDownload  For download
     *
     * @return string
     * @since  version
     */
    public static function genUrl(
        $id,
        $id_category,
        $categoryname = '',
        $token = false,
        $filename = null,
        $forDownload = true
    ) {
        $config = JFactory::getConfig();
        $params = JComponentHelper::getParams('com_dropfiles');
        $dropfilesUri = $params->get('uri', 'files');
        $url = JURI::root();
        if ($config->get('sef') && $dropfilesUri) {
            if (!$config->get('sef_rewrite')) {
                $url .= 'index.php/';
            }

            $url .= $dropfilesUri;

            $url .= '/' . $id_category;
            if ($categoryname) {
                $url .= '/' . preg_replace(array('/\'/', '#/#'), '', self::makeSafeFilename($categoryname, false));
            }

            $url .= '/' . $id;
            if ($filename !== null) {
                $url .= '/' . preg_replace('/\'/', '', self::makeSafeFilename($filename));
            }

            if ($token) {
                $url .= '?token=' . $token;
                if (!$forDownload) {
                    $url .= '&preview=1';
                }
            } elseif (!$forDownload) {
                $url .= '?preview=1';
            }
        } else {
            $url = JURI::root() . 'index.php?option=com_dropfiles&task=frontfile.download&&id=';
            $url .= $id . '&catid=' . $id_category;
            if ($token) {
                $url .= '&token=' . $token;
            }
            if (!$forDownload) {
                $url .= '&preview=1';
            }
            $url = JRoute::_($url);
        }
        return $url;
    }

    /**
     * Generate Viewer Url
     *
     * @param integer $id           File id
     * @param integer $id_category  Category id
     * @param string  $categoryname Category name
     * @param boolean $token        Token string
     * @param string  $filename     File name
     *
     * @throws Exception Fire if error
     *
     * @return string
     */
    public static function genViewerUrl($id, $id_category, $categoryname = '', $token = false, $filename = null)
    {
        $generatedPreviewUrl = self::getGeneratedPreviewUrl($id, $id_category, $token);
        if (false !== $generatedPreviewUrl) {
            return $generatedPreviewUrl;
        }

        $url = self::genUrl($id, $id_category, $categoryname, $token, $filename, false);
        return 'https://docs.google.com/viewer?url=' . urlencode($url) . '&embedded=true';
    }

    /**
     * Generate Media Viewer Url
     *
     * @param string  $id          File id
     * @param integer $id_category Category id
     * @param string  $ext         File extension
     *
     * @return string
     * @since  version
     */
    public static function genMediaViewerUrl($id, $id_category, $ext = '')
    {
        $imagesType = array('jpg', 'png', 'gif', 'jpeg', 'jpe', 'bmp', 'ico', 'tiff', 'tif', 'svg', 'svgz');
        $videoType  = array(
            'mp4',
            'mpeg',
            'mpe',
            'mpg',
            'mov',
            'qt',
            'rv',
            'avi',
            'movie',
            'flv',
            'webm',
            'ogv'
        );//,'3gp'
        $audioType  = array(
            'mid',
            'midi',
            'mp2',
            'mp3',
            'mpga',
            'ram',
            'rm',
            'rpm',
            'ra',
            'wav'
        );  // ,'aif','aifc','aiff'
        if (in_array($ext, $imagesType)) {
            $type = 'image';
        } elseif (in_array($ext, $videoType)) {
            $type = 'video';
        } elseif (in_array($ext, $audioType)) {
            $type = 'audio';
        } else {
            $type = '';
        }
        $url_frontviewer = JUri::root() . 'index.php?option=com_dropfiles&tmpl=component&view=frontviewer&id=';

        return $url_frontviewer . $id . '&catid=' . $id_category . '&type=' . $type . '&ext=' . $ext;
    }

    /**
     * Check media file
     *
     * @param string $ext File extension
     *
     * @return boolean
     * @since  version
     */
    public static function isMediaFile($ext)
    {
        $media_arr = array('mid', 'midi', 'mp2', 'mp3', 'mpga', 'ram', 'rm', 'rpm', 'ra', 'wav', //,'aif','aifc','aiff'
            'mp4', 'mpeg', 'mpe', 'mpg', 'mov', 'qt', 'rv', 'avi', 'movie', 'flv', 'webm', 'ogv', //'3gp',
            'jpg', 'png', 'gif', 'jpeg', 'jpe', 'bmp', 'ico', 'tiff', 'tif', 'svg', 'svgz');
        if (in_array($ext, $media_arr)) {
            return true;
        }
        return false;
    }

    /**
     * Add more file info
     *
     * @param array  $items    Files
     * @param object $category Category
     *
     * @return array
     * @since  version
     */
    public static function addInfosToFile($items, $category)
    {
        JLoader::register('DropfilesModelTokens', JPATH_ROOT . '/components/com_dropfiles/models/tokens.php');
        $params = JComponentHelper::getParams('com_dropfiles');
        $model = DropfilesModelTokens::getInstance('dropfilesModelTokens');
        $model->removeTokens();
        $session = JFactory::getSession();
        $sessionToken = $session->get('dropfilesToken', null);
        $viewfileanddowload = DropfilesBase::getAuthViewFileAndDownload();
        if ($sessionToken === null) {
            $token = $model->createToken();
            $session->set('dropfilesToken', $token);
        } else {
            $tokenId = $model->tokenExists($sessionToken);
            if ($tokenId) {
                $model->updateToken($tokenId);
                $token = $sessionToken;
            } else {
                $token = $model->createToken();
                $session->set('dropfilesToken', $token);
            }
        }
        if (!empty($items)) {
            $user = JFactory::getUser();
            $userId = (string) $user->id;
            if (is_array($items)) {
                foreach ($items as $key => &$item) {
                    if (!self::isUserCanViewFile($item)) {
                        unset($items[$key]);
                        continue;
                    }

                    if (isset($item->file) && strpos($item->file, 'http') !== false) {
                       // $item->link = $item->file;
                        $item->remoteurl = true;
                        $item->link = self::genUrl(
                            $item->id,
                            $category->id,
                            $category->title,
                            '',
                            $item->title . '.' . $item->ext
                        );
                    } else {
                        $item->remoteurl = false;
                        $item->link = self::genUrl(
                            $item->id,
                            $category->id,
                            $category->title,
                            '',
                            $item->title . '.' . $item->ext
                        );
                        $allowedgoogleext = 'pdf,ppt,pptx,doc,docx,xls,xlsx,dxf,ps,eps,xps,psd,tif,tiff,bmp,svg,pages,';
                        $allowedgoogleext .= 'ai,dxf,ttf,txt,mp3,mp4,png,gif,ico,jpeg,jpg';
                        if ($params->get('usegoogleviewer', 1) > 0 &&
                            in_array(
                                $item->ext,
                                explode(',', $params->get('allowedgoogleext', $allowedgoogleext))
                            )) {
                            $item->viewerlink = self::isMediaFile($item->ext) ?
                                self::genMediaViewerUrl($item->id, $category->id, $item->ext)
                                : self::genViewerUrl(
                                    $item->id,
                                    $category->id,
                                    $category->title,
                                    $token,
                                    $item->title . '.' . $item->ext
                                );
                        }
                    }
                    if (!$viewfileanddowload) {
                        $item->link = '#';
                    }

                    $item->link_download_popup = $item->link;

                    $item->created_time = JHtml::_('date', $item->created_time, $params->get('date_format', 'Y-m-d'));
                    $item->modified_time = JHtml::_('date', $item->modified_time, $params->get('date_format', 'Y-m-d'));
                    if (!isset($item->catid)) {
                        $item->catid = $category->id;
                    }
                    $item->versionNumber = $item->version;
                    if (isset($item->custom_icon) && $item->custom_icon) {
                        $pos = strpos($item->custom_icon, '#');
                        if ($pos !== false) {
                            $item->custom_icon =  substr($item->custom_icon, 0, $pos);
                        }
                        $image = new JImage(JPath::clean(JPATH_SITE . '/' . $item->custom_icon));
                        $result = $image->createThumbs(array('50x70'));
                        if (JPATH_SITE === '/') {
                            $item->custom_icon_thumb = JUri::root() . $result[0]->getPath();
                        } else {
                            $pat_replace = str_replace(JPATH_SITE, JUri::root(), $result[0]->getPath());
                            $item->custom_icon_thumb = str_replace('/\\', '/', $pat_replace);
                        }
                    }

                    if ((int) $params->get('open_pdf_in', 0) === 1 && $item->ext === 'pdf') {
                        $item->openpdflink = self::genUrl(
                            $item->id,
                            $category->id,
                            $category->title,
                            '',
                            $item->title . '.' . $item->ext,
                            false
                        );
                    }
                }
            } else {
                if (!self::isUserCanViewFile($items)) {
                    return array();
                }
                if (isset($items->file) && strpos($items->file, 'http') !== false) {
                    $items->link = $items->file;
                    $items->remoteurl = true;
                } else {
                    $items->remoteurl = false;
                    $items->link = self::genUrl(
                        $items->id,
                        $category->id,
                        $category->title,
                        '',
                        $items->title . '.' . $items->ext
                    );
                    $allowedgoogleext = 'pdf,ppt,pptx,doc,docx,xls,xlsx,dxf,ps,eps,xps,psd,tif,tiff,bmp,svg,pages,';
                    $allowedgoogleext .= 'ai,dxf,ttf,txt,mp3,mp4,png,gif,ico,jpeg,jpg';
                    if ($params->get('usegoogleviewer', 1) > 0 &&
                        in_array($items->ext, explode(
                            ',',
                            $params->get('allowedgoogleext', $allowedgoogleext)
                        ))) {
                        $items->viewerlink = self::isMediaFile($items->ext) ?
                            self::genMediaViewerUrl($items->id, $category->id, $items->ext)
                            : self::genViewerUrl(
                                $items->id,
                                $category->id,
                                $category->title,
                                $token,
                                $items->title . '.' . $items->ext
                            );
                    }
                }
                if (!$viewfileanddowload) {
                    $items->link = '#';
                }

                $items->link_download_popup = $items->link;

                $items->created_time = JHtml::_('date', $items->created_time, $params->get('date_format', 'Y-m-d'));
                $items->modified_time = JHtml::_('date', $items->modified_time, $params->get('date_format', 'Y-m-d'));
                if (!isset($items->catid)) {
                    $items->catid = $category->id;
                }
                $items->versionNumber = $items->version;

                if ($items->custom_icon) {
                    $pos = strpos($items->custom_icon, '#');
                    if ($pos !== false) {
                        $items->custom_icon =  substr($items->custom_icon, 0, $pos);
                    }
                    $image = new JImage(JPath::clean(JPATH_SITE . '/' . $items->custom_icon));
                    $result = $image->createThumbs(array('50x70'));
                    if (JPATH_SITE === '/') {
                        $items->custom_icon_thumb = JUri::root() . $result[0]->getPath();
                    } else {
                        $path_replace = str_replace(JPATH_SITE, JUri::root(), $result[0]->getPath());
                        $items->custom_icon_thumb = str_replace('/\\', '/', $path_replace);
                    }
                }
                if ((int) $params->get('open_pdf_in', 0) === 1 && $items->ext === 'pdf') {
                    $items->openpdflink = self::genUrl(
                        $items->id,
                        $category->id,
                        $category->title,
                        '',
                        $items->title . '.' . $items->ext,
                        false
                    );
                }
            }
        }

        return $items;
    }

    /**
     * Get mime type of a file extension
     *
     * @param string $ext File extension
     *
     * @return mixed|string
     * @since  version
     */
    public static function mimeType($ext)
    {

        $mime_types = array(
            //flash
            'swf'   => 'application/x-shockwave-flash',
            'flv'   => 'video/x-flv',
            // images
            'png'   => 'image/png',
            'jpe'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'jpg'   => 'image/jpeg',
            'gif'   => 'image/gif',
            'bmp'   => 'image/bmp',
            'ico'   => 'image/vnd.microsoft.icon',
            'tiff'  => 'image/tiff',
            'tif'   => 'image/tiff',
            'svg'   => 'image/svg+xml',
            'svgz'  => 'image/svg+xml',

            // audio
            'mid'   => 'audio/midi',
            'midi'  => 'audio/midi',
            'mp2'   => 'audio/mpeg',
            'mp3'   => 'audio/mpeg',
            'mpga'  => 'audio/mpeg',
            'aif'   => 'audio/x-aiff',
            'aifc'  => 'audio/x-aiff',
            'aiff'  => 'audio/x-aiff',
            'ram'   => 'audio/x-pn-realaudio',
            'rm'    => 'audio/x-pn-realaudio',
            'rpm'   => 'audio/x-pn-realaudio-plugin',
            'ra'    => 'audio/x-realaudio',
            'wav'   => 'audio/x-wav',
            'wma'   => 'audio/wma',

            //Video
            'mp4'   => 'video/mp4',
            'mpeg'  => 'video/mpeg',
            'mpe'   => 'video/mpeg',
            'mpg'   => 'video/mpeg',
            'mov'   => 'video/quicktime',
            'qt'    => 'video/quicktime',
            'rv'    => 'video/vnd.rn-realvideo',
            'avi'   => 'video/x-msvideo',
            'movie' => 'video/x-sgi-movie',
            '3gp'   => 'video/3gpp',
            'webm'  => 'video/webm',
            'ogv'   => 'video/ogg',
            //doc
            'pdf'   => 'application/pdf'

        );

        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } else {
            return 'application/octet-stream';
        }
    }

    /**
     * Sanitize a file name
     *
     * @param string  $filename File name
     * @param boolean $withext  With extension
     *
     * @return boolean|mixed|string false if failed string otherwise
     * @since  version
     */
    public static function makeSafeFilename($filename, $withext = true)
    {

        $replace = array(
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae', 'Å' => 'A', 'Æ' => 'A', 'Ă' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'a', 'ă' => 'a', 'æ' => 'ae',
            'þ' => 'b', 'Þ' => 'B',
            'Ç' => 'C', 'ç' => 'c', 'Č' => 'c',
            'Ď' => 'D', 'ď' => 'd',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ě' => 'E', 'ě' => 'e',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ğ' => 'G', 'ğ' => 'g',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'İ' => 'I', 'ı' => 'i', 'ì' => 'i', 'í' => 'i',
            'î' => 'i', 'ï' => 'i',
            'Ñ' => 'N', 'Ň' => 'N', 'ň' => 'n',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe', 'Ø' => 'O', 'ö' => 'oe', 'ø' => 'o',
            'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'Ř' => 'R', 'ř' => 'r',
            'Š' => 'S', 'š' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ş' => 's', 'ß' => 'ss',
            'ț' => 't', 'Ț' => 'T', 'Ť' => 'T', 'ť' => 't',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ů' => 'U', 'ů' => 'u',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue',
            'Ý' => 'Y',
            'ý' => 'y', 'ÿ' => 'y',
            'Ž' => 'Z', 'ž' => 'z'
        );
//        $chars = array_keys($replace);
        $name = strtr($filename, $replace);

        if ($withext) {
            //get last extension
            $exploded = explode('.', $name);
            $ext = $exploded[count($exploded) - 1];

            $name = substr($name, 0, strlen($name) - strlen($ext) - 1);
        } else {
            $ext = '';
        }
        $name = str_replace(array(' ', '&', '\'', ':', '/', '\\', '?'), '-', $name);
        // Keep latin character only
        $name = preg_replace('/[^A-Za-z0-9\-]/', '', $name);
        $name = preg_replace('/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $name);
        if (empty($name)) {
            if ($withext) {
                //get last extension
                $exploded = explode('.', $filename);
                $ext = $exploded[count($exploded) - 1];

                $filename = substr($filename, 0, strlen($filename) - strlen($ext) - 1);
            }
            $name = rawurlencode($filename);
        }

        if ($ext === '') {
            return $name;
        }
        return $name . '.' . $ext;
    }

    /**
     * Check current user can view file
     *
     * @param object $file File object
     *
     * @return boolean
     * @since  5.2.0
     */
    public static function isUserCanViewFile($file)
    {
        $dropfiles_params = JComponentHelper::getParams('com_dropfiles');
        if ($dropfiles_params->get('restrictfile', 0)) {
            $usersCanView = (isset($file->canview) && $file->canview !== '0' && $file->canview !== '') ? $file->canview : '';
            if ($usersCanView !== '') {
                $user         = JFactory::getUser();
                $user_id      = (string) $user->id;
                $usersCanView = explode(',', $usersCanView);
                if ($user_id) {
                    if (count($usersCanView) > 0 && !in_array($user_id, $usersCanView)) {
                        return false;
                    }
                } else {
                    if (is_array($usersCanView) && count($usersCanView) > 0) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get generated preview file
     *
     * @param string         $id    File id
     * @param integer        $catId Category id
     * @param string         $token Token key
     * @param string|boolean $path  Return path
     *
     * @throws Exception Fire if errors
     *
     * @return boolean|string
     */
    public static function getGeneratedPreviewUrl($id, $catId, $token = '', $path = false)
    {
        $params = JComponentHelper::getParams('com_dropfiles');
        $generatePreviewOption = $params->get('auto_generate_preview', 0);
        $securePreviewFileOption = $params->get('secure_preview_file', 0);

        if (!class_exists('DropfilesModelOptions')) {
            $optionModelPath = JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/options.php';
            require_once $optionModelPath;
        }

        $modelOption = new DropfilesModelOptions();
        $useGeneratedPreview = intval($generatePreviewOption) === 1 ? true : false;
        $securePreviewFile = intval($securePreviewFileOption) === 1 ? true : false;
        $rootUrl = JURI::root();
        $rootPath = JPATH_ROOT;
        if (is_numeric($id)) {
            $previewFilePath = $modelOption->get_option('_dropfiles_preview_file_path_' . $id);
        } else {
            //$id = str_replace('-', '!', $id);
            $previewFileInfo = $modelOption->get_option('_dropfilesAddon_preview_info_' . md5($id));
            $previewFilePath = is_array($previewFileInfo) && isset($previewFileInfo['path']) ? $previewFileInfo['path'] : false;
        }

        if ($useGeneratedPreview && $previewFilePath) {
            $previewFileUrl = $rootUrl . $previewFilePath;
            $previewFilePath = $rootPath . $previewFilePath;

            if (file_exists($previewFilePath)) {
                if (!$useGeneratedPreview || $path) {
                    return $previewFilePath;
                }
                if (!$securePreviewFile) {
                    return $previewFileUrl;
                } else {
                    return sprintf(
                        '%sindex.php?option=com_dropfiles&task=file.preview&dropfiles_category_id=%s&dropfiles_file_id=%s&token=%s',
                        $rootUrl,
                        $catId,
                        $id,
                        $token
                    );
                }
            }
        }

        return false;
    }

    /**
     * Display a custom pagination
     *
     * @param array  $args      Option args
     * @param string $form_name Form name
     *
     * @return array|string|boolean
     */
    public static function dropfiles_category_pagination(array $args = array(), $form_name = '') // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- For matching
    {
        $uri              = JUri::getInstance();
        $url              = $uri->toString();
        $total            = isset($args['total']) ? $args['total'] : 1;
        $current          = isset($args['current']) ? $args['current'] : 1;
        $sourceCategoryId = isset($args['sourcecat']) ? $args['sourcecat'] : 0;

        $args = array(
            'base'               => $url,
            'format'             => '',
            'total'              => $total,
            'current'            => $current,
            'sourcecat'          => $sourceCategoryId,
            'show_all'           => false,
            'prev_next'          => true,
            'prev_text'          => '&laquo; Previous',
            'next_text'          => 'Next &raquo;',
            'end_size'           => 1,
            'mid_size'           => 2,
            'type'               => 'plain',
            'add_args'           => array(),
            'add_fragment'       => '',
            'before_page_number' => '',
            'after_page_number'  => ''
        );
        if (!is_array($args['add_args'])) {
            $args['add_args'] = array();
        }

        // Who knows what else people pass in $args
        $total = (int)$args['total'];
        if ($total < 2) {
            return false;
        }
        $current = (int)$args['current'];
        $end_size = (int)$args['end_size']; // Out of bounds?  Make it the default.
        if ($end_size < 1) {
            $end_size = 1;
        }
        $mid_size = (int)$args['mid_size'];
        if ($mid_size < 0) {
            $mid_size = 2;
        }
        $add_args = $args['add_args'];
        $r = '';
        $page_links = array();
        $dots = false;
        if (isset($args['sourcecat']) && intval($args['sourcecat']) === 0) {
            $args['sourcecat'] = 'all_0';
        }
        if ($args['prev_next'] && $current && 1 < $current) :
            $link = str_replace('%_%', 2 === $current ? '' : $args['format'], $args['base']);
            $link = str_replace('%#%', $current - 1, $link);
            if ($add_args) {
                $link = self::add_query_arg($add_args, $link);
            }
            $link .= $args['add_fragment'];
            $page_link = "<a class='prev page-numbers' data-page='" . ($current - 1) . "' data-sourcecat='" . $args['sourcecat'] . "'>";
            $page_link .= $args['prev_text'] . '</a>';
            $page_links[] = $page_link;
        endif;
        for ($n = 1; $n <= $total; $n++) :
            if ($n === $current) :
                $page_link = "<span class='page-numbers current'>" . $args['before_page_number'];
                $page_link .= $n . $args['after_page_number'] . '</span>';
                $page_links[] = $page_link;
                $dots = true;
            else :
                if ($args['show_all'] ||
                    ($n <= $end_size || ($current && $n >= $current - $mid_size && $n <= $current + $mid_size) ||
                        $n > $total - $end_size)) :
                    $link = str_replace('%_%', 1 === $n ? '' : $args['format'], $args['base']);
                    $link = str_replace('%#%', $n, $link);
                    if ($add_args) {
                        $link = self::add_query_arg($add_args, $link);
                    }
                    $link .= $args['add_fragment'];
                    $page_link = "<a class='page-numbers' data-page='" . $n . "' data-sourcecat='" . $args['sourcecat'] . "'>" . $args['before_page_number'];
                    $page_link .= $n . $args['after_page_number'] . '</a>';
                    $page_links[] = $page_link;
                    $dots = true;
                elseif ($dots && !$args['show_all']) :
                    $page_links[] = '<span class="page-numbers dots">&hellip;</span>';
                    $dots = false;
                endif;
            endif;
        endfor;
        if ($args['prev_next'] && $current && ($current < $total || -1 === $total)) :
            $link = str_replace('%_%', $args['format'], $args['base']);
            $link = str_replace('%#%', $current + 1, $link);
            if ($add_args) {
                $link = self::add_query_arg($add_args, $link);
            }
            $link .= $args['add_fragment'];

            $page_link = "<a class='next page-numbers' data-page='" . ($current + 1) . "' data-sourcecat='" . $args['sourcecat'] . "'>";
            $page_link .= $args['next_text'] . '</a>';
            $page_links[] = $page_link;
        endif;
        switch ($args['type']) {
            case 'array':
                return $page_links;
            case 'list':
                $r .= "<ul class='page-numbers'>\n\t<li>";
                $r .= join("</li>\n\t<li>", $page_links);
                $r .= "</li>\n</ul>\n";
                break;
            default:
                $r .= "<div class='dropfiles-pagination'>\n\t";
                $r .= join("\n", $page_links);
                $r .= "\n</div>\n";
                break;
        }
        return $r;
    }

    // phpcs:ignore Squiz.Commenting.FunctionComment.MissingParamTag -- For matching
    /**
     * Retrieves a modified URL query string.
     *
     * You can rebuild the URL and append query variables to the URL query by using this function.
     * There are two ways to use this function; either a single key and value, or an associative array.
     *
     * Using a single key and value:
     *
     *     add_query_arg( 'key', 'value', 'http://example.com' );
     *
     * Using an associative array:
     *
     *     add_query_arg( array(
     *         'key1' => 'value1',
     *         'key2' => 'value2',
     *     ), 'http://example.com' );
     *
     * Omitting the URL from either use results in the current URL being used
     * (the value of `$_SERVER['REQUEST_URI']`).
     *
     * Values are expected to be encoded appropriately with urlencode() or rawurlencode().
     *
     * Setting any query variable's value to boolean false removes the key (see remove_query_arg()).
     *
     * Important: The return value of add_query_arg() is not escaped by default. Output should be
     * late-escaped with esc_url() or similar to help prevent vulnerability to cross-site scripting
     * (XSS) attacks.
     *
     * @param string|array ...$args Either a query variable key, or an associative array of query variables.
     *
     * @return string New URL query string (unescaped).
     */
    public static function add_query_arg(...$args) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- For matching
    {
        if (is_array($args[0])) {
            if (count($args) < 2 || false === $args[1]) {
                $uri = $_SERVER['REQUEST_URI'];
            } else {
                $uri = $args[1];
            }
        } else {
            if (count($args) < 3 || false === $args[2]) {
                $uri = $_SERVER['REQUEST_URI'];
            } else {
                $uri = $args[2];
            }
        }

        $frag = strstr($uri, '#');
        if ($frag) {
            $uri = substr($uri, 0, -strlen($frag));
        } else {
            $frag = '';
        }

        if (0 === stripos($uri, 'http://')) {
            $protocol = 'http://';
            $uri      = substr($uri, 7);
        } elseif (0 === stripos($uri, 'https://')) {
            $protocol = 'https://';
            $uri      = substr($uri, 8);
        } else {
            $protocol = '';
        }

        if (str_contains($uri, '?')) {
            list( $base, $query ) = explode('?', $uri, 2);
            $base                .= '?';
        } elseif ($protocol || ! str_contains($uri, '=')) {
            $base  = $uri . '?';
            $query = '';
        } else {
            $base  = '';
            $query = $uri;
        }

        self::dropfiles_parse_str($query, $qs);
        $qs = self::urlencode_deep($qs); // This re-URL-encodes things that were already in the query string.
        if (is_array($args[0])) {
            foreach ($args[0] as $k => $v) {
                $qs[ $k ] = $v;
            }
        } else {
            $qs[ $args[0] ] = $args[1];
        }

        foreach ($qs as $k => $v) {
            if (false === $v) {
                unset($qs[ $k ]);
            }
        }

        $ret = self::build_query($qs);
        $ret = trim($ret, '?');
        $ret = preg_replace('#=(&|$)#', '$1', $ret);
        $ret = $protocol . $base . $ret . $frag;
        $ret = rtrim($ret, '?');
        $ret = str_replace('?#', '#', $ret);
        return $ret;
    }

    /**
     * Parses a string into variables to be stored in an array.
     *
     * @param string $input_string The string to be parsed.
     * @param array  $result       Variables will be stored in this array.
     *
     * @return mixed|void
     */
    public static function dropfiles_parse_str($input_string, &$result) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- For matching
    {
        parse_str((string) $input_string, $result);
    }

    /**
     * Builds URL query based on an associative and, or indexed array.
     *
     * This is a convenient function for easily building url queries. It sets the
     * separator to '&' and uses _http_build_query() function.
     *
     * @param array $data URL-encode key/value pairs.
     *
     * @return string URL-encoded string.
     */
    public static function build_query($data) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- For matching
    {
        return self::_http_build_query($data, null, '&', '', false);
    }

    /**
     * From php.net (modified by Mark Jaquith to behave like the native PHP5 function).
     *
     * @param array|object $data      An array or object of data. Converted to array.
     * @param string       $prefix    Optional. Numeric index. If set, start parameter numbering with it.
     *                                Default null.
     * @param string       $sep       Optional. Argument separator; defaults to 'arg_separator.output'.
     *                                Default null.
     * @param string       $key       Optional. Used to prefix key name. Default empty string.
     * @param boolean      $urlencode Optional. Whether to use urlencode() in the result. Default true.
     *
     * @return string The query string.
     */
    public static function _http_build_query($data, $prefix = null, $sep = null, $key = '', $urlencode = true) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps, PSR2.Methods.MethodDeclaration.Underscore -- For matching
    {
        $ret = array();

        foreach ((array) $data as $k => $v) {
            if ($urlencode) {
                $k = urlencode($k);
            }

            if (is_int($k) && null !== $prefix) {
                $k = $prefix . $k;
            }

            if (! empty($key)) {
                $k = $key . '%5B' . $k . '%5D';
            }

            if (null === $v) {
                continue;
            } elseif (false === $v) {
                $v = '0';
            }

            if (is_array($v) || is_object($v)) {
                array_push($ret, self::_http_build_query($v, '', $sep, $k, $urlencode));
            } elseif ($urlencode) {
                array_push($ret, $k . '=' . urlencode($v));
            } else {
                array_push($ret, $k . '=' . $v);
            }
        }

        if (null === $sep) {
            $sep = ini_get('arg_separator.output');
        }

        return implode($sep, $ret);
    }

    /**
     * Navigates through an array, object, or scalar, and encodes the values to be used in a URL.
     *
     * @param mixed $value The array or string to be encoded.
     *
     * @return mixed The encoded value.
     */
    public static function urlencode_deep($value) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- For matching
    {
        return self::map_deep($value, 'urlencode');
    }

    /**
     * Maps a function to all non-iterable elements of an array or an object.
     *
     * @param mixed    $value    The array, object, or scalar.
     * @param callable $callback The function to map onto $value.
     *
     * @return mixed The value with the callback applied to all non-arrays and non-objects inside it.
     */
    public static function map_deep($value, $callback) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- For matching
    {
        if (is_array($value)) {
            foreach ($value as $index => $item) {
                $value[ $index ] = self::map_deep($item, $callback);
            }
        } elseif (is_object($value)) {
            $object_vars = get_object_vars($value);
            foreach ($object_vars as $property_name => $property_value) {
                $value->$property_name = self::map_deep($property_value, $callback);
            }
        } else {
            $value = call_user_func($callback, $value);
        }

        return $value;
    }
}
