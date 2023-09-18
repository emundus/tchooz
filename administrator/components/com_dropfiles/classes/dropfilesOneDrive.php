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
jimport('joomla.filesystem.file');

require_once JPATH_ADMINISTRATOR  . '/components/com_dropfiles/classes/OneDriveBusiness/packages/autoload.php';

use GuzzleHttp\Client as GuzzleHttpClient;
use Krizalys\Onedrive\Client;
use Krizalys\Onedrive\Exception\ConflictException;
use Microsoft\Graph\Exception\GraphException;
use Microsoft\Graph\Graph;
use Krizalys\Onedrive\Onedrive;
use Krizalys\Onedrive\Proxy\FileProxy;
use Microsoft\Graph\Model\DriveItem;
use Microsoft\Graph\Model;
use Microsoft\Graph\Model\UploadSession;
use Krizalys\Onedrive\Constant\ConflictBehavior;
use Krizalys\Onedrive\Constant\AccessTokenStatus;

/**
 * Class DropfilesOneDrive
 */
class DropfilesOneDrive
{
    /**
     * Parameters
     *
     * @var $param
     */
    protected $params;

    /**
     * OneDrive Client
     *
     * @var OneDrive_Client
     */
    private $client = null;

    /**
     * Last error
     *
     * @var $lastError
     */
    protected $lastError;
    /**
     * Api file fields
     *
     * @var string
     */
    protected $apifilefields = 'thumbnails,children(top=1000;expand=thumbnails(select=medium,large,mediumSquare,c1500x1500))';
    /**
     * Api list files fields
     *
     * @var string
     */
    protected $apilistfilesfields = 'thumbnails(select=medium,large,mediumSquare,c1500x1500)';
    /**
     * Breadcrumb
     *
     * @var string
     */
    public $breadcrumb = '';

    /**
     * AccessToken
     *
     * @var string
     */
    private $accessToken;

    /**
     * Drive type
     *
     * @var string
     */
    protected $type = 'onedrive';

    /**
     * DropfilesOneDrive class construct
     */
    public function __construct()
    {
        if (!class_exists('DropfilesCloudHelper')) {
            JLoader::register('DropfilesCloudHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/dropfilescloud.php');
        }
        if (!class_exists('DropfilesComponentHelper')) {
            JLoader::register('DropfilesComponentHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/component.php');
        }
        $this->loadParams();
    }

    /**
     * Get all onedrive config
     *
     * @return array
     */
    public function getAllOneDriveConfigs()
    {
        return DropfilesCloudHelper::getAllOneDriveConfigs();
    }

    /**
     * Get all onedrive config Old
     *
     * @return array
     */
    public function getAllOneDriveConfigsOld()
    {
        return DropfilesCloudHelper::getAllOneDriveConfigsOld();
    }

    /**
     * Save onedrive config
     *
     * @param array $data Config data
     *
     * @return boolean false if value was not updated
     *                 true if value was updated.
     */
    public function saveOneDriveConfigs($data)
    {
        return DropfilesCloudHelper::setParamsConfigs($data);
    }

    /**
     * Get data config by onedrive
     *
     * @param string $name Config name
     *
     * @return array|null
     */
    public function getDataConfigByOneDrive($name)
    {
        return DropfilesCloudHelper::getDataConfigByOneDrive($name);
    }

    /**
     * Get last error
     *
     * @return mixed
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Load param onedrive
     *
     * @return void
     */
    protected function loadParams()
    {
        $params       = $this->getDataConfigByOneDrive('onedrive');
        $this->params = new stdClass();

        $this->params->onedrive_client_id     = $params['onedriveKey'];
        $this->params->onedrive_client_secret = $params['onedriveSecret'];
        $this->params->onedrive_credentials   = isset($params['onedriveCredentials']) ?
            $params['onedriveCredentials'] : '';
        $this->params->onedrive_state         = isset($params['onedriveState']) ? $params['onedriveState'] : array();
        $this->params->access_token           = '';
        if (is_array($this->params->onedrive_state)) {
            $this->params->access_token = isset($this->params->onedrive_state['token']['data']['access_token']) ? $this->params->onedrive_state['token']['data']['access_token'] : '';
        } else {
            $this->params->access_token = isset($this->params->onedrive_state->token->data->access_token) ? $this->params->onedrive_state->token->data->access_token : '';
        }
    }

    /**
     * Save onedrive params
     *
     * @return void
     */
    protected function saveParams()
    {
        $params                        = $this->getAllOneDriveConfigs();
        $params['onedriveKey']         = $this->params->onedrive_client_id;
        $params['onedriveSecret']      = $this->params->onedrive_client_secret;
        $params['onedriveCredentials'] = $this->params->onedrive_credentials;
        $params['onedriveState']       = $this->params->onedrive_state;
        $this->saveOneDriveConfigs($params);
    }

    /**
     * Read OneDrive app key and secret
     *
     * @return Krizalys\Onedrive\Client|OneDrive_Client|boolean
     */
    public function getClient()
    {
        $config = DropfilesCloudHelper::getAllOneDriveConfigs();
        if (empty($config['onedriveKey']) && empty($config['onedriveSecret'])) {
            return false;
        }

        try {
            if (isset($config['onedriveState']) && !empty($this->params->access_token)) {
                $graph = new Graph();
                $graph->setAccessToken($this->params->access_token);
                $client = new Client(
                    $config['onedriveKey'],
                    $graph,
                    new GuzzleHttpClient(),
                    Onedrive::buildServiceDefinition(),
                    array(
                        'state' => json_decode(json_encode($config['onedriveState']))
                    )
                );

                if ($client->getAccessTokenStatus() === -2) {
                    $client = $this->renewAccessToken($client, $config);
                }
            } else {
                $client = new Client(
                    $config['onedriveKey'],
                    new Graph(),
                    new GuzzleHttpClient(),
                    Onedrive::buildServiceDefinition()
                );
            }

            $this->client = $client;
            return $this->client;
        } catch (Exception $ex) {
            $this->lastError = 'Onedrive Error: ' . $ex->getMessage();
            return false;
        }
    }

    /**
     * Create new folder in onedrive
     *
     * @param string      $title    Title
     * @param null|string $parentId Parent id
     *
     * @return \Krizalys\Onedrive\Proxy\DriveItemProxy
     */
    public function createFolder($title, $parentId = null)
    {
        $parentId = DropfilesCloudHelper::replaceIdOneDrive($parentId, false);
        try {
            $client = $this->getClient();
            $parentFolder = $client->getDriveItemById($parentId);
            return $parentFolder->createFolder($title, array(
                'conflictBehavior' => ConflictBehavior::RENAME
            ));
        } catch (Exception $ex) {
            return 'Can not create Onedrive folder:' . $ex->getMessage();
        }
    }

    /**
     * Renews the access token from OAuth. This token is valid for one hour.
     *
     * @param object $client Client
     * @param array  $config Settings
     *
     * @throws Exception     Fire message if errors
     *
     * @return Client
     */
    public function renewAccessToken($client, $config)
    {
        $client->renewAccessToken($config['onedriveSecret']);
        $config['onedriveState'] = $client->getState();
        DropfilesCloudHelper::setParamsConfigs($config);
        $graph = new Graph();
        $graph->setAccessToken($client->getState()->token->data->access_token);
        $client = new Client(
            $config['onedriveKey'],
            $graph,
            new GuzzleHttpClient(),
            Onedrive::buildServiceDefinition(),
            array(
                'state' => $client->getState()
            )
        );
        $config['onedriveState'] = $client->getState();
        DropfilesCloudHelper::setParamsConfigs($config);
        return $client;
    }

    /**
     * Change file name
     *
     * @param string $id       File id
     * @param string $filename New file name
     *
     * @return boolean
     */
    public function changeFilename($id, $filename)
    {
        $id = DropfilesCloudHelper::replaceIdOneDrive($id, false);
        try {
            $client = $this->getClient();
            $file    = $client->getDriveItemById($id);
            $file->rename($filename);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * Delete file in onedrive
     *
     * @param string      $id       File id
     * @param null|string $cloud_id Cloud category id
     *
     * @return boolean
     */
    public function delete($id, $cloud_id = null)
    {
        $id = DropfilesCloudHelper::replaceIdOneDrive($id, false);
        if ($cloud_id !== null) {
            $cloud_id = DropfilesCloudHelper::replaceIdOneDrive($cloud_id, false);
        }

        try {
            $client = $this->getClient();
            $file = $client->getDriveItemById($id);

            if ($cloud_id !== null) {
                $found  = false;
                if ($file->parentReference->id === $cloud_id) {
                    $found = true;
                }
                if (!$found) {
                    return false;
                }
            }
            $file->delete();
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            if (strpos($e->getMessage(), 'itemNotFound') !== false) {
                return true;
            }
            return false;
        }

        return true;
    }

    /**
     * Upload file to onedrive
     *
     * @param string  $filename    File name
     * @param array   $file        File info
     * @param string  $fileContent File path
     * @param string  $id_folder   Upload category id
     * @param boolean $replace     Overwrite file name
     *
     * @throws Exception           Fire message if errors
     *
     * @return array|boolean|OneDrive_Service_Drive_DriveFile
     */
    public function uploadFile($filename, $file, $fileContent, $id_folder, $replace = false)
    {
        if ($replace) {
            $conflictBehavior = ConflictBehavior::REPLACE;
        } else {
            $conflictBehavior = ConflictBehavior::RENAME;
        }

        $id_folder = DropfilesCloudHelper::replaceIdOneDrive($id_folder, false);
        $client    = $this->getClient();
        $folder    = $client->getDriveItemById($id_folder);

        if (!file_exists($fileContent)) {
            $file['error'] = 'File not exists! Upload failed!';
            return array(
                'file'   => $file
            );
        }

        $stream = fopen($fileContent, 'rb');
        try {
            $uploadSession = $folder->startUpload($filename, $stream, array('conflictBehavior' => $conflictBehavior));

            /* @var $uploadedItem \Krizalys\Onedrive\Proxy\DriveItemProxy */
            $uploadedItem = $uploadSession->complete();
            $file['name'] = DropfilesCloudHelper::stripExt($uploadedItem->name);
            $file['id']   = DropfilesCloudHelper::replaceIdOneDrive($uploadedItem->id);
            $file['createdDateTime'] = $uploadedItem->createdDateTime->format('Y-m-d H:i:s');
            $file['lastModifiedDateTime'] = $uploadedItem->lastModifiedDateTime->format('Y-m-d H:i:s');
            return array(
                'file'   => $file
            );
        } catch (ConflictException $e) { // File name already exists
            $file['error'] = 'Upload failed!' . $e->getMessage();
        } catch (Exception $e) {
            $file['error'] = 'Upload failed! Unknown exception: ' . $e->getMessage();
        }

        if (isset($file['error'])) {
            return array(
                'file'   => $file
            );
        }

        $file['error'] = 'Upload failed!';

        return array(
            'file'   => $file
        );
    }

    /**
     * Download file
     *
     * @param string $fileId File id
     *
     * @return boolean|stdClass
     */
    public function downloadFile($fileId)
    {
        $fileId = DropfilesCloudHelper::replaceIdOneDrive($fileId, false);
        $client = $this->getClient();
        try {
            /* @var \Krizalys\Onedrive\Proxy\DriveItemProxy $item */
            $item = $client->getDriveItemById($fileId);

            $ret = new stdClass();
            $ret->id = $item->id;

            /* @var GuzzleHttp\Psr7\Stream $httpRequest */
            $httpRequest = $item->download();
            $ret->datas = $httpRequest->getContents();
            $ret->title = DropfilesCloudHelper::stripExt($item->name);
            $ret->ext = DropfilesCloudHelper::getExt($item->name);
            $ret->size = $item->size;

            return $ret;
        } catch (Exception $ex) {
            return 'Failed to add folder';
        }
    }

    /**
     * Copy a file
     *
     * @param string $fileId      File id
     * @param string $newParentId Target category
     *
     * @return array
     */
    public function copyFile($fileId, $newParentId)
    {
        $newParentId = DropfilesCloudHelper::replaceIdOneDrive($newParentId, false);
        $fileId      = DropfilesCloudHelper::replaceIdOneDrive($fileId, false);
        $client      = $this->getClient();
        try {
            $driveItem = $client->getDriveItemById($fileId);
            $copyTo    = $client->getDriveItemById($newParentId);
            $location  = $driveItem->copy($copyTo);

            if (!empty($location)) {
                sleep(1);

                $response = file_get_contents($location);
                $response = (array) json_decode($response, true);

                if ($response['status'] === 'completed') {
                    return array('id' => DropfilesCloudHelper::replaceIdOneDrive($response['resourceId']));
                } else {
                    $maxTry = 20;
                    $i = 0;
                    while ($response['status'] !== 'completed') {
                        switch ($response['status']) {
                            case 'completed':
                                return array('id' => DropfilesCloudHelper::replaceIdOneDrive($response['resourceId']));
                            case 'failed':
                                return array();
                            default:
                                sleep(1);
                                $response = file_get_contents($location);
                                $response = (array) json_decode($response, true);
                                break;
                        }

                        $i++;
                        if ($i === $maxTry) {
                            break;
                        }
                    }
                }

                return array();
            }
        } catch (Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
            return array();
        }
    }

    /**
     * Move a file.
     *
     * @param string $fileId      File id
     * @param string $newParentId Target folder id
     *
     * @return boolean
     */
    public function moveFile($fileId, $newParentId)
    {
        $newParentId = DropfilesCloudHelper::replaceIdOneDrive($newParentId, false);
        $fileIds     = explode(',', $fileId);
        $client      = $this->getClient();

        /* Set new parent for item */
        try {
            $parentItem  = $client->getDriveItemById($newParentId);
            foreach ($fileIds as $id) {
                $id   = DropfilesCloudHelper::replaceIdOneDrive($id, false);
                $file = $client->getDriveItemById($id);
                $file->move($parentItem);
            }

            return true;
        } catch (Exception $ex) {
            return 'Failed to move entry: ' . $ex->getMessage();
        }
    }

    /**
     * Move file and return new file infos.
     *
     * @param string $fileId      File id
     * @param string $newParentId Target folder id
     *
     * @return array|boolean
     */
    public function moveFileWithInfo($fileId, $newParentId)
    {
        $newParentId = DropfilesCloudHelper::replaceIdOneDrive($newParentId, false);
        $id          = DropfilesCloudHelper::replaceIdOneDrive($fileId, false);
        $client      = $this->getClient();
        $fileInfo    = array();

        /* Set new parent for item */
        try {
            $parentItem  = $client->getDriveItemById($newParentId);
            $file        = $client->getDriveItemById($id);
            $newFile     = $file->move($parentItem);

            if ($newFile) {
                $fileInfo['id']             = $newFile->id;
                $fileInfo['title']          = $newFile->name;
                $fileInfo['size']           = $newFile->size;
                $fileInfo['created_time']   = $newFile->createdDateTime->format('Y-m-d H:i:s');
                $fileInfo['modified_time']  = $newFile->lastModifiedDateTime->format('Y-m-d H:i:s');
            }

            return $fileInfo;
        } catch (Exception $ex) {
            return print 'Failed to move entry: ' . $ex->getMessage();
        }
    }

    /**
     * Get onedrive item info
     *
     * @param string $idFile     File id
     * @param string $idCategory Category id
     *
     * @return array|boolean
     */
    public function getOneDriveFileInfos($idFile, $idCategory)
    {
        $idFile = DropfilesCloudHelper::replaceIdOneDrive($idFile, false);
        $client = $this->getClient();

        try {
            $file = $client->getDriveItemById($idFile);

            $data                  = array();
            $data['ID']            = DropfilesCloudHelper::replaceIdOneDrive($file->id);
            $data['id']            = $data['ID'];
            $data['catid']         = $idCategory;
            $data['title']         = DropfilesCloudHelper::stripExt($file->name);
            $data['post_title']    = $data['title'];
            $data['post_name']     = $data['title'];
            $data['file']          = '';
            $data['ext']           = DropfilesCloudHelper::getExt($file->name);
            $data['created_time']  = date('Y-m-d H:i:s', strtotime($file->createdDateTime->format('Y-m-d H:i:s')));
            $data['created']       = $data['created_time'];
            $modified_time         = date('Y-m-d H:i:s', strtotime($file->lastModifiedDateTime->format('Y-m-d H:i:s')));
            $data['modified_time'] = $modified_time;
            $data['modified']      = $modified_time;
            $data['file_tags']     = '';
            $data['size']          = $file->size;
            $data['ordering']      = 1;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }

        return $data;
    }

    /**
     * Get List folder on OneDrive
     *
     * @param string $folderId Filder id
     *
     * @return array|boolean
     */
    public function getListFolder($folderId)
    {
        $datas = array();
        try {
            $this->getFilesInFolder($folderId, $datas);
        } catch (Exception $ex) {
            return false;
        }

        return $datas;
    }

    /**
     * Get files in folder
     *
     * @param string  $folderId  Folder id
     * @param array   $datas     Data return
     * @param boolean $recursive Recursive
     *
     * @return void
     * @throws Exception Error
     */
    public function getFilesInFolder($folderId, &$datas, $recursive = true)
    {
        $folderId    = DropfilesCloudHelper::replaceIdOneDrive($folderId, false);
        $client      = $this->getClient();
        $folder      = $client->getDriveItemById($folderId);
        $params      = $this->getDataConfigByOneDrive('onedrive');
        $base_folder = array();
        $base_folder['id'] = $params['onedriveBaseFolderId'];
        $pageToken   = null;
        if ($datas === false) {
            throw new Exception('getFilesInFolder - datas error ');
        }

        if (!is_array($datas)) {
            $datas = array();
        }
        do {
            try {
                $childs = $folder->getChildren(
                    array(
                        'top' => 500
                    )
                );
                foreach ($childs as $item) {
                    if ($item->folder) {
                        $parentReference = $item->parentReference;
                        $idItem          = DropfilesCloudHelper::replaceIdOneDrive($item->id);
                        $nameItem        = $item->name;
                        if ($idItem !== $base_folder['id']) {
                            $base_folder_id = DropfilesCloudHelper::replaceIdOneDrive($base_folder['id'], false);
                            if ((string) $parentReference->id === (string) $base_folder_id) {
                                $datas[$idItem] = array('title' => $nameItem, 'parent_id' => 1);
                            } else {
                                $datas[$idItem] = array(
                                    'title'     => $nameItem,
                                    'parent_id' => DropfilesCloudHelper::replaceIdOneDrive($parentReference->id)
                                );
                            }
                            if ($recursive) {
                                $this->getFilesInFolder($idItem, $datas);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $datas     = false;
                $pageToken = null;
                throw new Exception('getFilesInFolder - error ' . $e->getCode());
            }
        } while ($pageToken);
    }

    /**
     * Check Auth
     *
     * @return boolean
     */
    public function checkAuth()
    {
        try {
            $client = $this->getClient();
            if (!$client) {
                return false;
            }
            if ($client->getAccessTokenStatus() === AccessTokenStatus::VALID) {
                return true;
            }
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Get Authorisation Url
     *
     * @return string
     */
    public function getAuthorisationUrl()
    {
        try {
            $client = new Client(
                $this->params->onedrive_client_id,
                new Graph(),
                new GuzzleHttpClient(),
                Onedrive::buildServiceDefinition()
            );
            $authorizeUrl = $client->getLogInUrl(
                array(
                    'files.read',
                    'files.read.all',
                    'files.readwrite',
                    'files.readwrite.all',
                    'offline_access',
                ),
                JURI::root() . 'administrator/index.php',
                'dropfiles-onedrive'
            );

            $this->params->onedrive_state = $client->getState();
            $this->saveParams();

            return $authorizeUrl;
        } catch (Exception $ex) {
            return 'Could not start authorization: ' . $ex->getMessage();
        }
    }

    /**
     * List files onedrive
     *
     * @param string  $folder_id       OneDrive id
     * @param string  $dropfiles_catid Category id
     * @param string  $ordering        Ordering
     * @param string  $direction       Ordering direction
     * @param boolean $listIdFlies     List id files?
     *
     * @return array|boolean
     */
    public function listFiles($folder_id, $dropfiles_catid, $ordering = 'ordering', $direction = 'asc', $listIdFlies = false)
    {
        $folder_idck = DropfilesCloudHelper::replaceIdOneDrive($folder_id, false);
        $client = $this->getClient();
        try {
            $folder = $client->getDriveItemById($folder_idck);

            $items = $folder->getChildren(
                array(
                    'top' => 500
                )
            );

            $files = array();
            foreach ($items as $f) {
                if ($f->folder) {
                    continue;
                }

                $idItem = DropfilesCloudHelper::replaceIdOneDrive($f->id);
                $parentId = $f->parentReference->id;

                if (is_array($listIdFlies) && !in_array($idItem, $listIdFlies)) {
                    continue;
                }

                if ($folder_idck === $parentId) {
                    $file = new stdClass;
                    $file->id = $idItem;
                    $file->ID = $idItem;
                    $file->title = DropfilesCloudHelper::stripExt($f->name);
                    $file->post_title = $file->title;
                    $file->post_name = $file->title;
                    $file->description = $f->description;
                    $file->ext = DropfilesCloudHelper::getExt($f->name);
                    $file->size = $f->size;
                    $file->created_time = date('Y-m-d H:i:s', strtotime($f->createdDateTime->format('Y-m-d H:i:s')));
                    $file->modified_time = date('Y-m-d H:i:s', strtotime($f->lastModifiedDateTime->format('Y-m-d H:i:s')));
                    $file->created = $file->created_time;
                    $file->modified = $file->modified_time;
                    $file->versionNumber = '';
                    $file->version = '';
                    $file->hits = 0;
                    $file->ordering = 0;
                    $file->file_custom_icon = '';
                    $file->catid = $dropfiles_catid;

                    $files[] = $file;
                    unset($file);
                }
            }
            $files = $this->subvalSort($files, $ordering, $direction);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            return false;
        }

        return $files;
    }

    /**
     * Sub val sort
     *
     * @param array  $a         Input array
     * @param string $subkey    Key
     * @param string $direction Order direction
     *
     * @return array
     */
    private function subvalSort($a, $subkey, $direction)
    {
        if (empty($a)) {
            return $a;
        }
        foreach ($a as $k => $v) {
            $b[$k] = strtolower($v->$subkey);
        }
        if ($direction === 'asc') {
            asort($b);
        } else {
            arsort($b);
        }
        $c = array();
        foreach ($b as $key => $val) {
            $c[] = $a[$key];
        }

        return $c;
    }

    /**
     * Onedrive authenticate
     *
     * @throws Exception Fire message if errors
     *
     * @return string
     */
    public function authenticate()
    {
        $code = JFactory::getApplication()->input->get('code');
        return $this->createToken($code);
    }

    /**
     * Create token after connected
     *
     * @param string $code Code to access to onedrive app
     *
     * @return boolean
     */
    public function createToken($code)
    {
        try {
            $onedriveconfig = $this->getAllOneDriveConfigs();

            $client = new Client(
                $onedriveconfig['onedriveKey'],
                new Graph(),
                new GuzzleHttpClient(),
                Onedrive::buildServiceDefinition(),
                array(
                    'state' => isset($onedriveconfig['onedriveState']) && !empty($onedriveconfig['onedriveState']) ? json_decode(json_encode($onedriveconfig['onedriveState'])) : array()
                )
            );

            $siteName = JFactory::getApplication()->get('sitename');
            $blogname = trim(str_replace(array(':', '~', '"', '%', '&', '*', '<', '>', '?', '/', '\\', '{', '|', '}'), '', $siteName));
            // Fix onedrive bug, last folder name can not be a dot
            if (substr($blogname, -1) === '.') {
                $blogname = substr($blogname, 0, strlen($blogname) - 1);
            }

            // Obtain the token using the code received by the OneDrive API.
            $client->obtainAccessToken($onedriveconfig['onedriveSecret'], $code);
            $graph = new Graph();
            $graph->setAccessToken($client->getState()->token->data->access_token);
            $baseFolder = array();

            if (empty($onedriveconfig['onedriveBaseFolderId'])) {
                $folderName = 'Dropfiles - ' . $blogname;
                $folderName = preg_replace('@["*:<>?/\\|]@', '', $folderName);
                $folderName = rtrim($folderName);

                try {
                    $root = $client->getRoot()->createFolder($folderName);
                    $baseFolder = array(
                        'id' => $root->id,
                        'name' => $root->name
                    );
                } catch (ConflictException $e) {
                    $root = $client->getDriveItemByPath('/' . $folderName);
                    $baseFolder = array(
                        'id' => $root->id,
                        'name' => $root->name
                    );
                }
            } else {
                try {
                    $root = $graph
                        ->createRequest('GET', '/me/drive/items/' . $onedriveconfig['onedriveBaseFolderId'])
                        ->setReturnType(Model\DriveItem::class) // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- For testing
                        ->execute();
                    $baseFolder = array(
                        'id' => $root->getId(),
                        'name' => $root->getName()
                    );
                } catch (Exception $ex) {
                    $folderName = 'Dropfiles - ' . $blogname;
                    $folderName = preg_replace('@["*:<>?/\\|]@', '', $folderName);
                    $folderName = rtrim($folderName);
                    $results = $graph->createRequest('GET', '/me/drive/search(q=\'' . $folderName . '\')')
                        ->setReturnType(Model\DriveItem::class) // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- For testing
                        ->execute();
                    if (isset($results[0])) {
                        $root = new stdClass;
                        $root->id = $results[0]->getId();
                        $root->name = $results[0]->getName();
                    } else {
                        $root = $client->getRoot()->createFolder($folderName);
                    }

                    $baseFolder = array(
                        'id' => $root->id,
                        'name' => $root->name
                    );
                }
            }

            $token = $client->getState()->token->data->access_token;
            $this->accessToken = $token;
            $onedriveconfig['onedriveBaseFolderId']     = isset($baseFolder['id']) ? $baseFolder['id'] : '';
            $onedriveconfig['onedriveBaseFolderName']   = isset($baseFolder['name']) ? $baseFolder['name'] : '';
            $onedriveconfig['onedriveConnected']        = 1;
            $onedriveconfig['onedriveState']            = $client->getState();
            $onedriveconfig['onedriveCredentials']      = json_encode($client->getState()->token->data);
            $this->saveOnedriveConfigs($onedriveconfig);
        } catch (Exception $ex) {
            ?>
            <div class="error" id="dropfiles_error">
                <p>
                    <?php
                    if ((int)$ex->getCode() === 409) {
                        echo 'The root folder name already exists on cloud. Please rename or delete that folder before connect';
                    } else {
                        echo 'Error communicating with OneDrive API: ' . $ex->getMessage();
                    }
                    ?>
                </p>
            </div>
            <?php
            return 'Error communicating with OneDrive API: ' . $ex->getMessage();
        }

        return true;
    }

    /**
     * Log out
     *
     * @return boolean
     */
    public function logout()
    {
        $config = DropfilesCloudHelper::getAllOneDriveConfigs();
        $config['onedriveCredentials'] = '';
        $config['onedriveState'] = '';
        $this->saveOneDriveConfigs($config);
        return true;
    }
}
