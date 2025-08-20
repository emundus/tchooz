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
use Joomla\CMS\Uri\Uri;

/**
 * DropfilesHelper class
 */
class DropfilesHelper
{

    /**
     * A cache for the available actions.
     *
     * @var JObject
     */
    protected static $actions;


    /**
     * Configure the Linkbar.
     *
     * @param string $vName The name of the active view.
     *
     * @return void
     * @since  1.6
     */
    public static function addSubmenu($vName)
    {
//      JSubMenuHelper::addEntry(
//          JText::_('COM_MESSAGES_ADD'),
//          'index.php?option=com_messages&view=message&layout=edit',
//          $vName == 'message'
//      );
//
//      JSubMenuHelper::addEntry(
//          JText::_('COM_MESSAGES_READ'),
//          'index.php?option=com_messages',
//          $vName == 'messages'
//      );
    }


    /**
     * Gets a list of the actions that can be performed.
     *
     * @return JObject
     *
     * @since 1.6
     * @todo  Refactor to work with notes
     */
    public static function getActions()
    {
        if (empty(self::$actions)) {
            // $actions = JAccess::getActions('com_dropfiles');
            self::$actions = ContentHelper::getActions('com_dropfiles', 'category');
        }

        return self::$actions;
    }


    /**
     * Dropfiles notification send mail
     *
     * @param string $email Email address
     * @param string $title Email title
     * @param string $body  Email body
     *
     * @return void
     * @since  version
     */
    public static function sendMail($email, $title, $body)
    {
        $config    = JFactory::getConfig();
        $from_name = $config->get('fromname');
        $from_mail = $config->get('mailfrom');
        $params    = JComponentHelper::getParams('com_dropfiles');

        if ($params->get('sender_name', 'Dropfiles') !== '') {
            $from_name = $params->get('sender_name', 'Dropfiles');
        }

        if ($params->get('sender_email', '') !== '') {
            $from_mail = $params->get('sender_email', '');
        }
        JFactory::getMailer()->sendMail($from_mail, $from_name, $email, $title, $body, true);
    }


    /**
     * Get super admins
     *
     * @return array|boolean
     * @since  version
     */
    public static function getSuperAdmins()
    {
        $dbo = JFactory::getDbo();
        $query = 'SELECT user_id FROM #__user_usergroup_map as usm JOIN #__users AS us ON usm.user_id = us.id ';
        $query .= ' WHERE usm.group_id=8 AND us.sendEmail = 1';
        $dbo->setQuery($query);
        if (!$dbo->execute()) {
            return false;
        }
        return $dbo->loadObjectList();
    }

    /**
     * Get Html email content
     *
     * @param string $fileName File name
     *
     * @return boolean|string
     * @since  version
     */
    public static function getHTMLEmail($fileName)
    {
        $file = JPATH_ROOT . '/administrator/components/com_dropfiles/assets/notifications/' . $fileName;
        return file_get_contents($file);
    }

    /**
     * Ordering multi category files
     *
     * @param array  $files      Files List
     * @param string $ordering   Ordering
     * @param string $direction  Ordering Direction
     * @param string $categoryId Category id
     *
     * @return array
     * @since  version
     */
    public static function orderingMultiCategoryFiles($files, $ordering, $direction, $categoryId = null)
    {
        if (!class_exists('DropfilesModelOptions')) {
            JLoader::register('DropfilesModelOptions', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/options.php');
        }

        if (!empty($files)) {
            $ordering = strtolower($ordering);
            $direction = strtolower($direction);
            switch ($ordering) {
                case 'ext':
                    usort($files, function ($first, $second) {
                        return (strtolower($first->ext) < strtolower($second->ext)) ? 1 : -1;
                    });
                    break;
                case 'size':
                    usort($files, function ($first, $second) {
                        return ((int)$first->size < (int)$second->size) ? 1 : -1;
                    });
                    break;
                case 'created_time':
                    usort($files, function ($first, $second) {
                        return (new DateTime($first->created_time) < new DateTime($second->created_time)) ? 1 : -1;
                    });
                    break;
                case 'modified_time':
                    usort($files, function ($first, $second) {
                        return (new DateTime($first->modified_time) < new DateTime($second->modified_time)) ? 1 : -1;
                    });
                    break;
                case 'version':
                    usort($files, function ($first, $second) {
                        return ($first->version < $second->version) ? 1 : -1;
                    });
                    break;
                case 'hits':
                    usort($files, function ($first, $second) {
                        return ((int)$first->hits < (int)$second->hits) ? 1 : -1;
                    });
                    break;
                case 'ordering':
                    $options = new DropfilesModelOptions();
                    $orderingList = !is_null($categoryId) ? (array) json_decode($options->get_option('dropfiles_custom_ordering_' . $categoryId)) : null;
                    if (!is_null($orderingList) && is_array($orderingList) && !empty($orderingList)) {
                        foreach ($files as $index => &$file) {
                            if (array_key_exists($file->id, $orderingList)) {
                                $file->ordering = $orderingList[$file->id];
                            }
                        }

                        usort($files, function ($first, $second) {
                            return (intval($first->ordering) < intval($second->ordering)) ? 1 : -1;
                        });
                    } else {
                        usort($files, function ($first, $second) {
                            return (strtolower($first->title) < strtolower($second->title)) ? 1 : -1;
                        });
                        break;
                    }
                    break;
                case 'title':
                default:
                    usort($files, function ($first, $second) {
                        return (strtolower($first->title) < strtolower($second->title)) ? 1 : -1;
                    });
                    break;
            }
            if ($direction === 'asc') {
                $files = array_reverse($files);
            }
        }

        return $files;
    }

    /**
     * Render a select html
     *
     * @param array   $options  Options array
     * @param string  $name     Name
     * @param string  $select   Select
     * @param string  $attr     Attr
     * @param boolean $disabled Disable
     *
     * @return string
     */
    public static function dropfilesSelect(array $options = array(), $name = '', $select = '', $attr = '', $disabled = false)
    {
        $html = '';
        $html .= '<select';
        if ($name !== '') {
            $html .= ' name="' . $name . '"';
        }
        if ($attr !== '') {
            $html .= ' ' . $attr;
        }
        $html .= '>';
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $select_option = '';
                if (is_array($select)) {
                    if (in_array($key, $select)) {
                        $select_option = 'selected="selected"';
                    } elseif ((string)$key === (string)$disabled) {
                        $select_option = self::disabled($disabled, $key, false);
                    } else {
                        $select_option = '';
                    }
                } else {
                    if ($disabled) {
                        $select_option = self::disabled($disabled, $key, false);
                    } else {
                        $select_option = self::selected($select, $key, false);
                    }
                }
                $html .= '<option value="' . $key . '" ' . $select_option . '>' . $value . '</option>';
            }
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Outputs the html disabled attribute.
     *
     * Compares the first two arguments and if identical marks as disabled
     *
     * @param mixed   $disabled One of the values to compare
     * @param mixed   $current  The other value to compare if not just true
     * @param boolean $echo     Whether to echo or just return the string
     *
     * @return string Html attribute or empty string
     *
     * @since 3.0.0
     */
    public static function disabled($disabled, $current = true, $echo = true)
    {
        return self::checkedSelectedHelper($disabled, $current, $echo, 'disabled');
    }

    /**
     * Outputs the html selected attribute.
     *
     * Compares the first two arguments and if identical marks as selected
     *
     * @param mixed   $selected One of the values to compare
     * @param mixed   $current  The other value to compare if not just true
     * @param boolean $echo     Whether to echo or just return the string
     *
     * @return string Html attribute or empty string
     *
     * @since 1.0.0
     */
    public static function selected($selected, $current = true, $echo = true)
    {
        return self::checkedSelectedHelper($selected, $current, $echo, 'selected');
    }

    /**
     * Private helper function for checked, selected, disabled and readonly.
     *
     * Compares the first two arguments and if identical marks as $type
     *
     * @param mixed   $helper  One of the values to compare
     * @param mixed   $current The other value to compare if not just true
     * @param boolean $echo    Whether to echo or just return the string
     * @param string  $type    The type of checked|selected|disabled|readonly we are doing
     *
     * @return string Html attribute or empty string
     *
     * @since  2.8.0
     * @access private
     */
    public static function checkedSelectedHelper($helper, $current, $echo, $type)
    {
        if ((string) $helper === (string) $current) {
            $result =  $type.'="'. $type .'"';
        } else {
            $result = '';
        }

        if ($echo) {
            echo $result;
        }

        return $result;
    }

    /**
     * Load script, style for media field
     *
     * @return void
     */
    public static function mediaFieldAssets()
    {
        $doc = JFactory::getDocument();
        $wam = JFactory::getDocument()->getWebAssetManager();
        $wam->useScript('webcomponent.media-select');
        $wam->useStyle('webcomponent.field-media')
            ->useScript('webcomponent.field-media');
        $doc->addScriptOptions('media-picker-api', ['apiBaseUrl' => Uri::base() . 'index.php?option=com_media&format=json']);

        if (count($doc->getScriptOptions('media-picker')) === 0) {
            $imagesExt = array_map(
                'trim',
                explode(
                    ',',
                    ComponentHelper::getParams('com_media')->get(
                        'image_extensions',
                        'bmp,gif,jpg,jpeg,png,webp'
                    )
                )
            );
            $audiosExt = array_map(
                'trim',
                explode(
                    ',',
                    ComponentHelper::getParams('com_media')->get(
                        'audio_extensions',
                        'mp3,m4a,mp4a,ogg'
                    )
                )
            );
            $videosExt = array_map(
                'trim',
                explode(
                    ',',
                    ComponentHelper::getParams('com_media')->get(
                        'video_extensions',
                        'mp4,mp4v,mpeg,mov,webm'
                    )
                )
            );
            $documentsExt = array_map(
                'trim',
                explode(
                    ',',
                    ComponentHelper::getParams('com_media')->get(
                        'doc_extensions',
                        'doc,odg,odp,ods,odt,pdf,ppt,txt,xcf,xls,csv'
                    )
                )
            );
            $doc->addScriptOptions('media-picker', array(
                'images' => $imagesExt,
                'audios' => $audiosExt,
                'videos' => $videosExt,
                'documents' => $documentsExt
            ));
        }
    }

    /**
     * Validate front task
     *
     * @param string $task Task
     *
     * @return boolean
     */
    public static function validateFrontTask($task)
    {
        if (!$task) {
            return  false;
        }
        if (strpos($task, 'googledrive.') === 0 || strpos($task, 'dropbox.') === 0
            || strpos($task, 'onedrive.') === 0 ||  strpos($task, 'onedrivebusiness.') === 0
            || strpos($task, 'frontdropbox.') === 0 ||  strpos($task, 'frontgoogle.') === 0
            || strpos($task, 'frontonedirve.') === 0 ||  strpos($task, 'frontonedrivebusiness.') === 0
            || strpos($task, 'categories.') === 0 ||  strpos($task, 'category.') === 0
            || strpos($task, 'files.') === 0 || strpos($task, 'file.') === 0 || strpos($task, 'frontfile.') === 0
        ) {
            return true;
        }
        return false;
    }

    /**
     * Pending file status
     *
     * @param string|integer $categoryId Category id
     *
     * @return boolean
     */
    public static function pendingUploadStatus($categoryId)
    {
        jimport('joomla.application.component.model');
        JModelLegacy::addIncludePath(JPATH_ROOT . '/components/com_dropfiles/models/', 'dropfilesModel');
        $model = JModelLegacy::getInstance('category', 'dropfilesModel');
        $category = $model->getItem($categoryId);
        $canDo = self::getActions();
        $isPending = true;
        if ($canDo->get('core.edit')) {
            $isPending = false;
        } else {
            if ($canDo->get('core.edit.own')
                && ((int) $category->created_user_id === (int) JFactory::getUser()->id)) {
                $isPending = false;
            }
        }

        return $isPending;
    }

    /**
     * Display single file
     *
     * @param string|integer $catID  Category ID
     * @param string|integer $fileID File ID
     *
     * @return string
     */
    public static function displaySingleFile($catID, $fileID)
    {
        $htmlContent = '' ;
        JModelLegacy::addIncludePath(JPATH_ROOT . '/components/com_dropfiles/models/', 'dropfilesModel');
        $modelCategory = JModelLegacy::getInstance('Frontcategory', 'dropfilesModel');
        $modelConfig = JModelLegacy::getInstance('Frontconfig', 'dropfilesModel');
        $modelFile              = JModelLegacy::getInstance('Frontfile', 'dropfilesModel');
        //  var_dump($catID);
        $dropfiles_params = JComponentHelper::getParams('com_dropfiles');
        if (!is_numeric($catID)) { //cloud id
            $catID = $modelCategory->getCategoryIDbyCloudId($catID);
        }

        $category = $modelCategory->getCategory((int)$catID);
        if (!$category) {
            return $htmlContent;
        }
        $user = JFactory::getUser();
        $params = $modelConfig->getParams($category->id);
        if ($dropfiles_params->get('categoryrestriction', 'accesslevel') === 'accesslevel') {
            if (!in_array((int)$category->access, $user->getAuthorisedViewLevels())) {
                return $htmlContent;
            }
        } else {
            // Check permission by user group
            $usergroup = isset($params->params->usergroup) ? $params->params->usergroup : array();

            $result = array_intersect($user->getAuthorisedGroups(), $usergroup);
            if (!count($result)) {
                return $htmlContent;
            }
        }

        if ($dropfiles_params->get('restrictfile', 0)) {
            $user_id = (int) $user->id;

            $canViewCategory = isset($params->params->canview) ? (int) $params->params->canview : 0;
            if ($user_id) {
                if (!($canViewCategory === $user_id || $canViewCategory === 0)) {
                    return $htmlContent;
                }
            } else {
                if ($canViewCategory !== 0) {
                    return $htmlContent;
                }
            }
        }

        if ($category->type === 'googledrive') {
            $modelGoogle = JModelLegacy::getInstance('Frontgoogle', 'dropfilesModel');
            $file        = $modelGoogle->getFile($fileID);
            $file->id    = $file->file_id;
            $file->file  = $file->title . '.' . $file->ext;
        } elseif ($category->type === 'dropbox') {
            $modelDropbox           = JModelLegacy::getInstance('Frontdropbox', 'dropfilesModel');
            $file = $modelDropbox->getFile($fileID);
        } elseif ($category->type === 'onedrive') {
            $modelOnedrive          = JModelLegacy::getInstance('Frontonedrive', 'dropfilesModel');
            $file = $modelOnedrive->getFile($fileID);
        } elseif ($category->type === 'onedrivebusiness') {
            $modelOnedriveBusiness  = JModelLegacy::getInstance('Frontonedrivebusiness', 'dropfilesModel');
            $file = $modelOnedriveBusiness->getFile($fileID);
        } else {
            $file = $modelFile->getFile((int)$fileID);
        }
        JLoader::register('DropfilesFilesHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/files.php');
        $path_dropfilesbase = JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesBase.php';
        JLoader::register('DropfilesBase', $path_dropfilesbase);
        DropfilesBase::loadLanguage();

        $file             = DropfilesFilesHelper::addInfosToFile(json_decode(json_encode($file), false), $category);
        if (!DropfilesFilesHelper::isUserCanViewFile($file)) {
            return '';
        }
        $theme = 'default';
        $params_arr = array(
            'file'     => $file,
            'category' => $category,
            'params'   => $params->params,
            'theme'    => $theme
        );
        $result     = DropfilesBase::onShowFrontFile($params_arr);

        if (!empty($result)) {
            $componentParams = JComponentHelper::getParams('com_dropfiles');
            $doc             = JFactory::getDocument();
            $doc->addStyleSheet(JURI::base('true') . '/components/com_dropfiles/assets/css/front_ver5.4.css');

            $bg_color       = $componentParams->get('singlebg', '#444444');
            $bg_hover_color = $componentParams->get('singlebghovercolor', '#444444');
            $hover_color    = $componentParams->get('singlehover', '#888888');
            $font_color     = $componentParams->get('singlefontcolor', '#ffffff');
            $singleStyle = '';

            if ($bg_color !== '') {
                $singleStyle .= '.dropfiles-single-file .dropfiles-file-link {
                    background-color: ' . $bg_color . '  !important;
                    font-family: "robotomedium", Georgia, serif;
                    font-size: 16px;
                    font-size: 1rem;
                }';
            }

            if ($bg_hover_color !== '') {
                $singleStyle .= '.dropfiles-single-file .dropfiles-file-link:hover {
                    background-color: ' . $bg_hover_color . '  !important;
                }';
            }

            if ($font_color !== '') {
                $singleStyle .= ' .dropfiles-single-file .dropfiles-file-link a,.dropfiles-single-file
                    .dropfiles-file-link a .droptitle {
                     color: ' . $font_color . '  !important;
                     text-decoration: none !important;
                }';
                $singleStyle .= ' .dropfiles-single-file .dropfiles-file-link a:hover {
                     background:  none !important;
                }';
            }

            if ($hover_color !== '') {
                $singleStyle .= ' .dropfiles-single-file .dropfiles-file-link a:hover,.dropfiles-single-file
                 .dropfiles-file-link a .droptitle:hover{
                    color: ' . $hover_color . '  !important;
                }';
            }

            $doc->addStyleDeclaration($singleStyle);
            $doc->addScriptDeclaration('dropfilesBaseUrl="' . JURI::base() . '";');

            $htmlContent .= $result;
        }

        return $htmlContent;
    }

    /**
     * Get theme of a category
     *
     * @param integer $catID Category ID
     *
     * @return string
     */
    public static function getCategoryTheme($catID)
    {
        JModelLegacy::addIncludePath(JPATH_ROOT . '/components/com_dropfiles/models/', 'dropfilesModel');
        $modelConfig = JModelLegacy::getInstance('Frontconfig', 'dropfilesModel');
        $params = $modelConfig->getParams($catID);
        $theme = 'default';
        if (!empty($params) && !empty($params->theme)) {
            $theme = $params->theme;
        }
        return $theme;
    }

    /**
     * Display category content
     *
     * @param string|integer $catID Category ID
     *
     * @return string
     */
    public static function displayCategory($catID)
    {

        $htmlContent = '' ;
        JModelLegacy::addIncludePath(JPATH_ROOT . '/components/com_dropfiles/models/', 'dropfilesModel');
        $modelCategory = JModelLegacy::getInstance('Frontcategory', 'dropfilesModel');
        $modelCategories = JModelLegacy::getInstance('Frontcategories', 'dropfilesModel');
        $modelConfig = JModelLegacy::getInstance('Frontconfig', 'dropfilesModel');
        $modelFiles = JModelLegacy::getInstance('Frontfiles', 'dropfilesModel');
        //  var_dump($catID);
        $dropfiles_params = JComponentHelper::getParams('com_dropfiles');

        if (!is_numeric($catID)) { //cloud id
            $catID = $modelCategory->getCategoryIDbyCloudId($catID);
        }
        $category = $modelCategory->getCategory((int)$catID);
        if (!$category) {
            return $htmlContent;
        }
        $modelFiles->getState('onsenfout'); //To autopopulate state
        $modelFiles->setState('filter.category_id', $category->id);
        $modelCategories->getState('onsenfout'); //To autopopulate state
        $modelCategories->setState('category.id', $category->id);
        $categories = $modelCategories->getItems();
        $params = $modelConfig->getParams($category->id);
        $user = JFactory::getUser();

        if ($dropfiles_params->get('categoryrestriction', 'accesslevel') === 'accesslevel') {
            $modelFiles->setState('filter.access', true);
            if (!in_array((int)$category->access, $user->getAuthorisedViewLevels())) {
                return $htmlContent;
            }
        } else {
            // Check permission by user group
            $modelFiles->setState('filter.access', false);
            $usergroup = isset($params->params->usergroup) ? $params->params->usergroup : array();

            $result = array_intersect($user->getAuthorisedGroups(), $usergroup);
            if (!count($result)) {
                return $htmlContent;
            }
        }

        if ($dropfiles_params->get('restrictfile', 0)) {
            $user_id = (int) $user->id;

            $canViewCategory = isset($params->params->canview) ? (int) $params->params->canview : 0;
            if ($user_id) {
                if (!($canViewCategory === $user_id || $canViewCategory === 0)) {
                    return $htmlContent;
                }
            } else {
                if ($canViewCategory !== 0) {
                    return $htmlContent;
                }
            }
        }

        if (isset($params->params->ordering)) {
            $ordering = $params->params->ordering;
        } else {
            $ordering = 'ordering';
        }
        if (isset($params->params->orderingdir)) {
            $direction = $params->params->orderingdir;
        } else {
            $direction = 'asc';
        }

        if ($category->type === 'googledrive') {
            $modelGoogle            = JModelLegacy::getInstance('Frontgoogle', 'dropfilesModel');
            $path_dropfilesgoogle = JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesGoogle.php';

            $files = $modelGoogle->getItems($category->cloud_id, $ordering, $direction);
            if ($files === false) {
                JLoader::register('DropfilesGoogle', $path_dropfilesgoogle);
                $google = new DropfilesGoogle();
                JFactory::getApplication()->enqueueMessage($google->getLastError(), 'error');
                return '';
            }
        } elseif ($category->type === 'dropbox') {
            $modelDropbox = JModelLegacy::getInstance('Frontdropbox', 'dropfilesModel');
            $files = $modelDropbox->getItems($category->cloud_id, $ordering, $direction);
        } elseif ($category->type === 'onedrive') {
            $modelOnedrive = JModelLegacy::getInstance('Frontonedrive', 'dropfilesModel');
            $files = $modelOnedrive->getItems($category->cloud_id, $ordering, $direction);
        } elseif ($category->type === 'onedrivebusiness') {
            $modelOnedriveBusiness = JModelLegacy::getInstance('Frontonedrivebusiness', 'dropfilesModel');
            $files = $modelOnedriveBusiness->getItems($category->cloud_id, $ordering, $direction);
        } else {
            if (isset($params->params->ordering)) {
                $modelFiles->setState('list.ordering', $params->params->ordering);
            }
            if (isset($params->params->orderingdir)) {
                $modelFiles->setState('list.direction', $params->params->orderingdir);
            }
            //to make storeid different to avoid duplicate results
            $modelFiles->setState('list.limit', 1000 * $catID);

            $subparams   = (array) $params->params;
            $lstAllFile  = null;
            $ordering    = (isset($params->params->ordering)) ? $params->params->ordering : '';
            $orderingdir = (isset($params->params->orderingdir)) ? $params->params->orderingdir : '';
            if (!empty($subparams) && isset($subparams['refToFile'])) {
                //  var_dump($subparams['refToFile']);
                if (!empty($subparams['refToFile'])) {
                    $listCatRef = $subparams['refToFile'];
                    $lstAllFile = self::getAllFileRef($modelFiles, $listCatRef, $ordering, $orderingdir);
                }
            }

            $files = $modelFiles->getItems();
            if (!empty($lstAllFile) && $lstAllFile !== null) {
                $files = array_merge($lstAllFile, $files);
                if (isset($params->params->ordering) && isset($params->params->orderingdir)) {
                    $ordering   = $params->params->ordering;
                    $direction  = $params->params->orderingdir;
                    $files      = self::orderingMultiCategoryFiles($files, $ordering, $direction, $category->id);
                }
            }
        }
        JLoader::register('DropfilesFilesHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/files.php');
        $path_dropfilesbase = JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesBase.php';
        JLoader::register('DropfilesBase', $path_dropfilesbase);
        $files = DropfilesFilesHelper::addInfosToFile($files, $category);

        if (!empty($params) && !empty($params->theme)) {
            $theme = $params->theme;
        } else {
            $theme = 'default';
        }

        $componentParams = JComponentHelper::getParams('com_dropfiles');

        if ((int) $componentParams->get('loadthemecategory', 1) === 0) {
            $params->params = self::loadParams($theme, $params->params, $componentParams);
        }

        if ($theme === 'default') {
            $columns = (int) $params->params->columns;

            // Check default columns value
            if ($columns === 0) {
                $columns = 2;
            }
        }

        JPluginHelper::importPlugin('dropfilesthemes');
        $app = JFactory::getApplication();
        $result = $app->triggerEvent('onShowFrontCategory', array(array('files' => $files,
            'category' => $category,
            'categories' => $categories,
            'params' => is_object($params) ? $params->params : '',
            'theme' => $theme,
            'columns'    => isset($columns) ? $columns : 2,
        )
        ));

        if (!empty($result[0])) {
            if (DropfilesBase::isJoomla40()) {
                JHtml::_('behavior.core');
            } else {
                JHtml::_('behavior.framework', true);
            }

            $doc = JFactory::getDocument();
            $doc->addStyleSheet(JURI::base('true') . '/components/com_dropfiles/assets/css/front_ver5.4.css');
            if ((int) $componentParams->get('usegoogleviewer', 1) === 1) {
                JHtml::_('jquery.framework');

                $doc->addStyleSheet(JURI::base('true') . '/components/com_dropfiles/assets/css/video-js.css');
                $doc->addScript(JURI::base('true') . '/components/com_dropfiles/assets/js/video.js');
                $doc->addScript(JURI::base('true') . '/components/com_dropfiles/assets/js/colorbox.init.js');
            }
            $doc->addScriptDeclaration('dropfilesBaseUrl="' . JURI::base() . '";');
            $doc->addScriptDeclaration('dropfilesRootUrl="' . JURI::root(true) . '/";');
            $htmlContent .= $result[0];
        }

        return $htmlContent;
    }

    /**
     * Load Params theme
     *
     * @param string $theme         Theme name
     * @param null   $cat_params    Category params
     * @param null   $global_params Global params
     *
     * @return stdClass
     */
    public static function loadParams($theme = 'default', $cat_params = null, $global_params = null)
    {
        $ob = new stdClass();
        if ($theme === '') {
            $theme = 'default';
        }
        foreach ((array)$cat_params as $key => $val) {
            $ob->$key = $global_params->get($theme . '_' . $key, $val);
        }

        return $ob;
    }

    /**
     * Get all file referent
     *
     * @param object $model       Files model
     * @param array  $listCatRef  List category
     * @param string $ordering    Ordering
     * @param string $orderingdir Ordering direction
     *
     * @return array
     */
    public static function getAllFileRef($model, $listCatRef, $ordering, $orderingdir)
    {
        $lstAllFile = array();
        foreach ($listCatRef as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $lstFile    = $model->getFilesRef($key, $value, $ordering, $orderingdir);
                $lstAllFile = array_merge($lstFile, $lstAllFile);
            }
        }
        return $lstAllFile;
    }


    /**
     * Method to get a list of cats
     *
     * @return array  The field option objects.
     *
     * @since 3.1
     */
    public static function dropfilesCatList()
    {
        $listArray = array('' => 'Select category');

        JLoader::register('DropfilesHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/dropfiles.php');
        $path_model = JPATH_ROOT . '/administrator/components/com_dropfiles/models/';
        JModelLegacy::addIncludePath($path_model, 'DropfilesModelCategories');
        $modelCategories = JModelLegacy::getInstance('categories', 'dropfilesModel');
        $all_cats = $modelCategories->getAllCategories();
        $totalCat = count($all_cats);
        for ($i = 0; $i < $totalCat; $i++) {
            $listArray[$all_cats[$i]->id] = str_repeat('-', ($all_cats[$i]->level - 1)) . ' ' . $all_cats[$i]->title;
        }

        return $listArray;
    }
}
