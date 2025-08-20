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

/**
 * Class DropfilesControllerFrontdropbox
 */
class DropfilesControllerFrontdropbox extends JControllerLegacy
{
    /**
     * Debug mode
     *
     * @var string
     */
    public $debug = false;

    /**
     * Dropbox sync file
     *
     * @return void
     * @since  version
     */
    public function index()
    {
        $model = $this->getModel();
        $dropboxCats = $model->getAllDropboxCategories();
        $path_dropfilesDropbox = JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesDropbox.php';
        JLoader::register('DropfilesDropbox', $path_dropfilesDropbox);
        $dropbox = new DropfilesDropbox();
        if (!$dropbox->checkAuth()) {
            $files_del = array();
            $gFilesInDb = $model->getAllDropboxFilesInDb();

            foreach ($dropboxCats as $dropboxcat) {
                $files = $dropbox->listDropboxFiles($dropboxcat->path);

                $files_new = array();
                if (!empty($files)) {
                    foreach ($files as $file) {
                        $files_new[$file['id']] = $file;
                    }
                }

                if (isset($gFilesInDb[$dropboxcat->cloud_id])) {
                    $files_diff_add = array_diff_key($files_new, $gFilesInDb[$dropboxcat->cloud_id]);
                    $files_diff_del = array_diff_key($gFilesInDb[$dropboxcat->cloud_id], $files_new);
                    $files_update = array_intersect_key($files_new, $gFilesInDb[$dropboxcat->cloud_id]);
                } else {
                    $files_diff_add = $files_new;
                    $files_diff_del = array();
                    $files_update = array();
                }

                if (!empty($files_update)) {
                    foreach ($files_update as $file_id => $file) {
                        $localFileTime = strtotime($gFilesInDb[$dropboxcat->cloud_id][$file_id]->modified_time);
                        $need_update = false;
                        if ($localFileTime) {
                            if ($localFileTime < strtotime($file['server_modified'])) {
                                $need_update = true;
                            }
                        } else {
                            $need_update = true;
                        }

                        if ($need_update) {
                            $data = array();
                            $data['id'] = $gFilesInDb[$dropboxcat->cloud_id][$file_id]->id;
                            $data['file_id'] = $file['id'];
                            $data['ext'] = JFile::getExt($file['name']);
                            $data['size'] = $file['size'];
                            $data['title'] = $file['name'];
                            $data['catid'] = $dropboxcat->cloud_id;
                            $data['modified_time'] = $file['server_modified'];
                            $model->save($data);
                        }
                    }
                }

                if (!empty($files_diff_add)) {
                    foreach ($files_diff_add as $file_id => $file) {
                        $data = array();
                        $data['id'] = 0;
                        $data['title'] = JFile::stripExt($file['name']);
                        $data['file_id'] = $file['id'];
                        $data['ext'] = strtolower(JFile::getExt($file['name']));
                        $data['size'] = $file['size'];
                        $data['catid'] = $dropboxcat->cloud_id;
                        $data['path'] = $file['path_lower'];
                        $data['created_time'] = date('Y-m-d H:i:s', strtotime($file['client_modified']));
                        $data['modified_time'] = date('Y-m-d H:i:s', strtotime($file['server_modified']));
                        $model->save($data);
                    }
                }

                if (!empty($files_diff_del)) {
                    $files_del = array_merge($files_del, array_keys($files_diff_del));
                }
            }

            if (!empty($files_del)) {
                $model->deleteFiles($files_del);
            }
        }
        // Update files count
        $categoriesModel = $this->getModel('Categories', 'DropfilesModel');
        $categoriesModel->updateFilesCount();
        die();
    }

    /**
     * Sync Dropbox files in each category
     *
     * @param boolean $isCron Is this running from a cronjob
     * @param integer $step   Step to run
     *
     * @return boolean|mixed
     * @since  version
     */
    public function syncFiles($isCron = false, $step = 0)
    {
        $app = JFactory::getApplication();
        $model = $this->getModel();
        $dropboxCats = $model->getAllDropboxCategories();

        if (!$isCron) {
            $step = $app->input->getInt('step');
        }

        $debug = false;
        if ($debug) {
            JLog::addLogger(
                array(
                    // Sets file name
                    'text_file' => 'com_dropfiles.php',
                    // Sets the format of each line
                    'text_entry_format' => '{DATETIME} {MESSAGE}'
                ),
                // Sets all but DEBUG log level messages to be sent to the file
                JLog::ALL & ~JLog::DEBUG,
                // The log category which should be recorded in this file
                array('com_dropfiles')
            );
        }

        $path_dropfilesDropbox = JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesDropbox.php';
        JLoader::register('DropfilesDropbox', $path_dropfilesDropbox);
        $dropbox = new DropfilesDropbox();
        if (!$dropbox->checkAuth()) {
            $allFilesInDb = $model->getAllDropboxFilesInDb();
            if (!isset($dropboxCats[$step])) {
                if (!$isCron) {
                    echo json_encode(array('continue' => false));
                } else {
                    return false;
                }

                $app->close();
            }

            $dropboxCat = $dropboxCats[$step];
            $files = $dropbox->listDropboxFiles($dropboxCat->path);
            $files_del = array();
            $files_new = array();
            if (!empty($files)) {
                foreach ($files as $file) {
                    $files_new[$file['id']] = $file;
                }
            }

            if (isset($allFilesInDb[$dropboxCat->cloud_id])) {
                $files_diff_add = array_diff_key($files_new, $allFilesInDb[$dropboxCat->cloud_id]);
                $files_diff_del = array_diff_key($allFilesInDb[$dropboxCat->cloud_id], $files_new);
                $files_update = array_intersect_key($files_new, $allFilesInDb[$dropboxCat->cloud_id]);
            } else {
                $files_diff_add = $files_new;
                $files_diff_del = array();
                $files_update = array();
            }

            if (!empty($files_update)) {
                foreach ($files_update as $file_id => $file) {
                    $localFileTime = strtotime($allFilesInDb[$dropboxCat->cloud_id][$file_id]->modified_time);
                    $need_update = false;
                    if ($localFileTime) {
                        if ($localFileTime < strtotime($file['server_modified'])) {
                            $need_update = true;
                        }
                    } else {
                        $need_update = true;
                    }

                    if ($need_update) {
                        $data = array();
                        $data['id'] = $allFilesInDb[$dropboxCat->cloud_id][$file_id]->id;
                        $data['file_id'] = $file['id'];
                        $data['ext'] = JFile::getExt($file['name']);
                        $data['size'] = $file['size'];
                        $data['title'] = $file['name'];
                        $data['catid'] = $dropboxCat->cloud_id;
                        // Convert time value 2025-01-04T13:39:25Z to mysql format
                        $modified_time = str_replace('T', ' ', $file['server_modified']) ;
                        $modified_time = str_replace('Z', '', $modified_time) ;
                        $data['modified_time'] = $modified_time ;
                        $result  = $model->save($data);
                        if ($debug) {
                            JLog::add('file update: '.json_encode($file).'<br />', JLog::INFO, 'com_dropfiles');
                            JLog::add('save result: '. $result .'<br />', JLog::INFO, 'com_dropfiles');
                            if (!$result) {
                                $errors = $model->getErrors();
                                JLog::add('save error: '.json_encode($errors).'<br />', JLog::INFO, 'com_dropfiles');
                            }
                        }
                    }
                }
            }

            if (!empty($files_diff_add)) {
                foreach ($files_diff_add as $file_id => $file) {
                    $data = array();
                    $data['id'] = 0;
                    $data['title'] = JFile::stripExt($file['name']);
                    $data['file_id'] = $file['id'];
                    $data['ext'] = strtolower(JFile::getExt($file['name']));
                    $data['size'] = $file['size'];
                    $data['catid'] = $dropboxCat->cloud_id;
                    $data['path'] = $file['path_lower'];
                    $data['created_time'] = date('Y-m-d H:i:s', strtotime($file['client_modified']));
                    $data['modified_time'] = date('Y-m-d H:i:s', strtotime($file['server_modified']));
                    $data['author'] = ''; // sync
                    $model->save($data);
                }
            }

            if (!empty($files_diff_del)) {
                $files_del = array_merge($files_del, array_keys($files_diff_del));
            }
            if (!empty($files_del)) {
                $model->deleteFiles($files_del);
            }
        }


        $step++;
        if (isset($dropboxCats[$step])) {
            if (!$isCron) {
                echo json_encode(array('continue' => true));
            } else {
                return true;
            }
        } else {
            if (!$isCron) {
                echo json_encode(array('continue' => false));
            } else {
                return false;
            }
        }
        $app->close();
        die();
    }


    /**
     * Get model front dropbox
     *
     * @param string $name   Model name
     * @param string $prefix Model prefix
     * @param array  $config Model config
     *
     * @return mixed
     * @since  version
     */
    public function getModel(
        $name = 'frontdropbox',
        $prefix = 'dropfilesModel',
        $config = array('ignore_request' => true)
    ) {
        $model = parent::getModel($name, $prefix, $config);
        return $model;
    }

    /**
     * Watch changes from Dropbox
     *
     * @throws Exception Throws when application can not start
     *
     * @return void
     */
    public function listener()
    {
        if (!class_exists('DropfilesModelOptions')) {
            JLoader::register('DropfilesModelOptions', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/options.php');
        }

        if (!class_exists('DropfilesDropbox')) {
            JLoader::register('DropfilesDropbox', JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesDropbox.php');
        }

        if (!class_exists('DropfilesModelCategories')) {
            JLoader::register('DropfilesModelCategories', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/categories.php');
        }

        $app = JFactory::getApplication();
        // Webhook document link: https://www.dropbox.com/developers/reference/webhooks#documentation
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['challenge'])) { // Verify callback Url
            echo $_GET['challenge'];
            exit();
        } else {
            $options     = new DropfilesModelOptions();
            $watchChange = $options->get_option('dropbox_watch_changes');

            if (is_null($watchChange) || !$watchChange || empty($watchChange)) {
                $this->writeLog('The WatchChange has been disabled!');
                exit();
            } else {
                // Get data from Webhook notifications
                $request_body = file_get_contents('php://input');
                $data         = json_decode($request_body, true);
                $params       = JComponentHelper::getParams('com_dropfiles');
                $secret       = trim($params->get('dropbox_key', ''));
                $sign         = 'X-Dropbox-Signature'; // Verify signature

                $dropbox     = new DropfilesDropbox();
                $accessToken = $dropbox->checkAndRefreshToken();

                if ($accessToken === false) {
                    $this->writeLog('Failed to get access token');
                    exit();
                }

                $accessToken = $accessToken->getToken();

                // Check other changes progress is running or timeout
                if (isset($data['list_folder']['accounts'])) {
                    foreach ($data['list_folder']['accounts'] as $account_id) {
                        // Process changes for this account
                        $this->processChanges($account_id, $accessToken);
                        $processedAccounts[] = $account_id;
                    }
                }
            }
        }
        die();
    }

    /**
     * Process the change from webhook notifications
     *
     * @param string $account_id  Account id
     * @param string $accessToken Access token
     *
     * @throws Exception Throws when application can not start
     *
     * @return mixed
     */
    public function processChanges($account_id, $accessToken)
    {
        // Get the latest cursor
        $cursor = $this->getLatestCursor($account_id, $accessToken);

        // Fetch and process changes
        $this->fetchAndProcessChanges($accessToken, $cursor);
    }

    /**
     * Retrieve the latest cursor
     *
     * @param string $account_id  Account id
     * @param string $accessToken Access token
     *
     * @throws Exception Throws when application can not start
     * @return mixed
     */
    public function getLatestCursor($account_id, $accessToken)
    {
        if (!class_exists('DropfilesModelOptions')) {
            JLoader::register('DropfilesModelOptions', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/options.php');
        }

        if (!class_exists('DropfilesDropbox')) {
            JLoader::register('DropfilesDropbox', JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesDropbox.php');
        }

        $url  = 'https://api.dropboxapi.com/2/files/list_folder/get_latest_cursor';
        $data = array(
            'include_deleted' => false,
            'include_has_explicit_shared_members' => false,
            'include_media_info' => false,
            'include_mounted_folders' => true,
            'include_non_downloadable_files' => true,
            'path' => '',
            'recursive' => true
        );
        $options = new DropfilesModelOptions();
        $header  = array(
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $result     = json_decode($response, true);
        $prevCursor = $options->get_option('_dropfiles_dropbox_latest_cursor_' . $account_id, 'dropfiles_cursor_none');

        if ($prevCursor && strval($prevCursor) !== 'dropfiles_cursor_none') {
            if (isset($result['cursor'])) {
                $options->update_option('_dropfiles_dropbox_latest_cursor_' . $account_id, $result['cursor']);
            } else {
                $options->update_option('_dropfiles_dropbox_latest_cursor_' . $account_id, null);
            }

            return $prevCursor;
        } else {
            if (isset($result['cursor'])) {
                $options->update_option('_dropfiles_dropbox_latest_cursor_' . $account_id, $result['cursor']);
                return $result['cursor'];
            } else {
                $options->update_option('_dropfiles_dropbox_latest_cursor_' . $account_id, null);
                return null;
            }
        }
    }

    /**
     * Fetch and precess changes
     *
     * @param string $accessToken Access token
     * @param string $cursor      Latest cursor
     *
     * @throws Exception Throws when application can not start
     * @return mixed
     */
    public function fetchAndProcessChanges($accessToken, $cursor)
    {
        if (!class_exists('DropfilesModelOptions')) {
            JLoader::register('DropfilesModelOptions', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/options.php');
        }

        if (!class_exists('DropfilesModelCategory')) {
            JLoader::register('DropfilesModelCategory', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/category.php');
        }

        if (!class_exists('DropfilesDropbox')) {
            JLoader::register('DropfilesDropbox', JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesDropbox.php');
        }

        if (!class_exists('DropfilesCloudHelper')) {
            JLoader::register('DropfilesCloudHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/dropfilescloud.php');
        }

        $options         = new DropfilesModelOptions();
        $categoryModel   = new DropfilesModelCategory();
        $cloudHelper     = new DropfilesCloudHelper();
        $url             = 'https://api.dropboxapi.com/2/files/list_folder/continue';
        $params          = JComponentHelper::getParams('com_dropfiles');
        $dropboxKey      = trim($params->get('dropbox_key', ''));
        $dropboxSecret   = trim($params->get('dropboxSecret', ''));
        $basicAuthString = base64_encode($dropboxKey . ':' . $dropboxSecret);
        $data            = array(
            'cursor' => $cursor
        );
        $dropbox         = new dropfilesDropbox();
        $baseFolderId    = trim($params->get('dropboxBaseFolderId', ''));
        $header          = array(
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);
        $result   = json_decode($response, true);

        if (isset($result['entries']) && is_array($result['entries']) && !empty($result['entries'])) {
            $count   = count($result['entries']);
            $folders = array();
            $processFiles = false;
            $processFolders = false;

            if ($count === 1) {
                // Process single change
                foreach ($result['entries'] as $entry) {
                    if (!isset($entry['.tag'])) {
                        continue;
                    }

                    if (!isset($entry['name'])) {
                        continue;
                    }

                    if ($entry['.tag'] === 'file' || ($entry['.tag'] === 'deleted' && pathinfo($entry['name'], PATHINFO_EXTENSION))) {
                        $processFiles = true;
                    }

                    $processFolders = $this->doSyncByChanges($entry);
                }
            } else {
                // Process multiple changes
                $actions  = array();
                $supports = array('folder', 'deleted');

                foreach ($result['entries'] as $entry) {
                    if (!isset($entry['.tag'])) {
                        continue;
                    }

                    if (!isset($entry['name'])) {
                        continue;
                    }

                    if ($entry['.tag'] === 'file' || ($entry['.tag'] === 'deleted' && pathinfo($entry['name'], PATHINFO_EXTENSION))) {
                        $processFiles = true;
                    }

                    if (!in_array($entry['.tag'], $supports)) {
                        continue;
                    }

                    if ($entry['.tag'] === 'folder') {
                        $folders[] = $entry;
                    }

                    if (!in_array($entry['.tag'], $actions)) {
                        $actions[] = $entry['.tag'];
                    }
                }

                if (!empty($actions)) {
                    if (count($actions) === 1) {
                        // Copy or delete actions
                        if (in_array('deleted', $actions)) {
                            $this->processMultipleSyncByChanges($result['entries'], 'deleted');
                        } else {
                            $this->processMultipleSyncByChanges($result['entries'], 'copied');
                        }
                    } else {
                        // Move or rename actions
                        $entries   = array();
                        $processed = false;
                        foreach ($result['entries'] as $entry) {
                            if ($entry['.tag'] === 'deleted') {
                                continue;
                            }

                            if ($entry['.tag'] === 'folder') {
                                $entries[]  = $entry;
                                $folderId   = isset($entry['id']) ? $entry['id'] : '';
                                $folderPath = isset($entry['path_lower']) ? $entry['path_lower'] : '';
                                $termId     = $cloudHelper::getTermIdDropBoxByDropBoxId($folderId);

                                if ($termId && $processed === false) {
                                    $term                      = $categoryModel->getCategory($termId);
                                    $parentTermId              = isset($term->parent_id) ? $term->parent_id : 1;
                                    $parentFolderIdFromDropbox = $dropbox->getDropboxParentFolderId($folderPath);

                                    if (is_null($parentFolderIdFromDropbox) || $parentFolderIdFromDropbox === $baseFolderId) {
                                        $newParentTermId = 1;
                                    } else {
                                        $newParentTermId = $cloudHelper::getTermIdDropBoxByDropBoxId($parentFolderIdFromDropbox);
                                    }

                                    if (intval($parentTermId) === intval($newParentTermId)) {
                                        // Modified
                                        $this->processMultipleSyncByChanges($entries, 'modified', $folders);
                                    } else {
                                        // Move
                                        $this->processMultipleSyncByChanges($entries, 'move', $folders);
                                    }

                                    $processed = true;
                                }
                            }
                        }
                    }
                }
            }

            if ($processFiles) {
                $this->processFileChanges($result['entries']);
            }
        }
    }

    /**
     * Process multiple changes
     *
     * @param array  $entries Changed items
     * @param string $action  Action
     * @param array  $folders Folders change
     *
     * @throws Exception Fire if errors
     *
     * @return mixed
     */
    public function processMultipleSyncByChanges($entries, $action, $folders = array())
    {
        if (!empty($entries) && !empty($action)) {
            switch ($action) {
                case 'copied':
                case 'deleted':
                    foreach ($entries as $entry) {
                        $this->doSyncByChanges($entry);
                    }
                    break;
                case 'modified':
                    foreach ($entries as $entry) {
                        $this->doSyncByChanges($entry, 'modified', $folders);
                    }
                    break;
                case 'move':
                    foreach ($entries as $entry) {
                        $this->doSyncByChanges($entry, 'move', $folders);
                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Process the change to client side
     *
     * @param object|mixed $folder          Folder change
     * @param object|mixed $overwriteChange Overwrite change
     * @param array        $folders         Folders change
     *
     * @throws Exception Throws when application can not start
     * @return mixed|void
     */
    public function doSyncByChanges($folder, $overwriteChange = null, $folders = array())
    {
        if (!class_exists('DropfilesModelOptions')) {
            JLoader::register('DropfilesModelOptions', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/options.php');
        }

        if (!class_exists('DropfilesModelCategory')) {
            JLoader::register('DropfilesModelCategory', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/category.php');
        }

        if (!class_exists('DropfilesModelCategories')) {
            JLoader::register('DropfilesModelCategories', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/categories.php');
        }

        if (!class_exists('DropfilesDropbox')) {
            JLoader::register('DropfilesDropbox', JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesDropbox.php');
        }

        if (!class_exists('DropfilesCloudHelper')) {
            JLoader::register('DropfilesCloudHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/dropfilescloud.php');
        }

        if (!class_exists('DropfilesControllerCategories')) {
            $categoriesControllerPath = JPATH_ADMINISTRATOR . '/components/com_dropfiles/controllers/categories.php';
            JLoader::register('DropfilesControllerCategories', $categoriesControllerPath);
        }

        if (!class_exists('DropfilesControllerCategory')) {
            $categoriesControllerPath = JPATH_ADMINISTRATOR . '/components/com_dropfiles/controllers/category.php';
            JLoader::register('DropfilesControllerCategory', $categoriesControllerPath);
        }

        if (is_null($folder) || empty($folder)) {
            $this->writeLog('Folder is empty from Webhook notifications');
            return false;
        }

        if (!isset($folder['.tag']) || empty($folder['.tag'])) {
            $this->writeLog('Folder type in not defined');
            return false;
        }

        if (isset($folder['.tag']) && $folder['.tag'] === 'file') {
            $this->writeLog('File type is not support');
            return false;
        }

        if (!isset($folder['name'])) {
            $this->writeLog('Folder name is empty');
            return false;
        }

        $categoriesModel = new DropfilesModelCategories();
        $categoryModel = new DropfilesModelCategory();
        $cloudHelper = new DropfilescloudHelper();
        $categoriesController = new DropfilesControllerCategories();
        $categoryController = new DropfilesControllerCategory();
        $app = JFactory::getApplication();
        $params = JComponentHelper::getParams('com_dropfiles');
        $change = !is_null($overwriteChange) ? $overwriteChange : $folder['.tag'];
        $folderName = isset($folder['name']) ? $folder['name'] : '';
        $dropbox = new dropfilesDropbox();
        $baseFolderId = trim($params->get('dropboxBaseFolderId', ''));

        switch ($change) {
            case 'folder':
                // Add new category to Dropfiles
                $folderId          = isset($folder['id']) ? $folder['id'] : '';
                $folderPath        = isset($folder['path_lower']) ? $folder['path_lower'] : '';
                $folderPathDisplay = isset($folder['path_display']) ? $folder['path_display'] : '';
                $termId            = $cloudHelper::getTermIdDropBoxByDropBoxId($folderId);
                $parentFolderId    = $dropbox->getDropboxParentFolderId($folderPath);

                if (is_null($parentFolderId) || empty($parentFolderId)) {
                    $parentTermId = 1;
                    $categoryLevel = 1;
                } else {
                    $parentTermId = $cloudHelper::getTermIdDropBoxByDropBoxId($parentFolderId);
                    $parentTermId = is_null($parentTermId) ? 1 : $parentTermId;
                    $cate = $categoriesModel->getOneCatByCloudId($parentFolderId);
                    $categoryLevel = isset($cate->level) ? $cate->level + 1 : 1;
                }

                if (is_null($termId)) {
                    $insertedCategoryId = $categoriesModel->createOnCategories($folderName, $parentTermId, $categoryLevel);
                    if ($insertedCategoryId) {
                        $categoriesModel->createDropboxOnDropfiles(
                            $insertedCategoryId,
                            'dropbox',
                            $folderId,
                            $folderPath
                        );
                        $this->writeLog('Category name: ' . $folderName . ' created with success');
                    } else {
                        $this->writeLog('Failed to create category name: ' . $folderName);
                    }
                }
                break;
            case 'modified':
                $folderId   = isset($folder['id']) ? $folder['id'] : '';
                $folderPath = isset($folder['path_lower']) ? $folder['path_lower'] : '';
                $termId     = $cloudHelper::getTermIdDropBoxByDropBoxId($folderId);

                if ($termId) {
                    $categoriesModel->updateTitleById($termId, $folderName);
                    $categoriesModel->updatePathDropboxById($termId, $folderPath);

                    // Process all child related the moving on site
                    if (!empty($folders)) {
                        foreach ($folders as $child) {
                            if (!isset($child['id'])) {
                                continue;
                            }

                            if (!isset($child['path_lower'])) {
                                continue;
                            }

                            if ($child['id'] === $folderId) {
                                continue;
                            }

                            $childTermId = $cloudHelper::getTermIdDropBoxByDropBoxId($child['id']);

                            if ($childTermId) {
                                $childFolderPath = $child['path_lower'];
                                $categoriesModel->updatePathDropboxById($termId, $childFolderPath);
                            }
                        }
                    }

                    $this->writeLog('Modify category name: ' . $folderName . ' with success.');
                }
                break;
            case 'move':
                // Move categories on Dropfiles
                $folderId = isset($folder['id']) ? $folder['id'] : '';
                $folderPath = isset($folder['path_lower']) ? $folder['path_lower'] : '';
                $pk = $cloudHelper::getTermIdDropBoxByDropBoxId($folderId);
                $moveModel = $categoriesController->getModel();
                $table = $moveModel->getTable();
                $position = 'first-child';

                if (!is_null($pk) && $pk) {
                    $parentFolderIdFromDropbox = $dropbox->getDropboxParentFolderId($folderPath);

                    if (is_null($parentFolderIdFromDropbox)) {
                        $ref = 1;
                    } else {
                        $ref = $cloudHelper::getTermIdDropBoxByDropBoxId($parentFolderIdFromDropbox);
                    }

                    if (intval($ref) === 0 || empty($ref)) {
                        $ref = 1;
                    }

                    if ($table->moveByReference($ref, $position, $pk)) {
                        $categoriesModel->updatePathDropboxById($pk, $folderPath);

                        // Process all child related the moving on site
                        if (!empty($folders)) {
                            foreach ($folders as $child) {
                                if (!isset($child['id'])) {
                                    continue;
                                }

                                if (!isset($child['path_lower'])) {
                                    continue;
                                }

                                if ($child['id'] === $folderId) {
                                    continue;
                                }

                                $childPk = $cloudHelper::getTermIdDropBoxByDropBoxId($child['id']);

                                if ($childPk) {
                                    $childFolderPath = $child['path_lower'];
                                    $categoriesModel->updatePathDropboxById($childPk, $childFolderPath);
                                }
                            }
                        }

                        $this->writeLog('Move category name: ' . $folderName . ' with success.');
                    }
                }
                break;
            case 'deleted':
                // Delete categories from Dropfiles
                if (!pathinfo($folderName, PATHINFO_EXTENSION)) {
                    $folderPath = isset($folder['path_lower']) ? $folder['path_lower'] : '';
                    $termId = $cloudHelper::getDropfilesIdByDropboxPath($folderPath);

                    if (!is_null($termId) && $termId) {
                        $categoriesModel->removeCategoryFromDropfiles($termId);
                        $categoriesModel->deleteOnCategories($termId);
                    }

                    $this->writeLog('Category name ' . $folderName . ' deleted with success');
                }
                break;
            case 'file':
                if (pathinfo($folderName, PATHINFO_EXTENSION)) {
                    // File indexing
                    $this->index();
                }
                break;
            default:
                break;
        }
    }

    /**
     * Process the files change
     *
     * @param object|mixed $result The change
     *
     * @throws Exception Throws when application can not start
     * @return mixed|void
     */
    public function processFileChanges($result = array())
    {
        if (!class_exists('DropfilesModelOptions')) {
            JLoader::register('DropfilesModelOptions', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/options.php');
        }

        if (!class_exists('DropfilesDropbox')) {
            JLoader::register('DropfilesDropbox', JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesDropbox.php');
        }

        if (!class_exists('DropfilesCloudHelper')) {
            JLoader::register('DropfilesCloudHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/dropfilescloud.php');
        }

        if (!class_exists('DropfilesModelDropboxfiles')) {
            JLoader::register('DropfilesModelDropboxfiles', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/dropboxfiles.php');
        }

        $supports = array('file', 'deleted');
        $model = $this->getModel();
        $dropbox = new dropfilesDropbox();
        $dropfilesModel = new DropfilesModelDropboxfiles();
        foreach ($result as $file) {
            if (!isset($file['.tag']) || empty($file['.tag'])) {
                continue;
            }

            if (!in_array($file['.tag'], $supports)) {
                continue;
            }

            $fileName = isset($file['name']) ? $file['name'] : '';

            if (is_null($fileName) || empty($fileName) || !pathinfo($fileName, PATHINFO_EXTENSION)) {
                continue;
            }

            $change = $file['.tag'];
            switch ($change) {
                case 'file':
                    // Add a new file on dropfiles
                    $folderId = $dropbox->getDropboxParentFolderId($file['path_lower']);
                    $data = array();
                    $data['id'] = 0;
                    $data['title'] = JFile::stripExt($file['name']);
                    $data['file_id'] = $file['id'];
                    $data['ext'] = strtolower(JFile::getExt($file['name']));
                    $data['size'] = $file['size'];
                    $data['catid'] = $folderId;
                    $data['path'] = $file['path_lower'];
                    $data['created_time'] = date('Y-m-d H:i:s', strtotime($file['client_modified']));
                    $data['modified_time'] = date('Y-m-d H:i:s', strtotime($file['server_modified']));
                    $model->save($data);
                    break;
                case 'deleted':
                    // Remove file from dropfiles
                    $delFile = $dropfilesModel->getFileByPath($file['path_lower']);
                    if ($delFile) {
                        $model->deleteFiles(array($delFile->file_id));
                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Write logs
     *
     * @param object|mixed $messages Messages
     *
     * @return void
     */
    public function writeLog($messages = '')
    {
        if ($this->debug) {
            $ds            = DIRECTORY_SEPARATOR;
            $dateString    = date('Y-m-d H:i:s');
            $fileAddress   = '';
            $logFolderPath = JPATH_ROOT . $ds . 'administrator' . $ds . 'logs';
            $logFileName   = $logFolderPath . $ds . 'dropfiles_push_notifications.php';

            if ($fileAddress) {
                $fileAddress = ' ' . $fileAddress;
            }

            // Log message
            $message = sprintf('[%s]%s: %s', $dateString, $fileAddress, $messages);

            // Push logs
            $this->writeMessage($message, $logFileName);
        }
    }

    /**
     * WriteMessage
     *
     * @param string $message  Message
     * @param string $fileName Destination file name
     *
     * @return void|mixed
     */
    public function writeMessage($message = '', $fileName = '') // phpcs:ignore PEAR.Functions.ValidDefaultValue.NotAtEnd -- it worked
    {
        if (!file_exists($fileName)) {
            $hl = fopen($fileName, 'w');
            fwrite($hl, " Start log: \n");
            fclose($hl);
        } else {
            $hl = fopen($fileName, 'a');
            fwrite($hl, $message . "\n");
            fclose($hl);
        }
    }
}
