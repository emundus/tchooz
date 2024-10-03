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

// no direct access
defined('_JEXEC') || die;

jimport('joomla.application.component.controllerform');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;

/**
 * Class DropfilesControllerConfig
 */
class DropfilesControllerConfig extends JControllerForm
{
    /**
     * Save param config
     *
     * @param null $key    Key
     * @param null $urlVar Url var
     *
     * @return boolean
     */
    public function save($key = null, $urlVar = null)
    {
        // Check for request forgeries.
        JSession::checkToken() || jexit(JText::_('JINVALID_TOKEN'));

        // Initialise variables.
        $app = JFactory::getApplication();
        $lang = JFactory::getLanguage();
        $model = $this->getModel();
        $data = $app->input->get('jform', array(), 'post', 'array');
        $context = $this->option . '.edit.' . $this->context;

        // Access check.
        $canDo = DropfilesHelper::getActions();
        if (!$canDo->get('core.edit')) {
            if ($canDo->get('core.edit.own')) {
                $category = $model->getItem(JFactory::getApplication()->input->getInt('id', 0));
                if ($category->created_user_id !== JFactory::getUser()->id) {
                    $this->exitStatus('not permitted');
                }
            } else {
                $this->exitStatus('not permitted');
            }
        }

        // Validate the posted data.
        // Sometimes the form needs some posted data, such as for plugins and modules.
        $form = $model->getForm($data, false);

        if (!$form) {
            $app->enqueueMessage($model->getError(), 'error');

            return false;
        }

        // Test whether the data is valid.
        $validData = $model->validate($form, $data);

        // Check for validation errors.
        if ($validData === false) {
            // Get the validation messages.
            $errors = $model->getErrors();

            // Push up to three validation messages out to the user.
            $errorCount = count($errors);
            for ($i = 0, $n = $errorCount; $i < $n && $i < 3; $i++) {
                if ($errors[$i] instanceof Exception) {
                    $app->enqueueMessage($errors[$i]->getMessage(), 'warning');
                } else {
                    $app->enqueueMessage($errors[$i], 'warning');
                }
            }

            // Save the data in the session.
            $app->setUserState($context . '.data', $data);

            // Redirect back to the edit screen.
            $url_redirect = 'index.php?option=' . $this->option . '&view=' . $this->view_item;
            $url_redirect .= $this->getRedirectToItemAppend(null, $urlVar);
            $this->setRedirect(JRoute::_($url_redirect, false));
            return false;
        }

        // Attempt to save the data.
        if (!$model->save($validData)) {
            // Save the data in the session.
            $app->setUserState($context . '.data', $validData);

            // Redirect back to the edit screen.
            $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()));
            $this->setMessage($this->getError(), 'error');
            $url_redirect = 'index.php?option=' . $this->option . '&view=' . $this->view_item . '&tmpl=component';
            $url_redirect .= $this->getRedirectToItemAppend(null, $urlVar);
            $this->setRedirect(JRoute::_($url_redirect, false));
            return false;
        }
        $recordId = $app->input->getInt($urlVar);
        $text_prefix = $this->text_prefix . ($recordId === 0 && $app->isClient('site') ? '_SUBMIT' : '') . '_SAVE_SUCCESS';
        $this->setMessage(
            JText::_(
                ($lang->hasKey($text_prefix)
                    ? $this->text_prefix
                    : 'JLIB_APPLICATION') . ($recordId === 0 && $app->isClient('site') ? '_SUBMIT' : '') . '_SAVE_SUCCESS'
            )
        );

        $app->setUserState($context . '.data', null);

        // Redirect to the list screen.
        $url_redirect = 'index.php?option=' . $this->option . '&view=' . $this->view_item . '&tmpl=component';
        $url_redirect .= $this->getRedirectToListAppend();
        $this->setRedirect(JRoute::_($url_redirect, false));

        // Invoke the postSave method to allow for the child class to access the model.
        $this->postSaveHook($model, $validData);
        return true;
    }

    /**
     * Get redirect to item append
     *
     * @param null   $recordId Record id
     * @param string $urlVar   Url var
     *
     * @return string
     */
    public function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
    {
        $app = JFactory::getApplication();
        $append = parent::getRedirectToItemAppend($recordId, $urlVar);

        $format = $app->input->get('format', 'raw');

        // Setup redirect info.
        if ($format) {
            $append .= '&format=' . $format;
        }
        return $append;
    }

    /**
     * Set theme
     *
     * @return void
     * @throws \Exception Throw when application can not start
     * @since  1.0
     */
    public function setTheme()
    {
        $app = JFactory::getApplication();
        $theme = $app->input->get('theme');
        $id = $app->input->getInt('id');


        $canDo = DropfilesHelper::getActions();
        if (!$canDo->get('core.edit')) {
            if ($canDo->get('core.edit.own')) {
                $modelC = $this->getModel('category');
                $category = $modelC->getItem($id);
                if ($category->created_user_id !== JFactory::getUser()->id) {
                    $this->exitStatus('not permitted');
                }
            } else {
                $this->exitStatus('not permitted');
            }
        }

        $themesObj = DropfilesBase::getDropfilesThemes();
        $themes = array();
        foreach ($themesObj as $value) {
            $themes[] = $value['id'];
        }

        if (!in_array($theme, $themes)) {
            $theme = 'default';
        }

        $model   = $this->getModel();
        $params  = $model->getParams($id);
        $params  = (isset($params)) ? (array)$params : array();
        $keep = array('group', 'access', 'refToFile', 'ordering', 'orderingdir');
        foreach ($params as $k => $v) {
            if (!in_array($k, $keep)) {
                unset($params[$k]);
            }
        }
        if (!empty($params)) {
            $params['setTheme'] = true;
        }
        $refToFile = (!empty($params)) ? json_encode($params) : '';
        if ($model->setTheme($theme, $id, $refToFile)) {
            $result = true;
        } else {
            $result = false;
        }
        echo json_encode($result);
        JFactory::getApplication()->close();
    }

    /**
     * Return a json response
     *
     * @param boolean $status Status
     * @param array   $datas  Array of datas to return with the json string
     *
     * @return void
     * @throws \Exception Throw when application can not start
     * @since  1.0
     */
    private function exitStatus($status, $datas = array())
    {
        $response = array('response' => $status, 'datas' => $datas);
        echo json_encode($response);
        JFactory::getApplication()->close();
    }

    /**
     * Logout Dropbox
     *
     * @return void
     * @since  1.0
     */
    public function logoutDropbox()
    {
        $canDo = DropfilesHelper::getActions();

        if (!$canDo->get('core.edit')) {
            $url_redirect = 'index.php?option=com_dropfiles&task=configuration.display';
            $this->setRedirect(JRoute::_($url_redirect, false), JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'));
            $this->redirect();
        }

        $Dropbox = new DropfilesDropbox();
        $Dropbox->logout();

        $this->setRedirect(JRoute::_('index.php?option=com_dropfiles&task=configuration.display', false));
        $this->redirect();
    }

    /**
     * Import jdownloads files from selected category
     *
     * @return void
     * @throws \Exception Throw when application can not start
     * @since  1.0
     */
    public function downimport()
    {
        $app = JFactory::getApplication();
        include_once JPATH_ADMINISTRATOR . '/components/com_dropfiles/controllers/category.php';
        $params = JComponentHelper::getParams('com_dropfiles');
        $allowedext_list = '7z,ace,bz2,dmg,gz,rar,tgz,zip,csv,doc,docx,html,key,keynote,odp,ods,odt,pages,pdf,pps,ppt,'
            . 'pptx,rtf,tex,txt,xls,xlsx,xml,bmp,exif,gif,ico,jpeg,jpg,png,psd,tif,tiff,aac,aif,aiff,alac,amr,au,cdda,'
            . 'flac,m3u,m4a,m4p, mid, mp3, mp4, mpa, ogg, pac, ra, wav, wma, 3gp,asf,avi,flv,m4v,mkv,mov,mpeg,mpg,'
            . 'rm,swf,vob,wmv';
        $allowed_ext = explode(',', $params->get('allowedext', $allowedext_list));
        foreach ($allowed_ext as $key => $value) {
            $allowed_ext[$key] = strtolower(trim($allowed_ext[$key]));
            if ($allowed_ext[$key] === '') {
                unset($allowed_ext[$key]);
            }
        }
        $id = JFactory::getApplication()->input->getInt('doccat');

        $path = JPATH_SITE . '/components/com_jdownloads/helpers/categories.php';
        $jDownloadV3 = true;
        if (is_file($path)) { // jDownload for J3
            include_once $path;
            JTable::addIncludePath(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jdownloads' . DS . 'tables');
            $path_admin_models = JPATH_ADMINISTRATOR . '/components/com_jdownloads/models/';
            JModelLegacy::addIncludePath($path_admin_models, 'jdownloadsModel');

            $categories = JDCategories::getInstance('jdownloads', '');
            $cat = $categories->get($id);
        } else {
            //jDownloads for J4
            $jDownloadV3 = false;
            $modelCategory = Factory::getApplication()->bootComponent('jdownloads')->getMVCFactory()
                ->createModel('Category', 'Administrator');
            $cat = $modelCategory->getItem($id);
        }


        if (is_object($cat)) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('c .*');
            $query->from('`#__jdownloads_categories` AS c');

            $rgt = $cat->rgt;
            $lft = $cat->lft;
            $query->where('c.lft >= ' . (int)$lft);
            $query->where('c.rgt <= ' . (int)$rgt);
            $query->order('c.lft ASC');

            $db->setQuery($query);
            $rows = $db->loadObjectList();

            $parent_id = 1;
            $mapping = array(); //jdownload cat => dropfiles cat
            if (count($rows)) {
                $catcontroller = new DropfilesControllerCategory();
                $app = JFactory::getApplication();
                $catpath = '';
                foreach ($rows as $category) {
                    $jdownload_parent = $category->parent_id;
                    if (isset($mapping[$jdownload_parent])) {
                        $parent_id = (int)$mapping[$jdownload_parent];
                    }
                    $db = JFactory::getDbo();
                    $query = $db->getQuery(true);
                    if ($jDownloadV3) {
                        // Select the required fields from the table.
                        $query->select('a.file_id, a.file_title, a.file_alias, a.description, a.file_pic, '
                            . 'a.price,a.release, a.cat_id, a.size, a.date_added, a.publish_from, a.modified_date,'
                            . 'a.publish_to, a.use_timeframe,a.url_download, a.other_file_id, a.extern_file,'
                            . 'a.downloads,a.extern_site, a.notes,a.access, a.language, a.checked_out,'
                            . 'a.checked_out_time, a.ordering, a.featured,a.published, a.asset_id');
                        $query->from('`#__jdownloads_files` AS a');

                        // Join over the users for the checked out user.
                        $query->select('uc . name AS editor');
                        $query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
                        // Join over the files for other selected file
                        $query->select('f.url_download AS other_file_name, f.file_title AS other_download_title');
                        $query->join('LEFT', $db->quoteName('#__jdownloads_files') .
                            ' AS f ON f.file_id = a.other_file_id');
                        // Join over the language
                        $query->select('l.title AS language_title');
                        $query->join('LEFT', $db->quoteName('#__languages') . ' AS l ON l.lang_code = a.language');

                        // Join over the asset groups.
                        $query->select('ag.title AS access_level');
                        $query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');
                        // Join over the categories.
                        $query->select('c.title AS category_title, c.parent_id AS category_parent_id');
                        $query->join('LEFT', '#__jdownloads_categories AS c ON c.id = a.cat_id');

                        $query->where('a.published = 1');


                        $query->where('a.cat_id = ' . $category->id);
                    } else {
                        // Select the required fields from the table.
                        $query->select('a.id, a.title as file_title, a.alias, a.description, a.file_pic, '
                            . 'a.price,a.release, a.catid, a.size, a.created as date_added, a.publish_up as publish_from, a.modified as modified_date,'
                            . 'a.publish_down as publish_to, a.use_timeframe,a.url_download, a.other_file_id, a.extern_file,'
                            . 'a.downloads,a.extern_site, a.notes,a.access, a.language, a.checked_out,'
                            . 'a.checked_out_time, a.ordering, a.featured,a.published, a.asset_id');
                        $query->from('`#__jdownloads_files` AS a');

                        // Join over the users for the checked out user.
                        $query->select('uc . name AS editor');
                        $query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
                        // Join over the files for other selected file
                        $query->select('f.url_download AS other_file_name, f.title AS other_download_title');
                        $query->join('LEFT', $db->quoteName('#__jdownloads_files') .
                            ' AS f ON f.id = a.other_file_id');
                        // Join over the language
                        $query->select('l.title AS language_title');
                        $query->join('LEFT', $db->quoteName('#__languages') . ' AS l ON l.lang_code = a.language');

                        // Join over the asset groups.
                        $query->select('ag.title AS access_level');
                        $query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

                        // Join over the categories.
                        $query->select('c.title AS category_title, c.parent_id AS category_parent_id');
                        $query->join('LEFT', '#__jdownloads_categories AS c ON c.id = a.catid');

                        $query->where('a.published = 1');


                        $query->where('a.catid = ' . $category->id);
                    }

                    $db->setQuery($query);

                    $downloads = $db->loadObjectList();

                    $downloaddir = JPATH_ROOT . '/jdownloads';

                    $datas = array();
                    $datas['jform']['extension'] = 'com_dropfiles';
                    $datas['jform']['title'] = $category->title;
                    $datas['jform']['alias'] = $category->alias . '-' . date('dmY-h-m-s', time());
                    $datas['jform']['parent_id'] = $parent_id;
                    $datas['jform']['published'] = 1;
                    $datas['jform']['language'] = '*';
                    $datas['jform']['metadata']['tags'] = '';

                    //Set state value to retreive the correct table
                    $model = $this->getModel('category');

                    $model->setState('category.extension', 'category');

                    foreach ($datas as $data => $val) {
                        $app->input->set($data, $val, 'POST');
                    }

                    if ($catcontroller->save()) {
                        $newId = $catcontroller->savedId;
                        $mapping[$category->id] = $newId;
                        $parent_id = $newId;

                        $file_dir = DropfilesBase::getFilesPath($newId);
                        if (!file_exists($file_dir)) {
                            JFolder::create($file_dir);
                            $data = '<html><body bgcolor="#FFFFFF"></body></html>';
                            JFile::write($file_dir . 'index.html', $data);
                            $data = 'deny from all';
                            JFile::write($file_dir . '.htaccess', $data);
                        }

                        $modelFiles = $this->getModel('files');
                        $user = JFactory::getUser();

                        $catpath = ($category->cat_dir_parent)? $category->cat_dir_parent. '/' . $category->cat_dir : $category->cat_dir ;
                        foreach ($downloads as $download) {
                            $publish = ($download->publish_from === '0000-00-00 00:00:00') ? $download->date_added : $download->publish_from;
                            $created_time = $download->date_added;
                            $modified_time = ($download->modified_date === '0000-00-00 00:00:00') ? $download->date_added : $download->modified_date;
                            if ($download->url_download !== '') {
                                if (!in_array(strtolower(JFile::getExt($download->url_download)), $allowed_ext)) {
                                    continue;
                                }

                                $newname = uniqid() . '.' . strtolower(JFile::getExt($download->url_download));

                                $downloadfile = $downloaddir . '/' . $catpath . '/' . $download->url_download;

                                if (file_exists($downloadfile)) {
                                    JFile::copy($downloadfile, $file_dir . $newname);
                                }

                                //Insert new image into database
                                $id_file = $modelFiles->addFile(array(
                                    'title' => $download->file_title,
                                    'description' => $download->description,
                                    'id_category' => $newId,
                                    'file' => $newname,
                                    'ext' => strtolower(JFile::getExt($download->url_download)),
                                    'size' => filesize($file_dir . $newname),
                                    'author' => $user->get('id'),
                                    'created_time' => $created_time,
                                    'modified_time' => $modified_time,
                                    'publish' => $publish
                                ));
                                if (!$id_file) {
                                    JFile::delete($file_dir . $newname);
                                }
                            } else {
                                $urlfile = pathinfo($download->extern_file);
                                $modelFiles->addFile(array(
                                    'title' => $download->file_title,
                                    'description' => $download->description,
                                    'id_category' => $newId,
                                    'file' => $download->extern_file,
                                    'ext' => $urlfile['extension'],
                                    'size' => $this->remoteFileSize($download->extern_file),
                                    'author' => $user->get('id'),
                                    'created_time' => $created_time,
                                    'modified_time' => $modified_time,
                                    'publish' => $publish
                                ));
                            }
                        }
                    }
                }
            }
        }

        $this->exitStatus('Done');
    }

    /**
     *  Import Files from eDocman
     *
     * @return void
     * @throws \Exception Throw when application can not start
     * @since  1.0
     */
    public function eDocImport()
    {
        $app = JFactory::getApplication();
        include_once JPATH_ADMINISTRATOR . '/components/com_dropfiles/controllers/category.php';

        $params = JComponentHelper::getParams('com_dropfiles');
        $allowedext_list = '7z,ace,bz2,dmg,gz,rar,tgz,zip,csv,doc,docx,html,key,keynote,odp,ods,odt,pages,pdf,pps,ppt,'
            . 'pptx,rtf,tex,txt,xls,xlsx,xml,bmp,exif,gif,ico,jpeg,jpg,png,psd,tif,tiff,aac,aif,aiff,alac,amr,au,cdda,'
            . 'flac,m3u,m4a,m4p, mid, mp3, mp4, mpa, ogg, pac, ra, wav, wma, 3gp,asf,avi,flv,m4v,mkv,mov,mpeg,mpg,'
            . 'rm,swf,vob,wmv';
        $allowed_ext = explode(',', $params->get('allowedext', $allowedext_list));
        foreach ($allowed_ext as $key => $value) {
            $allowed_ext[$key] = strtolower(trim($allowed_ext[$key]));
            if ($allowed_ext[$key] === '') {
                unset($allowed_ext[$key]);
            }
        }

        $id = JFactory::getApplication()->input->getInt('doccat');

        require_once JPATH_ADMINISTRATOR . '/components/com_edocman/libraries/rad/loader.php';
        JLoader::register('EDocmanModelList', JPATH_ROOT . '/components/com_edocman/model/list.php');

        $cats = EDocmanHelper::getChildrenCategories($id);
        if (count($cats)) {
            $parent_id = 1;
            $mapping = array(); //edoc cat => dropfiles cat
            $modelList = OSModel::getInstance('List', 'EDocmanModel', array('ignore_session' => true,
                'ignore_request' => true));
            $catcontroller = new DropfilesControllerCategory();
            $modelFiles = $this->getModel('files');
            $user = JFactory::getUser();
            $edocmandir = JPATH_ROOT . '/edocman/';

            foreach ($cats as $cat) {
                $cat = (int)$cat;
                $category = EDocmanHelper::getCategory($cat);
                $edoc_parent = $category->parent_id;
                if (isset($mapping[$edoc_parent])) {
                    $parent_id = (int)$mapping[$edoc_parent];
                }

                $datas = array();
                $datas['jform']['extension'] = 'com_dropfiles';
                $datas['jform']['title'] = $category->title;
                $datas['jform']['alias'] = $category->alias . '-' . date('dmY-h-m-s', time());
                $datas['jform']['parent_id'] = $parent_id;
                $datas['jform']['published'] = 1;
                $datas['jform']['language'] = '*';
                $datas['jform']['metadata']['tags'] = '';

                //Set state value to retreive the correct table
                $model = $this->getModel('category');
                $model->setState('category.extension', 'category');
                foreach ($datas as $data => $val) {
                    $app->input->set($data, $val, 'POST');
                }

                if ($catcontroller->save()) {
                    $newId = $catcontroller->savedId;
                    $mapping[$cat] = $newId;
                    $parent_id = $newId;

                    $file_dir = DropfilesBase::getFilesPath($newId);
                    if (!file_exists($file_dir)) {
                        JFolder::create($file_dir);
                        $data = '<html><body bgcolor="#FFFFFF"></body></html>';
                        JFile::write($file_dir . 'index.html', $data);
                        $data = 'deny from all';
                        JFile::write($file_dir . '.htaccess', $data);
                    }

                    $docs = $modelList
                        ->limitstart(0)
                        ->limit(0)
                        ->id($cat)
                        ->getData();

                    foreach ($docs as $document) {
                        if ($document->document_url !== '') {
                            $urlfile = pathinfo($document->document_url);
                            $des = $document->description;
                            if ($document->short_description !== '') {
                                $des = $document->short_description;
                            }
                            $modelFiles->addFile(array(
                                'title' => $document->title,
                                'description' => $des,
                                'id_category' => $newId,
                                'file' => $document->document_url,
                                'ext' => $urlfile['extension'],
                                'size' => $this->remoteFileSize($document->document_url),
                                'author' => $user->get('id')
                            ));
                        } else {
                            if (!in_array(strtolower(JFile::getExt($document->filename)), $allowed_ext)) {
                                continue;
                            }

                            $newname = uniqid() . '.' . strtolower(JFile::getExt($document->filename));

                            $docmanfile = $edocmandir . $document->filename;
                            if (file_exists($docmanfile)) {
                                JFile::copy($docmanfile, $file_dir . $newname);
                            }
                            //Insert new image into databse
                            $des = $document->description;
                            if ($document->short_description !== '') {
                                $des = $document->short_description;
                            }
                            $id_file = $modelFiles->addFile(array(
                                'title' => $document->title,
                                'description' => $des,
                                'id_category' => $newId,
                                'file' => $newname,
                                'ext' => strtolower(JFile::getExt($document->filename)),
                                'size' => filesize($file_dir . $newname),
                                'author' => $user->get('id')
                            ));
                            if (!$id_file) {
                                JFile::delete($file_dir . $newname);
                            }
                        }
                    } //end of foreach
                }
            }
        }
        $this->exitStatus('Done');
    }

    /**
     * Import docman files from selected category
     *
     * @return void
     * @throws \Exception Throw when application can not start
     * @since  1.0
     */
    public function docimport()
    {
        $app = JFactory::getApplication();
        include_once JPATH_ADMINISTRATOR . '/components/com_dropfiles/controllers/category.php';

        $params = JComponentHelper::getParams('com_dropfiles');
        $allowedext_list = '7z,ace,bz2,dmg,gz,rar,tgz,zip,csv,doc,docx,html,key,keynote,odp,ods,odt,pages,pdf,pps,ppt,'
            . 'pptx,rtf,tex,txt,xls,xlsx,xml,bmp,exif,gif,ico,jpeg,jpg,png,psd,tif,tiff,aac,aif,aiff,alac,amr,au,cdda,'
            . 'flac,m3u,m4a,m4p, mid, mp3, mp4, mpa, ogg, pac, ra, wav, wma, 3gp,asf,avi,flv,m4v,mkv,mov,mpeg,mpg,'
            . 'rm,swf,vob,wmv';
        $allowed_ext = explode(',', $params->get('allowedext', $allowedext_list));
        foreach ($allowed_ext as $key => $value) {
            $allowed_ext[$key] = strtolower(trim($allowed_ext[$key]));
            if ($allowed_ext[$key] === '') {
                unset($allowed_ext[$key]);
            }
        }

        $id = JFactory::getApplication()->input->getInt('doccat', 0);
        $dbo = JFactory::getDbo();
        $query = 'SELECT GROUP_CONCAT(r.descendant_id ORDER BY r.level ASC SEPARATOR \'/\') as path ,r.level'
            . ' FROM #__docman_category_relations as r INNER JOIN #__docman_categories as c  ON '
            . ' c.docman_category_id=r.ancestor_id  WHERE c.docman_category_id='
            . (int)$id . ' GROUP BY r.ancestor_id';

        $dbo->setQuery($query);

        $list = $dbo->loadObject();

        $cats = explode('/', $list->path);
        $cats = ArrayHelper::toInteger($cats);
        $query = 'SELECT r.*  FROM #__docman_category_relations as r ';
        $query .= ' WHERE r.level = 1 AND r.descendant_id IN (' . implode(',', $cats) . ') Order By r.ancestor_id';
        $dbo->setQuery($query);
        $cat_relations = $dbo->loadObjectList('descendant_id');

        if (count($cats)) {
            $parent_id = 1;
            $mapping = array(); //doc cat => dropfiles cat
            $app = JFactory::getApplication();
            $config = KObjectManager::getInstance()->getObject('com://admin/docman.model.entity.config');
            $docmandir = JPATH_ROOT . '/' . $config->document_path . '/' ;

            foreach ($cats as $catid) {
                $model = KObjectManager::getInstance()->getObject('com://admin/docman.model.documents')
                    ->enabled(1)
                    ->status('published')
                    ->category($catid)
                    ->limit(0)
                    ->sort('title')
                    ->direction('ASC');
                if (isset($cat_relations[$catid])) {
                    $doc_parent = $cat_relations[$catid]->ancestor_id;
                    if (isset($mapping[$doc_parent])) {
                        $parent_id = (int)$mapping[$doc_parent];
                    }
                }

                $documents = $model->fetch();
                $uri_docman_cate = 'com://admin/docman.model.categories';
                $cat = KObjectManager::getInstance()->getObject($uri_docman_cate)->id($catid)->fetch();
                $catcontroller = new DropfilesControllerCategory();

                $datas = array();
                $datas['jform']['extension'] = 'com_dropfiles';
                $datas['jform']['title'] = $cat->title;
                $datas['jform']['alias'] = $cat->slug . '-' . date('dmY-h-m-s', time());
                $datas['jform']['parent_id'] = $parent_id;
                $datas['jform']['published'] = 1;
                $datas['jform']['language'] = '*';
                $datas['jform']['metadata']['tags'] = '';

                //Set state value to retreive the correct table
                $model = $this->getModel('category');

                $model->setState('category.extension', 'category');

                foreach ($datas as $data => $val) {
                    $app->input->set($data, $val, 'POST');
                }

                if ($catcontroller->save()) {
                    $newId = $catcontroller->savedId;
                    $mapping[$catid] = $newId;
                    $parent_id = $newId;

                    $file_dir = DropfilesBase::getFilesPath($newId);
                    if (!file_exists($file_dir)) {
                        JFolder::create($file_dir);
                        $data = '<html><body bgcolor="#FFFFFF"></body></html>';
                        JFile::write($file_dir . 'index.html', $data);
                        $data = 'deny from all';
                        JFile::write($file_dir . '.htaccess', $data);
                    }

                    $modelFiles = $this->getModel('files');
                    $user = JFactory::getUser();

                    foreach ($documents as $document) {
                        if ($document->storage_type === 'remote') {
                            $urlfile = pathinfo($document->storage_path);
                            $modelFiles->addFile(array(
                                'title' => $document->title,
                                'description' => $document->description,
                                'id_category' => $newId,
                                'file' => $document->storage_path,
                                'ext' => $urlfile['extension'],
                                'size' => $this->remoteFileSize($document->storage_path),
                                'author' => $user->get('id')
                            ));
                        } elseif ($document->storage_type === 'file') {
                            if (!in_array(strtolower(JFile::getExt($document->storage_path)), $allowed_ext)) {
                                continue;
                            }

                            $newname = uniqid() . '.' . strtolower(JFile::getExt($document->storage_path));

                            $docmanfile = $docmandir . $document->storage_path;

                            if (file_exists($docmanfile)) {
                                JFile::copy($docmanfile, $file_dir . $newname);
                            }

                            //Insert new image into databse

                            $id_file = $modelFiles->addFile(array(
                                'title' => $document->title,
                                'description' => $document->description,
                                'id_category' => $newId,
                                'file' => $newname,
                                'ext' => strtolower(JFile::getExt($document->storage_path)),
                                'size' => filesize($file_dir . $newname),
                                'author' => $user->get('id')
                            ));
                            if (!$id_file) {
                                JFile::delete($file_dir . $newname);
                            }
                        }
                    }
                }
            }
        }
        $this->exitStatus('Done');
    }


    /**
     * Import Phoca download files from selected category
     *
     * @return void
     * @throws \Exception Throw when application can not start
     * @since  1.0
     */
    public function phocaDownloadImport()
    {
        $app = JFactory::getApplication();
        include_once JPATH_ADMINISTRATOR . '/components/com_dropfiles/controllers/category.php';

        $params = JComponentHelper::getParams('com_dropfiles');
        $allowedext_list = '7z,ace,bz2,dmg,gz,rar,tgz,zip,csv,doc,docx,html,key,keynote,odp,ods,odt,pages,pdf,pps,ppt,'
            . 'pptx,rtf,tex,txt,xls,xlsx,xml,bmp,exif,gif,ico,jpeg,jpg,png,psd,tif,tiff,aac,aif,aiff,alac,amr,au,cdda,'
            . 'flac,m3u,m4a,m4p, mid, mp3, mp4, mpa, ogg, pac, ra, wav, wma, 3gp,asf,avi,flv,m4v,mkv,mov,mpeg,mpg,'
            . 'rm,swf,vob,wmv';
        $allowed_ext = explode(',', $params->get('allowedext', $allowedext_list));
        foreach ($allowed_ext as $key => $value) {
            $allowed_ext[$key] = strtolower(trim($allowed_ext[$key]));
            if ($allowed_ext[$key] === '') {
                unset($allowed_ext[$key]);
            }
        }

        $id = $app->input->getInt('phocadownloadcat', 0);
        $listchid = array();
        $this->getAllChild((array)$id, $listchid);
        $listchid = array_merge((array)$id, $listchid);

        $dbo = JFactory::getDbo();
        $query = 'SELECT a.id,a.parent_id,a.title,a.alias FROM #__phocadownload_categories AS a ';
        $query .= ' WHERE a.id IN (' . implode(',', $listchid) . ') ORDER BY a.parent_id';
        $dbo->setQuery($query);
        $list = $dbo->loadObjectList();

        if (count($list)) {
            $catcontroller = new DropfilesControllerCategory();
            $catpath = '';

            $parent_id = 1;
            $mapping = array(); //doc cat => dropfiles cat
            $db = JFactory::getDbo();
            $app = JFactory::getApplication();
            foreach ($list as $cats) {
                $downloads = $this->getAllFilesPhocaByCat($db, $cats);
                $phoca_parent = $cats->parent_id;
                if (isset($mapping[$phoca_parent])) {
                    $parent_id = $mapping[$phoca_parent];
                } else {
                    $parent_id = 1;
                }

                $datas = array();
                $datas['jform']['extension'] = 'com_dropfiles';
                $datas['jform']['title'] = $cats->title;
                $datas['jform']['alias'] = $cats->alias . '-' . date('dmY-h-m-s', time());
                $datas['jform']['parent_id'] = $parent_id;
                $datas['jform']['published'] = 1;
                $datas['jform']['language'] = '*';
                $datas['jform']['metadata']['tags'] = '';

                //Set state value to retreive the correct table
                $model = $this->getModel('category');

                $model->setState('category.extension', 'category');

                foreach ($datas as $data => $val) {
                    $app->input->set($data, $val, 'POST');
                }

                if ($catcontroller->save()) {
                    $newId = $catcontroller->savedId;
                    $mapping[$cats->id] = $newId;


                    $file_dir = DropfilesBase::getFilesPath($newId);
                    if (!file_exists($file_dir)) {
                        JFolder::create($file_dir);
                        $data = '<html><body bgcolor="#FFFFFF"></body></html>';
                        JFile::write($file_dir . 'index.html', $data);
                        $data = 'deny from all';
                        JFile::write($file_dir . '.htaccess', $data);
                    }

                    $modelFiles = $this->getModel('files');
                    $user = JFactory::getUser();

                    $catpath .= '/' . $cats->title;
                    foreach ($downloads as $download) {
                        $downloaddir = JPATH_ROOT . '/phocadownload';
                        if (dirname($download->filename) !== '.') {
                            $downloaddir = $downloaddir . '/' . dirname($download->filename);
                        }
                        if ($download->filename !== '') {
                            if (!in_array(strtolower(JFile::getExt(basename($download->filename))), $allowed_ext)) {
                                continue;
                            }

                            $newname = uniqid() . '.' . strtolower(JFile::getExt(basename($download->filename)));

                            $downloadfile = $downloaddir . '/' . basename($download->filename);

                            if (file_exists($downloadfile)) {
                                JFile::copy($downloadfile, $file_dir . $newname);
                            }

                            //Insert new image into databse

                            $id_file = $modelFiles->addFile(array(
                                'title' => $download->title,
                                'description' => $download->description,
                                'id_category' => $newId,
                                'file' => $newname,
                                'ext' => strtolower(JFile::getExt(basename($download->filename))),
                                'size' => filesize($file_dir . $newname),
                                'author' => $user->get('id')
                            ));
                            if (!$id_file) {
                                JFile::delete($file_dir . $newname);
                            }
                        } else {
                            $urlfile = pathinfo($download->filename);
                            $modelFiles->addFile(array(
                                'title' => $download->title,
                                'description' => $download->description,
                                'id_category' => $newId,
                                'file' => basename($download->filename),
                                'ext' => $urlfile['extension'],
                                'size' => $this->remoteFileSize($download->filename),
                                'author' => $user->get('id')
                            ));
                        }
                    }
                }
            }
        }
        $this->exitStatus('Done');
    }

    /**
     * Get file size of a remote file
     *
     * @param string $url Url
     *
     * @return mixed|string
     * @since  1.0
     */
    protected function remoteFileSize($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_NOBODY => 1,
        ));

        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_exec($ch);

        $clen = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);

        if (!$clen || ((int) $clen === -1)) {
            return 'n/a';
        }

        return $clen;
    }

    /**
     * Get all id child
     *
     * @param array $id  List id
     * @param array $arr Array
     *
     * @return array
     * @since  1.0
     */
    private function getAllChild($id, &$arr)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('id,parent_id');
        $query->from('#__phocadownload_categories as c');
        $query->where('c.parent_id IN (' . implode(',', $id) . ')');
        $db->setQuery($query);
        $results = $db->loadColumn();
        if ($results) {
            $arr = array_merge($arr, $results);
            $this->getAllChild($results, $arr);
        } else {
            return $arr;
        }
    }


    /**
     * Get all files of category
     *
     * @param object $db  Database instance
     * @param object $cat Category
     *
     * @return mixed
     * @since  1.0
     */
    private function getAllFilesPhocaByCat($db, $cat)
    {
        $query = $db->getQuery(true);
        // Select the required fields from the table.
        $query->select('a.*');
        $query->from('`#__phocadownload` AS a');

        // Join over the language
        $query->select('l.title AS language_title');
        $query->join('LEFT', '`#__languages` AS l ON l.lang_code = a.language');

        // Join over the users for the checked out user.


        $query->select('uc.name AS editor');
        $query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');

        $query->select('uua.id AS uploaduserid, uua.username AS uploadusername, uua.name AS uploadname');
        $query->join('LEFT', '#__users AS uua ON uua.id=a.userid');

        // Join over the asset groups.
        $query->select('ag.title AS access_level');
        $query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

        // Join over the categories.
        $query->select('c.title AS category_title, c.id AS category_id');
        $query->join('LEFT', '#__phocadownload_categories AS c ON c.id = a.catid');

        $query->select('ua.id AS userid, ua.username AS username, ua.name AS usernameno');
        $query->join('LEFT', '#__users AS ua ON ua.id = a.owner_id');

        $query->where('a.catid = ' . (int)$cat->id);
        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Clone theme
     *
     * @return void
     * @throws \Exception Throw when application can not start
     * @since  1.0
     */
    public function clonetheme()
    {
        $fromTheme = JFactory::getApplication()->input->getString('fromtheme');
        $newTheme = JFactory::getApplication()->input->getString('newtheme');

        $newTheme = str_replace(' ', '_', $newTheme);
        $newTheme = preg_replace('/[^a-zA-Z0-9_]+/', '', $newTheme);
        $newTheme = strtolower($newTheme);

        $model = $this->getModel('config');

        $result = $model->cloneTheme($fromTheme, $newTheme);

        $this->exitStatus($result['success'], $result['message']);
    }

    /**
     * Google Stop watch changes
     *
     * @return void
     * @since  5.2
     */
    public function googleStopWatchChanges()
    {
        // Check for request forgeries.
        JSession::checkToken() || jexit(JText::_('JINVALID_TOKEN'));

        try {
            $app = JFactory::getApplication();
        } catch (Exception $ex) {
            $this->exitStatus(false, JText::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $params = JComponentHelper::getParams('com_dropfiles');
        $google_watch_changes = (int) $params->get('google_watch_changes', 1);

        if (!$google_watch_changes) {
            // Watch changes
            if (DropfilesCloudHelper::watchChanges()) {
                DropfilesComponentHelper::setParams(array('google_watch_changes' => 1));
            } else {
                DropfilesComponentHelper::setParams(array('google_watch_changes' => 0));
            }
        } else {
            // Cancel watch changes
            DropfilesCloudHelper::cancelWatchChanges();
            DropfilesComponentHelper::setParams(array('google_watch_changes' => 0));
        }
        $this->exitStatus(true);
        $app->close();
    }

    /**
     * Onedrive Business Stop watch changes
     *
     * @return void
     */
    public function onedriveBusinessStopWatchChanges()
    {
        try {
            $app = JFactory::getApplication();
        } catch (Exception $ex) {
            $this->exitStatus(false, JText::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        // Check for request forgeries.
        if (!JSession::checkToken()) {
            $this->exitStatus(false, JText::_('JINVALID_TOKEN'));
            $app->close();
        }

        $onedrive_business_watch_changes = (int) DropfilesComponentHelper::getParam('_dropfiles_onedrive_business_watch_changes', true);

        if (!$onedrive_business_watch_changes) {
            // Watch changes
            if (DropfilesCloudHelper::watchOnedriveBusinessChanges()) {
                DropfilesComponentHelper::setParam('_dropfiles_onedrive_business_watch_changes', true);
            } else {
                DropfilesComponentHelper::setParam('_dropfiles_onedrive_business_watch_changes', false);
            }
        } else {
            // Cancel watch changes
            DropfilesCloudHelper::cancelOnedriveBusinessWatchChanges();
            DropfilesComponentHelper::setParam('_dropfiles_onedrive_business_watch_changes', false);
        }

        header('Content-Type: application/json');
        $this->exitStatus(true);
        $app->close();
    }

    /**
     * Cloud automatic connect.
     *
     * @return void
     */
    public function cloudAutoConnect()
    {
        $path_cloudconnector = JPATH_ADMINISTRATOR . '/components/com_dropfiles/cloud-connector/CloudConnector.php';
        JLoader::register('DropfilesCloudConnector', $path_cloudconnector);
        $connector = new DropfilesCloudConnector();
        $connector->executeAction();
    }

    /**
     * List all categories to import from servers
     *
     * @return void
     */
    public function dropfilesListAllCategories()
    {
        $modelCategories = $this->getModel('categories');
        $dropfilesCategories = $modelCategories->getAllCategories();

        if (is_array($dropfilesCategories) && !empty($dropfilesCategories)) {
            foreach ($dropfilesCategories as $index => $category) {
                if (!isset($category->id) || (isset($category->id) && !is_numeric($category->id))) {
                    unset($dropfilesCategories[$index]);
                }
            }
            echo json_encode(array('data' => $dropfilesCategories, 'success' => true));
            jexit();
        } else {
            $dropfilesCategories = array();
            echo json_encode(array('data' => $dropfilesCategories, 'success' => false));
            jexit();
        }
    }

    /**
     * Import the server folders and files into dropfiles categories
     *
     * @throws Exception Fire if errors
     *
     * @return void
     */
    public function dropfilesRunImportServerFolders()
    {
        $list_import   = JFactory::getApplication()->input->getString('dropfiles_list_import');
        $categoryDisc  = JFactory::getApplication()->input->getString('server_category_disc');
        $importOption  = JFactory::getApplication()->input->getString('server_import_option');
        $exclude_terms = array();
        $existsTerms   = array();

        if (!is_array($list_import) || is_null($list_import)) {
            echo json_encode(array('success' => false, 'existsTerms' => $existsTerms));
            jexit();
        }

        if (!empty($list_import)) {
            if (in_array('', $list_import)) {
                $key_null = array_search('', $list_import);
                unset($list_import[$key_null]);
            }
            foreach ($list_import as $directory) {
                if ($directory !== '/') {
                    $path          = realpath(JPATH_ROOT . $directory);
                    $parent        = ($categoryDisc !== '') ? (int)$categoryDisc : 1;
                    if (!file_exists($path)) {
                        continue;
                    }

                    if (!in_array($path, $exclude_terms)) {
                        $inserted_sub_terms = $this->dropfilesImportCategoryFromServers($path, $parent, $importOption);
                        $exclude_terms      = array_merge($inserted_sub_terms['child_inserted'], $exclude_terms);
                        $existsTerms        = array_merge($inserted_sub_terms['existsTermList'], $existsTerms);
                    }
                }
            }
        }

        echo json_encode(array('success' => true, 'existsTerms' => $existsTerms));
        jexit();
    }

    /**
     * Recursive import each category with it's files into dropfiles
     *
     * @param string  $path         Directory path
     * @param integer $parent       Category parent
     * @param string  $importOption Advanced import option
     *
     * @throws Exception Fire if errors
     *
     * @return array
     */
    public function dropfilesImportCategoryFromServers($path, $parent = 1, $importOption = 'only_selected_folders')
    {
        $app            = JFactory::getApplication();
        $results        = JFactory::getApplication()->input->getString('dropfiles_list_import');
        $results        = $this->dropfilesMapImportPathList($results);
        $child_inserted = array();
        $existsTermList = array();
        $existsTermObj  = null;

        if (!class_exists('DropfilesControllerCategory')) {
            $categoryControllerPath = JPATH_ADMINISTRATOR . '/components/com_dropfiles/controllers/category.php';
            include_once $categoryControllerPath;
        }

        if (!class_exists('DropfilesControllerCategories')) {
            $categoriesControllerPath = JPATH_ADMINISTRATOR . '/components/com_dropfiles/controllers/categories.php';
            include_once $categoriesControllerPath;
        }

        if (!class_exists('DropfilesHelperfolder')) {
            $helperFolderPath = JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/dropfilesHelperFolder.php';
            include_once $helperFolderPath;
        }

        $helperFolder = new DropfilesHelperfolder();
        $name         = basename($helperFolder->untrailingslashit($path));

        // Import category into dropfiles category disc
        $title = !empty($name) ? $name : JText::_('COM_DROPFILES_MODEL_CATEGORY_DEFAULT_NAME');
        $datas = array();
        $datas['jform']['extension'] = 'com_dropfiles';
        $datas['jform']['title'] = $title;
        $datas['jform']['alias'] = $title . '-' . date('dmY-h-m-s', time());
        $datas['jform']['language'] = '*';
        $datas['jform']['metadata']['tags'] = '';

        // Set state value to retrieve the correct table
        $modelCategory = $this->getModel('category');
        $modelCategories = $this->getModel('categories');
        $modelCategory->setState('category.extension', 'com_dropfiles');

        foreach ($datas as $data => $val) {
            $app->input->set($data, $val, 'POST');
        }
        $app->input->set('id', null, 'POST');
        $table = $modelCategory->getTable();
        $categoryData = $app->input->get('jform', array(), 'array');
        property_exists($table, 'checked_out');

        // Determine the name of the primary key for the data.
        $key = $table->getKeyName();

        // To avoid data collisions the urlVar may be different from the primary key.
        $urlVar = $key;
        $recordId = $app->input->getInt($urlVar);

        // Populate the row id from the session.
        $categoryData[$key] = $recordId;
        $categoryForm = $modelCategory->getForm($categoryData, false);

        if (!$categoryForm) {
            return array();
        }

        // Test whether the data is valid.
        $validCategoryData = $modelCategory->validate($categoryForm, $categoryData);

        // Check for validation errors.
        if ($validCategoryData === false) {
            return array();
        }

        // Attempt to save the category data.
        $parent = (intval($parent) > 0) ? intval($parent) : 1;
        $validCategoryData['published'] = 1;
        $validCategoryData['metadesc'] = '';
        $validCategoryData['metakey'] = '';
        $validCategoryData['description'] = '';
        $validCategoryData['params'] = '';
        $validCategoryData['parent_id'] = $parent;
        $insertedCategoryId = $modelCategory->save($validCategoryData, true);

        if (empty($insertedCategoryId) || is_null($insertedCategoryId)) {
            return array();
        }

        // Ordering categories
        $this->dropfilesCategoryOrdering($insertedCategoryId, $parent, 'last-child');

        // Import files into the new created category
        $files = $this->dropfilesGetAllFileFromServerFolders($path, array());
        if (!empty($files)) {
            $this->dropfilesImportFiles((int)$insertedCategoryId, $files);
        }

        // Import sub categories
        $directories = glob($path . '/*', GLOB_ONLYDIR);
        if (!empty($directories)) {
            foreach ($directories as $direct) {
                if ($importOption === 'all_sub_folders') {
                    $child_inserted2  = $this->dropfilesImportCategoryFromServers($direct, (int)$insertedCategoryId, 'all_sub_folders');
                    $child_inserted   = array_merge($child_inserted2['child_inserted'], $child_inserted);
                    $child_inserted[] = $direct;
                    $existsTermList   = array_merge($child_inserted2['existsTermList'], $existsTermList);
                } else {
                    if (in_array($direct, $results)) {
                        $child_inserted2  = $this->dropfilesImportCategoryFromServers($direct, (int)$insertedCategoryId, 'only_selected_folders');
                        $child_inserted   = array_merge($child_inserted2['child_inserted'], $child_inserted);
                        $child_inserted[] = $direct;
                        $existsTermList   = array_merge($child_inserted2['existsTermList'], $existsTermList);
                    }
                }
            }
        }

        return array('child_inserted' => $child_inserted, 'existsTermList' => $existsTermList);
    }

    /**
     * List all files of the given directory from servers
     *
     * @param string $dir     Directory path
     * @param array  $results Contents
     *
     * @return array
     */
    public function dropfilesGetAllFileFromServerFolders($dir, $results = array())
    {
        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = $path;
            } elseif ($value !== '.' && $value !== '..') {
                $this->dropfilesGetAllFileFromServerFolders($path, $results);
                $results[] = $path;
            }
        }

        return $results;
    }

    /**
     * Import files from server to dropfiles category
     *
     * @param integer $categoryId Category id
     * @param array   $files      File list
     *
     * @throws Exception Fire if errors
     *
     * @return void
     */
    public function dropfilesImportFiles($categoryId, $files)
    {
        if ((int)$categoryId > 0) {
            $app = JFactory::getApplication();
            $file_dir = DropfilesBase::getFilesPath($categoryId);

            // Check folder exists
            if (!file_exists($file_dir)) {
                JFolder::create($file_dir);
                $data = '<html><body bgcolor="#FFFFFF"></body></html>';
                JFile::write($file_dir . 'index.html', $data);
                $data = 'deny from all';
                JFile::write($file_dir . '.htaccess', $data);
            }
            $params = JComponentHelper::getParams('com_dropfiles');
            $allowedext_list = '7z,ace,bz2,dmg,gz,rar,tgz,zip,csv,doc,docx,html,key,keynote,odp,ods,odt,pages,pdf,pps,ppt,'
                . 'pptx,rtf,tex,txt,xls,xlsx,xml,bmp,exif,gif,ico,jpeg,jpg,png,psd,tif,tiff,aac,aif,aiff,alac,amr,au,cdda,'
                . 'flac,m3u,m4a,m4p, mid, mp3, mp4, mpa, ogg, pac, ra, wav, wma, 3gp,asf,avi,flv,m4v,mkv,mov,mpeg,mpg,'
                . 'rm,swf,vob,wmv';
            $allowed = explode(',', $params->get('allowedext', $allowedext_list));
            $count = 0;
            $modelFiles = $this->getModel('files');
            $modelCategories = $this->getModel('Categories');
            $user = JFactory::getUser();

            // Process import files
            if (!empty($files)) {
                foreach ($files as $file) {
                    if (in_array(strtolower(JFile::getExt($file)), $allowed)) {
                        $newname = uniqid() . '.' . strtolower(JFile::getExt($file));
                        copy($file, $file_dir . $newname);
                        chmod($file_dir . $newname, 0777);
                        setlocale(LC_ALL, 'C.UTF-8');

                        // Insert new image into database when success
                        $id_file = $modelFiles->addFile(array(
                            'title'       => preg_replace('#\.[^.]*$#', '', basename($file)),
                            'state'       => '1',
                            'id_category' => $categoryId,
                            'file'        => $newname,
                            'ext'         => strtolower(JFile::getExt($file)),
                            'size'        => filesize($file_dir . $newname),
                            'author'      => $user->get('id')
                        ));

                        if (!$id_file) {
                            unlink($file_dir . $newname);
                        }
                        $count++;
                    }
                }

                // Update files counter
                $modelCategories->updateFilesCount();
            }
        }
    }

    /**
     * Return full of the directory path correctly
     *
     * @param array $importList List directory import
     *
     * @return array Result list
     */
    public function dropfilesMapImportPathList($importList)
    {
        $results = array();

        if (!empty($importList)) {
            foreach ($importList as $order) {
                if ($order !== '/') {
                    $folder_path = realpath(JPATH_ROOT . $order);
                    $results[] = $folder_path;
                }
            }
        }

        return $results;
    }

    /**
     * Ordering categories
     *
     * @param string|mixed   $pk       Current category id
     * @param integer|string $ref      Parent category id
     * @param string         $position Position to push
     *
     * @throws Exception Fire if errors
     *
     * @return void
     */
    public function dropfilesCategoryOrdering($pk = null, $ref = 1, $position = 'last-child')
    {
        $app = JFactory::getApplication();
        $modelCategory = $this->getModel('Category');
        $canDo = DropfilesHelper::getActions();

        if (!$canDo->get('core.edit')) {
            if ($canDo->get('core.edit.own')) {
                $category = $modelCategory->getItem($pk);
                if ($category->created_user_id !== JFactory::getUser()->id) {
                    $this->exitStatus('not permitted');
                }
            } else {
                $this->exitStatus('not permitted');
            }
        }

        if ((int) $ref === 0) {
            $ref = 1;
        }

        if ($position !== 'last-child') {
            $position = 'first-child';
        }

        $table = $modelCategory->getTable();
        $table->moveByReference($ref, $position, $pk);
    }

    /**
     * Save export params for exporting
     *
     * @throws Exception Fire if errors
     *
     * @return void
     */
    public function saveExportParams()
    {
        $export_type_values    = JFactory::getApplication()->input->getString('export_type', 'all');
        $include_folder_values = JFactory::getApplication()->input->getString('selected_categories', '');
        $supportTypes          = array('all', 'only_folder', 'selection_folder');

        if (!class_exists('DropfilesModelOptions')) {
            JLoader::register('DropfilesModelOptions', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/options.php');
        }

        if (!in_array($export_type_values, $supportTypes)) {
            jexit();
        }

        // Save export params for exporting
        $options = new DropfilesModelOptions();
        $options->update_option('dropfiles_export_folder_type', $export_type_values);
        if ($export_type_values === 'selection_folder' && !empty($include_folder_values)) {
            $options->update_option('dropfiles_export_selected_categories', explode(',', $include_folder_values));
        }
    }

    /**
     * Export dropfiles folders and files
     *
     * @throws Exception Fire if errors
     *
     * @return void
     */
    public function exportFolder()
    {
        if (!class_exists('DropfilesFilesHelper')) {
            JLoader::register('DropfilesFilesHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/files.php');
        }

        if (!class_exists('DropfilesModelOptions')) {
            JLoader::register('DropfilesModelOptions', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/options.php');
        }

        $dbo                    = JFactory::getDbo();
        $options                = new DropfilesModelOptions();
        $export_type            = $options->get_option('dropfiles_export_folder_type');
        $include_folders        = !is_null($options->get_option('dropfiles_export_selected_categories')) ? (array) json_decode($options->get_option('dropfiles_export_selected_categories')) : array();
        $supportTypes           = array('all', 'only_folder', 'selection_folder');
        $only_select_terms      = array();
        $only_select_folders_id = array();
        $folders                = array();
        $pendingFolders         = array();
        $terms                  = array();
        $files                  = array();
        $folders_id             = array();

        if (!in_array($export_type, $supportTypes)) {
            jexit();
        }

        if (is_null($export_type) || empty($export_type)) {
            $export_type = 'only_folder';
        }

        if (is_null($include_folders) || empty($include_folders)) {
            $include_folders = array();
        }

        if (empty($include_folders) || is_string($include_folders)) {
            $include_folders = array();
        }

        $modelCategory = $this->getModel('category');
        $modelCategories = $this->getModel('categories');
        $modelFiles    = $this->getModel('files');
        $config        = JFactory::getConfig();
        $siteName      = $config->get('sitename');

        if (!empty($siteName)) {
            $siteName .= '.';
        }

        $date        = date('Y-m-d');
        $xmlFileName = $siteName . 'dropfiles.' . $date . '.xml';

        if (is_null($xmlFileName) || empty($xmlFileName)) {
            $xmlFileName = 'Dropfiles.export.xml';
        }

        // Get categories
        switch ($export_type) {
            case 'all':
            case 'only_folder':
                $query = $dbo->getQuery(true);
                $query->select('c.*');
                $query->from('`#__categories` as c');
                $query->innerJoin('`#__dropfiles` as d');
                $query->where('c.id=d.id');
                $query->where('c.extension = "com_dropfiles"');
                $dbo->setQuery($query);
                $folders = $dbo->loadObjectList();
                break;
            case 'selection_folder':
                $query = $dbo->getQuery(true);
                $query->select('c.*');
                $query->from('`#__categories` as c');
                $query->where('c.extension = "com_dropfiles"');
                $query->where('c.id IN ('. implode(',', $include_folders) .')');
                $dbo->setQuery($query);
                $folders = $dbo->loadObjectList();
                break;
        }

        // Categories ordering for importing
        if (!empty($folders)) {
            while ($folder = array_shift($folders)) {
                if ((int) $folder->parent_id === 1 || isset($terms[$folder->parent_id])) {
                    $terms[$folder->id] = $folder;
                    $folders_id[] = $folder->id;
                } else {
                    if (isset($terms[$folder->parent_id])) {
                        $folders[] = $folder;
                    } else {
                        $pendingFolders[] = $folder;
                    }
                }
            }
        }

        // Process pending folders
        if ($export_type === 'selection_folder' && !empty($pendingFolders)) {
            foreach ($pendingFolders as $pendingFolder) {
                $terms[$pendingFolder->id] = $pendingFolder;
                $folders_id[] = $pendingFolder->id;
            }
        }

        // Export exists categories only
        if (empty($terms) || !is_array($terms) || count($terms) <= 0) {
            jexit(JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_EXPORT_FAILED'));
        }

        // Get file ids for exporting the file(s)
        switch ($export_type) {
            case 'all':
                $query = $dbo->getQuery(true);
                $query->select('id');
                $query->from('`#__dropfiles_files`');
                $dbo->setQuery($query);
                $post_ids = $dbo->loadAssocList();
                break;
            case 'selection_folder':
                foreach ($terms as $term) {
                    if (isset($term->id) && in_array($term->id, $include_folders)) {
                        $only_select_terms[$term->id] = $term;
                    }
                }
                $terms = $only_select_terms;
                if (!empty($folders_id)) {
                    foreach ($folders_id as $id) {
                        if (in_array($id, $include_folders)) {
                            $only_select_folders_id[] = $id;
                        }
                    }
                    $folders_id = $only_select_folders_id;
                }

                // Query get posts with selected terms
                $fQuery = 'SELECT f.id FROM #__dropfiles_files as f WHERE f.catid IN ('. implode(',', $folders_id) .')';
                $dbo->setQuery($fQuery);
                $post_ids = $dbo->loadAssocList();
                break;
            case 'only_folder':
            default:
                $post_ids = array();
                break;
        }

        // Export XML contents
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><chanel></chanel>');
        $record = $xml->addChild('site_name');
        $record->addChild('title', $siteName);
        $record = $xml->addChild('site_url');
        $record->addChild('link', JURI::root());
        $record = $xml->addChild('site_path');
        $record->addChild('path', JPATH_SITE);
        $record = $xml->addChild('site_language');
        $record->addChild('language', JFactory::getLanguage()->getName());

        // Export categories
        foreach ($terms as $term) {
            if (!isset($term->id)) {
                continue;
            }
            $params = $modelCategory->getCategoryParams($term->id);
            $params = !empty($params) ? json_encode($params) : '';
            $theme  = $modelCategory->getCategoryTheme($term->id);
            $record = $xml->addChild('category');
            $record->addChild('id', $term->id);
            $record->addChild('title', $term->title);
            $record->addChild('description', $term->description);
            $record->addChild('published', $term->published);
            $record->addChild('parent_id', $term->parent_id);
            $record->addChild('level', $term->level);
            $record->addChild('hits', $term->hits);
            $record->addChild('theme', $theme);
            $record->addChild('params', $params);
        }

        // Export all related files
        if ($post_ids) {
            $fileList = array();
            $post_ids = array_map(function ($postId) {
                return $postId['id'];
            }, $post_ids);
            foreach ($terms as $term) {
                if (is_null($term) || !isset($term->id)) {
                    continue;
                }
                $category = $modelCategory->getCategory($term->id);
                $categoryFiles = $modelFiles->getListOfCate($term->id);
                $categoryFiles = DropfilesFilesHelper::addInfosToFile($categoryFiles, $category);
                $fileList = array_merge($categoryFiles, $fileList);
            }

            foreach ($fileList as $file) {
                $files[$file->id] = $file;
            }

            // Fetch 20 files at a time for improving performance
            while ($nextPosts = array_splice($post_ids, 0, 20)) {
                $fetchFullFileQuery = 'SELECT * FROM #__dropfiles_files as f WHERE f.id IN ('. implode(',', $nextPosts) .')';
                $dbo->setQuery($fetchFullFileQuery);

                if (!$dbo->execute()) {
                    $attachments = array();
                } else {
                    $attachments = $dbo->loadObjectList();
                }

                foreach ($attachments as $attachment) {
                    // Remove file does not exists in queue
                    if (!key_exists($attachment->id, $files)) {
                        continue;
                    }
                    $link_download_file = (isset($files[$attachment->id]) && isset($files[$attachment->id]->link)) ? $files[$attachment->id]->link : '';
                    $record = $xml->addChild('item');
                    $record->addChild('id', $attachment->id);
                    $record->addChild('catid', $attachment->catid);
                    $record->addChild('title', htmlspecialchars($attachment->title));
                    $record->addChild('file', $attachment->file);
                    $record->addChild('state', $attachment->state);
                    $record->addChild('description', htmlspecialchars($attachment->description));
                    $record->addChild('ext', $attachment->ext);
                    $record->addChild('size', $attachment->size);
                    $record->addChild('hits', $attachment->hits);
                    $record->addChild('remoteurl', $attachment->remoteurl);
                    $record->addChild('version', $attachment->version);
                    $record->addChild('created_time', $attachment->created_time);
                    $record->addChild('modified_time', $attachment->modified_time);
                    $record->addChild('author', $attachment->author);
                    $record->addChild('file_tags', $attachment->file_tags);
                    $record->addChild('custom_icon', $attachment->custom_icon);
                    $record->addChild('file_multi_category', $attachment->file_multi_category);
                    $record->addChild('link_download', $link_download_file);
                }
            }
        }

        $xml->asXML($xmlFileName);
        while (ob_get_level() !== 0) {
            ob_end_clean();
        }
        header('Content-disposition: attachment; filename=' . $xmlFileName);
        header('Content-type: text/xml');
        readfile($xmlFileName);
        jexit();
    }

    /**
     * Import dropfiles folders and files to new site
     *
     * @throws Exception Fire if errors
     *
     * @return void
     */
    public function savefolderimportexportparams()
    {
        $app       = JFactory::getApplication();
        $dbo       = JFactory::getDbo();
        $importMsg = '';

        if (empty($_FILES) || !isset($_FILES['file'])) {
            $importMsg .= '<p><strong>'. JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_IMPORT_ERROR') .'</strong><br />';
            $importMsg .= JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_FILE_NOT_FOUND') .'</p>';

            echo json_encode(array('success' => false, 'msg' => $importMsg));
            jexit();
        }

        // Read XML contents
        $xmlFileType           = isset($_FILES['file']['type']) ? $_FILES['file']['type'] : '';
        $xmlFileError          = $_FILES['file']['error'];
        $insertedCategoriesIds = array();
        $insertedFileIds       = array();
        $missedCategoryIds     = array();
        $files                 = array();
        $xmlFileContents       = isset($_FILES['file']['tmp_name']) ? simplexml_load_file($_FILES['file']['tmp_name']) : '';
        $categoryDisc          = $app->input->getString('xml_category_disc', '0');
        $importFolderOnly      = $app->input->getString('import_only_folder', '1');
        $categoryModel         = $this->getModel('category');

        if ($xmlFileError || $xmlFileType !== 'text/xml') {
            $importMsg .= '<p><strong>'. JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_IMPORT_ERROR') .'</strong><br />';
            $importMsg .= JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_FILE_TYPE_VALID') .'</p>';

            echo json_encode(array('success' => false, 'msg' => $importMsg));
            jexit();
        }

        if (empty($xmlFileContents) || !is_object($xmlFileContents)) {
            $importMsg .= '<p><strong>'. JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_IMPORT_ERROR') .'</strong><br />';
            $importMsg .= JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_READ_FILE') .'</p>';

            echo json_encode(array('success' => false, 'msg' => $importMsg));
            jexit();
        }

        $xmlCategories = (isset($xmlFileContents->category) && !empty($xmlFileContents->category)) ? $xmlFileContents->category : array();
        $xmlFiles      = (isset($xmlFileContents->item) && !empty($xmlFileContents->item)) ? $xmlFileContents->item : array();

        if (empty($xmlFileContents->category)) {
            $importMsg .= '<p><strong>'. JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_IMPORT_ERROR') .'</strong><br />';
            $importMsg .= JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_NO_CATEGORY') .'</p>';

            echo json_encode(array('success' => false, 'msg' => $importMsg));
            jexit();
        }

        // Import categories to new site
        foreach ($xmlFileContents->category as $category) {
            $categoryId                         = htmlspecialchars($category->id, ENT_XML1, 'UTF-8');
            $parentCategoryId                   = htmlspecialchars($category->parent_id, ENT_XML1, 'UTF-8');
            $categoryTitle                      = isset($category->title) ? htmlspecialchars($category->title, ENT_XML1, 'UTF-8') : JText::sprintf('COM_DROPFILES_MODEL_CATEGORY_DEFAULT_NAME');
            $datas                              = array();
            $datas['jform']['extension']        = 'com_dropfiles';
            $datas['jform']['title']            = $categoryTitle;
            $datas['jform']['alias']            = $categoryTitle . '-' . date('dmY-h-m-s', time());
            $datas['jform']['language']         = '*';
            $datas['jform']['metadata']['tags'] = '';
            $modelCategory                      = $this->getModel('category');
            $modelCategory->setState('category.extension', 'com_dropfiles');

            foreach ($datas as $data => $val) {
                $app->input->set($data, $val, 'POST');
            }

            $app->input->set('id', null, 'POST');
            $table              = $modelCategory->getTable();
            $categoryData       = $app->input->get('jform', array(), 'array');
            property_exists($table, 'checked_out');
            $key                = $table->getKeyName();
            $urlVar             = $key;
            $recordId           = $app->input->getInt($urlVar);
            $categoryData[$key] = $recordId;
            $categoryForm       = $modelCategory->getForm($categoryData, false);

            if (!$categoryForm) {
                $missedCategoryIds[] = $categoryId;
                $importMsg .= '<p>'. JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_IMPORT_CATEGORY_FAILED') . ' ' . $categoryTitle .'</p>';
                continue;
            }

            $validCategoryData = $modelCategory->validate($categoryForm, $categoryData);

            if ($validCategoryData === false) {
                $missedCategoryIds[] = $categoryId;
                $importMsg .= '<p>'. JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_IMPORT_CATEGORY_FAILED') . ' ' . $categoryTitle .'</p>';
                continue;
            }

            $parent                           = (isset($insertedCategoriesIds[$parentCategoryId]) && intval($insertedCategoriesIds[$parentCategoryId]) > 1) ? $insertedCategoriesIds[$parentCategoryId] : 1;
            $parent                           = (intval($parent) === 1 && intval($categoryDisc) > 0) ? $categoryDisc : $parent;
            $validCategoryData['published']   = 1;
            $validCategoryData['metadesc']    = '';
            $validCategoryData['metakey']     = '';
            $validCategoryData['description'] = '';
            $validCategoryData['params']      = '';
            $validCategoryData['parent_id']   = $parent;
            $insertedCategoryId               = $modelCategory->save($validCategoryData, true);

            // Save related category params
            if ($insertedCategoryId) {
                $categoryThemeName = isset($category->theme) ? htmlspecialchars($category->theme, ENT_XML1, 'UTF-8') : 'default';
                $categoryParams    = isset($category->params) ? htmlspecialchars($category->params, ENT_XML1, 'UTF-8') : '';
                $categoryParams    = !empty($categoryParams) ? (array) json_decode($categoryParams) : null;

                $modelCategory->setTheme($insertedCategoryId, $categoryThemeName);

                if (!is_null($categoryParams) && !empty($categoryParams)) {
                    $modelCategory->setCategoryParams($insertedCategoryId, $categoryParams);
                }

                $insertedCategoriesIds[$categoryId] = $insertedCategoryId;
            }

            // Move reference for child categories
            if (intval($parent) > 1) {
                $this->dropfilesCategoryOrdering($insertedCategoryId, $parent, 'last-child');
            }
        }

        // Import file to disc category
        if (intval($importFolderOnly) === 0 && !empty($xmlFileContents->item)) {
            foreach ($xmlFileContents->item as $item) {
                $cateId = htmlspecialchars($item->catid, ENT_XML1, 'UTF-8');
                $fileMultipleCategory = htmlspecialchars($item->file_multi_category, ENT_XML1, 'UTF-8');

                if (!isset($cateId) || !array_key_exists($cateId, $insertedCategoriesIds)) {
                    continue;
                }

                if (!empty($fileMultipleCategory)) {
                    $fileMultipleCategory    = explode(',', $fileMultipleCategory);
                    $newFileMultipleCategory = array();
                    foreach ($fileMultipleCategory as $multipleCategory) {
                        if (!array_key_exists($multipleCategory, $insertedCategoriesIds)) {
                            continue;
                        }

                        $newFileMultipleCategory[] = $insertedCategoriesIds[$multipleCategory];
                    }
                    $newFileMultipleCategory = implode(',', $newFileMultipleCategory);
                }
                $fileMultipleCategory             = isset($newFileMultipleCategory) ? $newFileMultipleCategory : '';
                $newCategoryId                    = $insertedCategoriesIds[$cateId];
                $shortFile                        = array();
                $shortFile['id']                  = htmlspecialchars($item->id, ENT_XML1, 'UTF-8');
                $shortFile['title']               = htmlspecialchars($item->title, ENT_XML1, 'UTF-8');
                $shortFile['catid']               = $cateId;
                $shortFile['state']               = htmlspecialchars($item->state, ENT_XML1, 'UTF-8');
                $shortFile['ext']                 = htmlspecialchars($item->ext, ENT_XML1, 'UTF-8');
                $shortFile['size']                = htmlspecialchars($item->size, ENT_XML1, 'UTF-8');
                $shortFile['description']         = htmlspecialchars($item->description, ENT_XML1, 'UTF-8');
                $shortFile['hits']                = htmlspecialchars($item->hits, ENT_XML1, 'UTF-8');
                $shortFile['remoteurl']           = htmlspecialchars($item->remoteurl, ENT_XML1, 'UTF-8');
                $shortFile['custom_icon']         = htmlspecialchars($item->custom_icon, ENT_XML1, 'UTF-8');
                $shortFile['file_tags']           = htmlspecialchars($item->file_tags, ENT_XML1, 'UTF-8');
                $shortFile['version']             = htmlspecialchars($item->version, ENT_XML1, 'UTF-8');
                $shortFile['author']              = htmlspecialchars($item->author, ENT_XML1, 'UTF-8');
                $shortFile['file']                = htmlspecialchars($item->file, ENT_XML1, 'UTF-8');
                $shortFile['file_multi_category'] = $fileMultipleCategory;
                $shortFile['link_download']       = htmlspecialchars($item->link_download, ENT_XML1, 'UTF-8');

                $files[$newCategoryId][] = $shortFile;
            }

            $exportSitePath = isset($xmlFileContents->site_path->path) ? htmlspecialchars($xmlFileContents->site_path->path, ENT_XML1, 'UTF-8') : '';

            // Import file list for special category
            foreach ($files as $key => $value) {
                $fileResults = $this->dropfilesXMLFileImporting($key, $value, $importMsg, $exportSitePath);
                $importMsg .= (is_array($fileResults) && isset($fileResults['msg']) && !empty($fileResults['msg'])) ? $fileResults['msg'] : '';
                if (is_array($fileResults) && isset($fileResults['file_ids']) && !empty($fileResults['file_ids'])) {
                    $insertedFileIds = array_replace($fileResults['file_ids'], $insertedFileIds);
                }
            }

            // File multiple categories
            foreach ($insertedCategoriesIds as $newInsertedCategoryId) {
                $categoryParams = (array) $categoryModel->getCategoryParams($newInsertedCategoryId);
                $refToFile      = (isset($categoryParams['refToFile'])) ? (array) $categoryParams['refToFile'] : array();
                if (!empty($refToFile)) {
                    $newRefToFile = array();
                    foreach ($refToFile as $refCategoryId => $refFileIds) {
                        if (!array_key_exists($refCategoryId, $insertedCategoriesIds)) {
                            continue;
                        }
                        $newRefFileIds = array();
                        foreach ($refFileIds as $refFileId) {
                            if (!array_key_exists($refFileId, $insertedFileIds)) {
                                continue;
                            }
                            $newRefFileIds[] = $insertedFileIds[$refFileId];
                        }

                        $newRefToFile[$insertedCategoriesIds[$refCategoryId]] = $newRefFileIds;
                    }

                    $categoryParams['refToFile'] = $newRefToFile;

                    // Save params
                    $categoryModel->setCategoryParams($newInsertedCategoryId, $categoryParams);
                }
            }
        }

        $importMsg .= '<p>'. JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_IMPORT_DONE');
        $importMsg .= ' <a href="'. JURI::root()  .'administrator/index.php?option=com_dropfiles" class="have-fun">';
        $importMsg .= JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_IMPORT_HAVE_FUN') .'</a></p>';

        echo json_encode(array('success' => true, 'msg' => $importMsg));
        jexit();
    }

    /**
     * Import files from XML file to new site
     *
     * @param integer $categoryId     Category id
     * @param array   $files          File info
     * @param string  $importMsg      Import message
     * @param string  $exportSitePath Export site path
     *
     * @throws Exception Fire if errors
     *
     * @return string
     */
    public function dropfilesXMLFileImporting($categoryId, $files, $importMsg = '', $exportSitePath = '')
    {
        $app             = JFactory::getApplication();
        $file_dir        = DropfilesBase::getFilesPath($categoryId);
        $insertedFileIds = array();

        // Check folder exists
        if (!file_exists($file_dir)) {
            JFolder::create($file_dir);
            $data = '<html><body bgcolor="#FFFFFF"></body></html>';
            JFile::write($file_dir . 'index.html', $data);
            $data = 'deny from all';
            JFile::write($file_dir . '.htaccess', $data);
        }

        $params          = JComponentHelper::getParams('com_dropfiles');
        $allowedext_list = '7z,ace,bz2,dmg,gz,rar,tgz,zip,csv,doc,docx,html,key,keynote,odp,ods,odt,pages,pdf,pps,ppt,'
                         . 'pptx,rtf,tex,txt,xls,xlsx,xml,bmp,exif,gif,ico,jpeg,jpg,png,psd,tif,tiff,aac,aif,aiff,alac,amr,au,cdda,'
                         . 'flac,m3u,m4a,m4p,mid,mp3,mp4,mpa,ogg,pac,ra,wav,wma,3gp,asf,avi,flv,m4v,mkv,mov,mpeg,mpg,'
                         . 'rm,swf,vob,wmv';
        $allowed         = explode(',', $params->get('allowedext', $allowedext_list));
        $count           = 0;
        $modelFile       = $this->getModel('file');
        $modelFiles      = $this->getModel('files');
        $modelCategories = $this->getModel('Categories');
        $user            = JFactory::getUser();

        // Process import files
        if (!empty($files)) {
            foreach ($files as $file) {
                $fileTitle = htmlspecialchars_decode($file['title']);
                if (!isset($file['link_download']) || empty($file['link_download'])) {
                    $importMsg .= '<p>'. JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_IMPORT_FILE_URL') . ' ' . $fileTitle .'</p>';
                    continue;
                }

                if (in_array(strtolower($file['ext']), $allowed)) {
                    $newname      = uniqid() . '.' . strtolower($file['ext']);
                    $remoteURL    = $file['link_download'];
                    $httpcheck    = isset($file['file']) ? $file['file'] : '';
                    $isRemoteFile = preg_match('(http://|https://)', $httpcheck) ? true : false;
                    $content      = @file_get_contents($remoteURL); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- GET remote file only

                    if ($content !== false) {
                        file_put_contents($file_dir . $newname, $content);
                        chmod($file_dir . $newname, 0777);
                        setlocale(LC_ALL, 'C.UTF-8');

                        $insertFileParams = array(
                            'title'       => $fileTitle,
                            'state'       => isset($file['state']) ? $file['state'] : '1',
                            'id_category' => $categoryId,
                            'ext'         => $file['ext'] ? strtolower($file['ext']) : strtolower(filetype($file_dir . $newname)),
                            'size'        => filesize($file_dir . $newname) ? filesize($file_dir . $newname) : $file['size'],
                            'description' => htmlspecialchars_decode($file['description']),
                            'hits'        => $file['hits'],
                            'remoteurl'   => $file['remoteurl'],
                            'custom_icon' => $file['custom_icon'],
                            'file_tags'   => $file['file_tags'],
                            'author'      => $user->get('id')
                        );

                        // Remote file URL
                        $insertFileParams['file'] = $isRemoteFile ? $file['file'] : $newname;

                        // Insert new image into database when success
                        $id_file = $modelFiles->addFile($insertFileParams);

                        if (!$id_file) {
                            unlink($file_dir . $newname);
                            $importMsg .= '<p>'. JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_IMPORT_FILE_FAILED') . ' ' . $fileTitle .'</p>';
                        } else {
                            // Save related file version
                            if (isset($file['version']) && intval($file['version']) > 0) {
                                $modelFile->saveFileVersion($id_file, $file['version']);
                            }

                            // Save related file multiple category
                            if (isset($file['file_multi_category']) && !empty($file['file_multi_category'])) {
                                $modelFile->saveFileMultipleCategory($id_file, $file['file_multi_category']);
                            }

                            // Save related file custom icon
                            if (isset($file['custom_icon']) && !empty($file['custom_icon'])) {
                                $exportCustomIcon = JPath::clean($exportSitePath . '/' . $file['custom_icon']);
                                $customIconDisc   = JPath::clean(JPATH_SITE . '/' . $file['custom_icon']);
                                if (file_exists($exportCustomIcon) && !file_exists($customIconDisc)) {
                                    file_put_contents($customIconDisc, file_get_contents($exportCustomIcon));
                                }
                                $modelFile->saveFileCustomIcon($id_file, $file['custom_icon']);
                            }

                            $insertedFileIds[$file['id']] = $id_file;
                        }

                        $count++;
                    }
                } else {
                    $importMsg .= '<p>'. JText::_('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MSG_IMPORT_FILE_TYPE') . ' ' . $fileTitle .'</p>';
                }
            }

            // Update files counter
            $modelCategories->updateFilesCount();
        }

        return array('file_ids' => $insertedFileIds, 'msg' => $importMsg);
    }

    /**
     * Enable/Disable dropbox watch change option
     *
     * @throws Exception Fire if errors
     *
     * @return string|mixed|void
     */
    public function dropboxWatchChanges()
    {
        if (!class_exists('DropfilesFilesHelper')) {
            JLoader::register('DropfilesFilesHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/files.php');
        }

        if (!class_exists('DropfilesModelOptions')) {
            JLoader::register('DropfilesModelOptions', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/options.php');
        }

        $app                 = JFactory::getApplication();
        $options             = new DropfilesModelOptions();
        $dropboxWatchChanges = $options->get_option('dropbox_watch_changes', false);

        if ($dropboxWatchChanges === false || is_null($dropboxWatchChanges)) {
            $options->update_option('dropbox_watch_changes', true);
            $enable = true;
        } else {
            $options->update_option('dropbox_watch_changes', false);
            $enable = false;
        }

        echo json_encode(array('success' => true, 'enable' => $enable));
        jexit();
    }
}
