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



use Krizalys\Onedrive\Client;
use Krizalys\Onedrive\Exception\ConflictException;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Http\GraphResponse;
use Microsoft\Graph\Model\DriveItem;
use Microsoft\Graph\Model\Subscription;
use Microsoft\Graph\Model;

/**
 * Class DropfilesOneDriveBusiness initialization and connection OneDrive Business
 */
class DropfilesOneDriveBusiness
{
    /**
     * Onedrive connection config
     *
     * @var array $config
     */
    public $config;
    /**
     * OneDrive Client
     *
     * @var OneDrive_Client
     */
    private $client = null;

    /**
     * File fields
     *
     * @var string
     */
    protected $apifilefields = 'thumbnails,children(top=1000;expand=thumbnails(select=medium,large,mediumSquare,c1500x1500))';

    /**
     * List files fields
     *
     * @var string
     */
    protected $apilistfilesfields = 'thumbnails(select=medium,large,mediumSquare,c1500x1500)';

    /**
     * BreadCrumb
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
     * Refresh token
     *
     * @var string
     */
    private $refreshToken;

    /**
     * Last error
     *
     * @var $lastError
     */
    protected $lastError;

    /**
     * Cloud type
     *
     * @var string
     */
    protected $type = 'onedrive_business';

    /**
     * Debug
     *
     * @var boolean
     */
    private $debug = false;

    /**
     * DropfilesOneDriveBusiness constructor.
     */
    public function __construct()
    {
        set_include_path(__DIR__ . PATH_SEPARATOR . get_include_path());
        $path_dropfilescloud  = JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/dropfilescloud.php';
        $path_admin_component = JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/component.php';
        JLoader::register('DropfilesCloudHelper', $path_dropfilescloud);
        JLoader::register('DropfilesComponentHelper', $path_admin_component);
        require_once 'OneDriveBusiness/packages/autoload.php';
        $this->config = DropfilesCloudHelper::getAllOneDriveBusinessConfigs();
        if ($this->client === null && isset($this->config['state']->token->data->access_token)) {
            $this->getClient();
        }
    }

    /**
     * Check Onedrive Business show connect button
     *
     * @return boolean
     */
    public function hasOneDriveButton()
    {
        if (isset($this->config) && (!empty($this->config))) {
            if (!empty($this->config['onedriveBusinessKey']) &&
                !empty($this->config['onedriveBusinessSecret'])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check Onedrive Business connection
     *
     * @return boolean
     */
    public function checkConnectOnedrive()
    {
        if (isset($this->config) && (!empty($this->config))) {
            if (!empty($this->config['onedriveBusinessKey']) &&
                !empty($this->config['onedriveBusinessSecret']) &&
                isset($this->config['connected']) &&
                (int) $this->config['connected'] === 1
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get config
     *
     * @return array
     */
    public function getConfig()
    {
        $params  = JComponentHelper::getParams('com_dropfiles');
        $config  = array(
            'onedriveBusinessKey'         => (isset($params['onedriveBusinessKey']) && (string) $params['onedriveBusinessKey'] !== '') ? $params['onedriveBusinessKey'] : '',
            'onedriveBusinessSecret'      => (isset($params['onedriveBusinessSecret']) && (string) $params['onedriveBusinessSecret'] !== '') ? $params['onedriveBusinessSecret'] : '',
            'onedriveBusinessSyncTime'    => isset($params['onedriveBusinessSyncTime']) ? $params['onedriveBusinessSyncTime'] : '30',
            'onedriveBusinessSyncMethod'  => isset($params['onedriveBusinessSyncMethod']) ? $params['onedriveBusinessSyncMethod'] : 'sync_page_curl',
            'onedriveBusinessConnectedBy' => (int)JFactory::getUser()->id,
            'onedriveBusinessBaseFolder'  => isset($params['onedriveBusinessBaseFolder']) ? $params['onedriveBusinessBaseFolder'] : array(),
            'state'                       => isset($params['onedriveBusinessState']) ? $params['onedriveBusinessState'] : array(),
            'connected'                   => (isset($params['onedriveBusinessConnected']) && (int)$params['onedriveBusinessConnected'] === 1) ? (int) $params['onedriveBusinessConnected'] : 0
        );

        $this->config = $config;
        $this->accessToken = isset($config['state']->token->data->access_token) ? $config['state']->token->data->access_token : '';
        return $this->config;
    }

    /**
     * Save config
     *
     * @param array $config Config
     *
     * @return void
     */
    public function saveConfig($config)
    {
        if (!class_exists('DropfilesComponentHelper')) {
            $path_admin_component = JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/component.php';
            JLoader::register('DropfilesComponentHelper', $path_admin_component);
        }
        if (isset($config['state'])) {
            $config['onedriveBusinessState'] = $config['state'];
            unset($config['state']);
        }
        if (isset($config['connected'])) {
            $config['onedriveBusinessConnected'] = $config['connected'];
            unset($config['connected']);
        }
        if (isset($config['onedriveBusinessLogout'])) {
            if (isset($config['onedriveBusinessState'])) {
                unset($config['onedriveBusinessState']);
            }
            unset($config['onedriveBusinessLogout']);
        }

        DropfilesComponentHelper::setParams($config);
    }

    /**
     * Get authorisation url onedrive business
     *
     * @return string|boolean
     */
    public function getAuthorisationUrl()
    {
        try {
            // Instantiates a OneDrive client bound to your OneDrive application.
            $client = \Krizalys\Onedrive\Onedrive::client($this->config['onedriveBusinessKey']);

            // Gets a log in URL with sufficient privileges from the OneDrive API.
            $authorizeUrl = $client->getLogInUrl(
                array(
                'files.read',
                'files.read.all',
                'files.readwrite',
                'files.readwrite.all',
                'offline_access',
                ),
                JURI::root() . 'administrator/index.php?option=com_dropfiles&task=onedrivebusiness.authenticated',
                'dropfiles-onedrive-business'
            );
            $config = $this->getConfig();
            $config['state'] = $client->getState();
            $this->saveConfig($config);
            return $authorizeUrl;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * Show push notification elements in onedrice business config tab
     *
     * @return void
     */
    public function getPushNotificationButton()
    {
        $errorMessage = '';
        $errorBgColor = '';
        $onedrivePush = DropfilesComponentHelper::getParam('_dropfiles_onedrive_business_watch_changes', false);
        $text = JText::_('COM_DROPFILES_ONEDRIVE_BUSINESS_PUSH_NOTIFICATION_BUTON_WATCH_CHANGES_LABEL');
        $icon = 'arrow-right-4';
        if ($onedrivePush) {
            $icon = 'cancel';
            $text = JText::_('COM_DROPFILES_ONEDRIVE_BUSINESS_PUSH_NOTIFICATION_BUTON_STOP_WATCH_CHANGES_LABEL');
        }

        if ($errorMessage !== '') {
            $errorBgColor = ' error';
        }
        ?>
        <button id="dropfiles-btnpush-onedrive-business"
            data-csrf="<?php echo JSession::getFormToken(); ?>"
            class="ju-button orange-outline-button<?php echo $errorBgColor; ?>"
            style="display: inline-block;float: right;"
            title="<?php echo JText::_('COM_DROPFILES_ONEDRIVE_BUSINESS_PUSH_NOTIFICATION_BUTON_TITLE'); ?>"
    >
            <span class="icon-<?php echo $icon; ?>" aria-hidden="true"></span>
            <?php echo $text; ?>
        </button>
        <?php
        $displayError = DropfilesComponentHelper::getParam('_dropfiles_onedrive_business_display_push_error', true);
        if ($errorMessage !== '' && $displayError) : ?>
            <div class="dropfiles-float-message">
                <div class="dropfiles-alert-message"><strong>Warning: </strong><?php echo $errorMessage; ?></div>
            </div>
        <?php endif;
    }
    /**
     * Authenticate
     *
     * @return boolean
     *
     * @throws Exception Message if errors
     */
    public function authenticate()
    {
        $code = JFactory::getApplication()->input->get('code');
        return $this->createToken($code);
    }

    /**
     * Renew the access token from OAuth. This token is valid for one hour.
     *
     * @param object $client Client
     * @param array  $config Setings
     *
     * @return Client
     */
    public function renewAccessToken($client, $config)
    {
        $client->renewAccessToken($config['onedriveBusinessSecret']);
        $config['state'] = $client->getState();
        $this->saveConfig($config);
        $graph = new \Microsoft\Graph\Graph();
        $graph->setAccessToken($client->getState()->token->data->access_token);
        try {
            $client = \Krizalys\Onedrive\Onedrive::client(
                $config['onedriveBusinessKey'],
                array(
                    'state' => $client->getState()
                )
            );

            return $client;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * Read OneDrive app key and secret
     *
     * @return Krizalys\Onedrive\Client|OneDrive_Client|boolean
     */
    public function getClient()
    {
        if (empty($this->config['onedriveBusinessKey']) && empty($this->config['onedriveBusinessSecret'])) {
            return false;
        }

        try {
            if (isset($this->config['state']) && isset($this->config['state']->token->data->access_token)) {
                $graph = new \Microsoft\Graph\Graph();
                $graph->setAccessToken($this->config['state']->token->data->access_token);
                $client = \Krizalys\Onedrive\Onedrive::client(
                    $this->config['onedriveBusinessKey'],
                    array(
                        'state' => $this->config['state']
                    )
                );

                if ($client->getAccessTokenStatus() === -2) {
                    $client = $this->renewAccessToken($client, $this->config);
                }
            } else {
                $client = \Krizalys\Onedrive\Onedrive::client(
                    $this->config['onedriveBusinessKey']
                );
            }

            $this->client = $client;

            return $this->client;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Create token after connected
     *
     * @param string $code Code to access to OneDrive app
     *
     * @return boolean
     *
     * @throws Exception Message if errors
     */
    public function createToken($code)
    {
        try {
            $onedriveconfig = $this->getConfig();

            $client = \Krizalys\Onedrive\Onedrive::client(
                $onedriveconfig['onedriveBusinessKey'],
                array(
                    'state' => isset($onedriveconfig['state']) && !empty($onedriveconfig['state']) ? $onedriveconfig['state'] : array()
                )
            );

            $siteName = JFactory::getApplication()->get('sitename');
            $blogname = trim(str_replace(array(':', '~', '"', '%', '&', '*', '<', '>', '?', '/', '\\', '{', '|', '}'), '', $siteName));

            // Fix onedrive bug, last folder name can not be a dot
            if (substr($blogname, -1) === '.') {
                $blogname = substr($blogname, 0, strlen($blogname) - 1);
            }

            if ($blogname === '') {
                $siteUrl  = JURI::root() ? JURI::root() : '';
                $blogname = parse_url($siteUrl, PHP_URL_HOST);
                if (!$blogname) {
                    $blogname = '';
                } else {
                    $blogname = trim($blogname);
                }
            }

            // Obtain the token using the code received by the OneDrive API.
            $client->obtainAccessToken($onedriveconfig['onedriveBusinessSecret'], $code);
            $graph = new \Microsoft\Graph\Graph();
            $graph->setAccessToken($client->getState()->token->data->access_token);

            if (empty($onedriveconfig['onedriveBusinessBaseFolder'])) {
                $folderName = 'Dropfiles - ' . $blogname;
                $folderName = preg_replace('@["*:<>?/\\|]@', '', $folderName);
                $folderName = rtrim($folderName);
                try {
                    $root = $client->getRoot()->createFolder($folderName);

                    $onedriveconfig['onedriveBusinessBaseFolder'] = array(
                        'id' => $root->id,
                        'name' => $root->name
                    );
                } catch (ConflictException $e) {
                    $root = $client->getDriveItemByPath('/' . $folderName);
                    $onedriveconfig['onedriveBusinessBaseFolder'] = array(
                        'id' => $root->id,
                        'name' => $root->name
                    );
                }
            } else {
                try {
                    $root = $graph
                        ->createRequest('GET', '/me/drive/items/' . $onedriveconfig['onedriveBusinessBaseFolder']->id)
                        ->setReturnType(\Microsoft\Graph\Model\DriveItem::class) // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- Use to sets the return type of the response object
                        ->execute();
                    $onedriveconfig['onedriveBusinessBaseFolder'] = array(
                        'id' => $root->getId(),
                        'name' => $root->getName()
                    );
                } catch (Exception $ex) {
                    $folderName = 'Dropfiles - ' . $blogname;
                    $folderName = preg_replace('@["*:<>?/\\|]@', '', $folderName);
                    $folderName = rtrim($folderName);
                    $results = $graph->createRequest('GET', '/me/drive/search(q=\'' . $folderName . '\')')
                        // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- Use to sets the return type of the response object
                        ->setReturnType(Model\DriveItem::class)
                        ->execute();
                    if (isset($results[0])) {
                        $root = new stdClass;
                        $root->id = $results[0]->getId();
                        $root->name = $results[0]->getName();
                    } else {
                        $root = $client->getRoot()->createFolder($folderName);
                    }

                    $onedriveconfig['onedriveBusinessBaseFolder'] = array(
                        'id' => $root->id,
                        'name' => $root->name
                    );
                }
            }

            $token = $client->getState()->token->data->access_token;
            $this->accessToken = $token;
            $onedriveconfig['connected'] = 1;
            $onedriveconfig['state'] = $client->getState();
            // update config and redirect page
            $this->saveConfig($onedriveconfig);
        } catch (Exception $ex) {
            $this->writeLog('Create token fail! Message: ' . $ex->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Logout
     *
     * @return boolean
     */
    public function logout()
    {
        $config = $this->getConfig();
        $config['connected'] = '0';
        $config['onedriveBusinessLogout'] = '1';
        unset($config['state']);
        $this->saveConfig($config);
        return true;
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
            if ($client->getAccessTokenStatus() === \Krizalys\Onedrive\Constant\AccessTokenStatus::VALID) {
                return true;
            }
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Create OneDrive Folder
     *
     * @param string $title    New folder name
     * @param string $parentId Parent id
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
                'conflictBehavior' => \Krizalys\Onedrive\Constant\ConflictBehavior::RENAME
            ));
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * Upload file to onedrive business
     *
     * @param string  $filename    File name
     * @param array   $file        File info
     * @param string  $fileContent File path
     * @param string  $id_folder   Upload category id
     * @param boolean $replace     Overwrite file name
     *
     * @return array|boolean|OneDrive_Service_Drive_DriveFile
     */
    public function uploadFile($filename, $file, $fileContent, $id_folder, $replace = false)
    {
        if ($replace) {
            $conflictBehavior = \Krizalys\Onedrive\Constant\ConflictBehavior::REPLACE;
        } else {
            $conflictBehavior = \Krizalys\Onedrive\Constant\ConflictBehavior::RENAME;
        }

        $id_folder = DropfilesCloudHelper::replaceIdOneDrive($id_folder, false);
        $client    = $this->client;
        try {
            $folder = $client->getDriveItemById($id_folder);
        } catch (Exception $e) {
            $file['error'] = print 'Upload failed! : ' . $e->getMessage();

            return $file;
        }

        if (!file_exists($fileContent)) {
            $file['error'] = print 'File not exists! Upload failed!';

            return array(
                'file'   => $file
            );
        }

        $stream = fopen($fileContent, 'rb');
        try {
            $uploadSession                  = $folder->startUpload($filename, $stream, array('conflictBehavior' => $conflictBehavior));

            /* @var $uploadedItem \Krizalys\Onedrive\Proxy\DriveItemProxy */
            $uploadedItem                   = $uploadSession->complete();
            $file['name']                   = DropfilesCloudHelper::stripExt($uploadedItem->name);
            $file['id']                     = DropfilesCloudHelper::replaceIdOneDrive($uploadedItem->id);
            $file['createdDateTime']        = $uploadedItem->createdDateTime->format('Y-m-d H:i:s');
            $file['lastModifiedDateTime']   = $uploadedItem->lastModifiedDateTime->format('Y-m-d H:i:s');
            $file['size']                   = $uploadedItem->size;
            return array(
                'file'   => $file
            );
        } catch (ConflictException $e) { // File name already exists
            $file['error'] = print 'Upload failed! : ' . $e->getMessage();
        } catch (Exception $e) {
            $file['error'] = print 'Upload failed! Unknown exception : ' . $e->getMessage();
        }

        if (isset($file['error'])) {
            return array(
                'file'   => $file
            );
        }

        $file['error'] = print 'Upload failed!';
        return array(
            'file'   => $file
        );
    }

    /**
     * Save file information
     *
     * @param array $datas File info
     *
     * @return boolean
     */
    public function saveOnDriveBusinessFileInfos($datas)
    {
        $id     = DropfilesCloudHelper::replaceIdOneDrive($datas['file_id'], false);
        $client = $this->client;
        try {
            $params = array(
                'name' => $datas['title'] . '.' . $datas['ext']
            );
            $client->updateDriveItem($id, $params);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * Delete file in onedrive business
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
            $client = $this->client;
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

            return false;
        }

        return true;
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
            $client = $this->client;
            $file    = $client->getDriveItemById($id);
            $file->rename($filename);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            return false;
        }

        return true;
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
        $client      = $this->client;

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
            return print 'Failed to move entry: ' . $ex->getMessage();
        }
    }

    /**
     * Move single file and return file infos.
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
        $client      = $this->client;
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
                $fileInfo['created_time']   = $newFile->createdDateTime;
                $fileInfo['modified_time']  = $newFile->lastModifiedDateTime;
            }

            return $fileInfo;
        } catch (Exception $ex) {
            return print 'Failed to move entry: ' . $ex->getMessage();
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
        $client = $this->client;
        try {
            $driveItem = $client->getDriveItemById($fileId);
            $copyTo = $client->getDriveItemById($newParentId);
            $location = $driveItem->copy($copyTo);

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
     * Download onedrive business file
     *
     * @param string $fileId File id
     *
     * @return boolean|stdClass
     */
    public function downloadFile($fileId)
    {
        $fileId = DropfilesCloudHelper::replaceIdOneDrive($fileId, false);
        $client = $this->client;
        try {
            /* @var \Krizalys\Onedrive\Proxy\DriveItemProxy $item */
            $item       = $client->getDriveItemById($fileId);
            $ret        = new stdClass();
            $ret->id    = $item->id;

//            $ret->datas = $this->createSharedLink($fileId);
            /* @var GuzzleHttp\Psr7\Stream $httpRequest */
            $httpRequest    = $item->download();
            $ret->datas     = $httpRequest->getContents();
            $ret->title     = DropfilesCloudHelper::stripExt($item->name);
            $ret->ext       = DropfilesCloudHelper::getExt($item->name);
            $ret->size      = $item->size;

            return $ret;
        } catch (Exception $ex) {
            return print 'Failed to add folder';
        }
    }

    /**
     * Get onedrive business file info
     *
     * @param string  $idFile     File id
     * @param integer $idCategory Category id
     *
     * @return array|boolean
     */
    public function getOneDriveBusinessFileInfos($idFile, $idCategory)
    {
        $idFile  = DropfilesCloudHelper::replaceIdOneDrive($idFile, false);
        $client  = $this->client;

        try {
            $file                  = $client->getDriveItemById($idFile);

            $data                  = array();
            $data['ID']            = DropfilesCloudHelper::replaceIdOneDrive($file->id);
            $data['id']            = $data['ID'];
            $data['catid']         = $idCategory;
            $data['title']         = DropfilesCloudHelper::stripExt($file->name);
            $data['post_title']    = $data['title'];
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
     * Get List folder on OneDrive Business
     *
     * @param string $folderId Folder id
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
     * @param string  $folderId Folder id
     * @param array   $datas    Data return
     * @param boolean $recusive Get all folders
     *
     * @return void
     * @throws Exception Error
     *
     * todo: This only return 200 results
     */
    public function getFilesInFolder($folderId, &$datas, $recusive = true)
    {
        $client      = $this->client;
        try {
            $folder      = $client->getDriveItemById($folderId);
        } catch (Exception $ex) {
            $this->lastError = 'Get drive item false';
            $this->writeLog($this->lastError);
        }

        $params      = $this->getConfig();
        $baseFolder = (array) $params['onedriveBusinessBaseFolder'];
        $baseFolderId = is_array($baseFolder) ? $baseFolder['id'] : (is_object($baseFolder) ? $baseFolder->id : false);
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
                    /* @var \Krizalys\Onedrive\Proxy\DriveItemProxy $item */

                    if ($item->folder) {
                        $parentReference = $item->parentReference;
                        $idItem          = $item->id;
                        $nameItem        = $item->name;
                        if ($idItem !== $baseFolderId) {
                            if ((string) $parentReference->id === (string) $baseFolderId) {
                                $datas[$idItem] = array('title' => $nameItem, 'parent_id' => 1);
                            } else {
                                $datas[$idItem] = array(
                                    'title'     => $nameItem,
                                    'parent_id' => $parentReference->id
                                );
                            }
                            if ($recusive) {
                                $this->getFilesInFolder($idItem, $datas);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $datas     = false;
                $pageToken = null;
                throw new Exception('getFilesInFolder - Onedrive Business error ' . $e->getMessage());
            }
        } while ($pageToken);
    }

    /**
     * List files onedrive business
     *
     * @param string $folder_id       Category id
     * @param string $dropfiles_catid Category id in dropfiles
     * @param string $ordering        Ordering
     * @param string $direction       Ordering direction
     * @param array  $listIdFlies     List id files
     *
     * @return array|boolean
     */
    public function listFiles($folder_id, $dropfiles_catid, $ordering = 'ordering', $direction = 'asc', $listIdFlies = array())
    {
        $client         = $this->getClient();
        try {
            $folder = $client->getDriveItemById($folder_id);
            $items  = $folder->getChildren(
                array(
                    'top' => 500
                )
            );

            $files = array();

            foreach ($items as $f) {
                if ($f->folder) {
                    continue;
                }
                $idItem     = DropfilesCloudHelper::replaceIdOneDrive($f->id);
                $parentId   = $f->parentReference->id;
                if ($listIdFlies && !in_array($idItem, $listIdFlies)) {
                    continue;
                }
                if ($folder_id === $parentId) {
                    $file                   = new stdClass();
                    $file->id               = $idItem;
                    $file->ID               = $idItem;
                    $file->title            = DropfilesCloudHelper::stripExt($f->name);
                    $file->post_title       = $file->title;
                    $file->description      = $f->description ? $f->description : '';
                    $file->ext              = DropfilesCloudHelper::getExt($f->name);
                    $file->size             = $f->size;
                    $file->created          = date('Y-m-d H:i:s', strtotime($f->createdDateTime->format('Y-m-d H:i:s')));
                    $file->created_time     = $file->created;
                    $modified_time          = date('Y-m-d H:i:s', strtotime($f->lastModifiedDateTime->format('Y-m-d H:i:s')));
                    $file->modified         = $modified_time;
                    $file->modified_time    = $modified_time;
                    $file->versionNumber    = '';
                    $file->version          = '';
                    $file->hits             = 0;
                    $file->ordering         = 0;
                    $file->file_custom_icon = '';
                    $file->catid            = $dropfiles_catid;

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
     * Watch changes
     *
     * @return boolean|array
     * @throws Exception Throw when failed
     *
     * @see https://docs.microsoft.com/en-us/graph/api/resources/subscription?view=graph-rest-1.0
     */
    public function watchChanges()
    {
        $client = $this->getClient();
        $drivers = $client->getDrives();
        $watchDatas = array();
        foreach ($drivers as $drive) {
            $this->writeLog('Driver ID: '.  $drive->id);
            $driveId = $drive->id;
            try {
                $subscriptionData = $this->createSubscription($driveId);
            } catch (Exception $e) {
                $this->writeLog(__METHOD__ . ' : DRIVE ID: ' . $driveId . ' Exception: ' . $e->getMessage());
                continue;
            }

            if (!$subscriptionData) {
                $this->writeLog(__METHOD__ . ' : Watch changes failed for DRIVE ID: ' . $driveId);
                continue;
            }
            $watchDatas[$subscriptionData->getId()] = $subscriptionData;
        }

        $this->writeLog(__METHOD__ . ': watch changes success!');
        DropfilesComponentHelper::setParam('_dropfiles_onedrive_business_watch_data', json_encode($watchDatas));
        try {
            $this->getDeltaLinkData($watchDatas);
        } catch (Exception $e) {
            $this->writeLog(__METHOD__ . ' :' . $e->getMessage());

            return false;
        }
        // Get the first deltaLink
        return true;
    }

    /**
     * Create subscription
     *
     * @param string $driveId Drive id
     *
     * @return Subscription | boolean
     *
     * @throws Exception Throw when error
     */
    public function createSubscription($driveId)
    {
        $ajaxUrl = JURI::root() . 'index.php?option=com_dropfiles&task=frontonedrivebusiness.listener';

        $subscription = new Subscription(array(
            'changeType'         => 'updated',
            'notificationUrl'    => $ajaxUrl,
            'resource'           => 'drives/' . $driveId . '/root', // The relative path of the subscription within the drive. Read-only.
            'expirationDateTime' => gmdate('Y-m-d\TH:i:s\Z', strtotime('+ 2 days')),
            'clientState'        => 'dropfiles-onedrive-business-subscription',
        ));

        $graph = new Graph;
        $this->getConfig();
        $graph->setAccessToken($this->accessToken);
        $graph->setApiVersion('beta');
        try {
            $response = $graph->createRequest('POST', '/subscriptions')
                ->attachBody($subscription)
                ->execute();
        } catch (\Exception $e) {
            $this->writeLog(__METHOD__ . ':' . $e->getMessage());
            throw new \Exception('Unexpected status code produced by \'GET /subscriptions\': ' . $e->getMessage());
        }

        $status = $response->getStatus();

        if ($status !== 201) {
            $this->writeLog(__METHOD__ . ': Watch changes failed. Response code: ' . $status);
            throw new \Exception('Unexpected status code produced by \'GET /subscriptions\': ' . $status);
        }

        // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- It's OK
        $data = $response->getResponseAsObject(Subscription::class);

        return $data;
    }

    /**
     * List subscriptions
     *
     * @return mixed
     * @throws Exception Throw if failed
     */
    public function listSubscriptions()
    {
        $graph = new Graph;
        $this->getConfig();
        $graph->setAccessToken($this->accessToken);
        try {
            $response = $graph->createRequest('GET', '/subscriptions')
                ->execute();
        } catch (Exception $e) {
            throw new \Exception('Unexpected status code produced by \'GET /subscriptions\': ' . $e->getMessage());
        }

        $status = $response->getStatus();

        if ($status !== 200) {
            throw new \Exception('Unexpected status code produced by \'GET /subscriptions\': ' . $status);
        }

        $data = $response->getBody();

        if (!is_array($data)) {
            return false;
        }

        return $data;
    }

    /**
     * Check subscription and renew if it expiration soon
     *
     * @return boolean
     * @throws Exception Throw when failed
     */
    public function checkSubscriptions()
    {
        $this->writeLog('Check subscriptions: ' . date('d-m-Y H:i:s'));

        $watchChanges = DropfilesComponentHelper::getParam('_dropfiles_onedrive_business_watch_changes', false);
        if (intval($watchChanges) === false) {
            $this->writeLog('Watch changes turn off, exit!');
            return false;
        }

        // Get current subscriptions information
        $watchDatas = DropfilesComponentHelper::getParam('_dropfiles_onedrive_business_watch_data', array());

        if ($watchDatas === '') {
            return false;
        }

        // Check expiry date
        if (is_array($watchDatas) && count($watchDatas) > 0) {
            $errors = array();
            /* @var Subscription[] $watchDatas */
            foreach ($watchDatas as $subscription) {
                $expiredDate = $subscription->getExpirationDateTime()->getTimestamp();

                $checkpoint = strtotime('today + 1 days');
                $this->writeLog('ExpirationDate: ' . $subscription->getExpirationDateTime()->format('d-m-Y H:i:s'));
                $this->writeLog('Current Date: ' . date('d-m-Y H:i:s', $checkpoint));
                if ($expiredDate < $checkpoint) {
                    // Renew it
                    try {
                        $this->renewSubscriptions($subscription);
                    } catch (Exception $e) {
                        $errors[] = true;
                        $this->writeLog($e->getMessage());
                    }
                }
            }
        }

        return false;
    }

    /**
     * Renew subscriptions
     *
     * @param Subscription $subscription Subscription data
     *
     * @return boolean
     *
     * @throws Exception Can not renew subscription
     */
    public function renewSubscriptions(Subscription $subscription)
    {
        $newExpiredTime = $subscription->getExpirationDateTime()->add(new \DateInterval('P2D'));
        $patchData = new Subscription();
        $patchData->setExpirationDateTime($newExpiredTime);
        // Send Patch
        $graph  = new Graph;
        $this->getConfig();
        $graph->setAccessToken($this->accessToken);
        try {
            $response = $graph->createRequest('PATCH', '/subscriptions/' . $subscription->getId())
                ->attachBody($patchData)
                ->execute();
        } catch (Exception $e) {
            throw new \Exception('Unexpected status code produced by \'PATCH /subscriptions/' . $subscription->getId() .'\': ' . $e->getMessage());
        }

        $status = $response->getStatus();

        if ($status !== 200) {
            throw new \Exception('Unexpected status code produced by \'PATCH /subscriptions/' . $subscription->getId() .'\': ' . $status);
        }

        /* @var Subscription $data */
        $data = $response->getResponseAsObject(Subscription::class); // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- It's OK
        $this->writeLog('Subscription renewed. Id: ' . $data->getId() . ' New expiried time: ' . $data->getExpirationDateTime()->format('d-m-Y H:i:s'));
        $this->updateWatchData($data);

        return true;
    }

    /**
     * Update watch changes data
     *
     * @param Subscription $data Subscription data
     *
     * @return void
     */
    public function updateWatchData(Subscription $data)
    {
        $watchDatas = DropfilesComponentHelper::getParam('_dropfiles_onedrive_business_watch_data', array());

        if ($watchDatas === '' || !is_array($watchDatas)) {
            return;
        }

        if (isset($watchDatas[$data->getId()])) {
            $watchDatas[$data->getId()] = $data;

            DropfilesComponentHelper::setParam('_dropfiles_onedrive_business_watch_data', $watchDatas);
        }
    }

    /**
     * Get watch changes data
     *
     * @return void|Subscription|string
     */
    public function getWatchDatas()
    {
        $params = JComponentHelper::getParams('com_dropfiles');
        $watchData = $params->get('_dropfiles_onedrive_business_watch_data', array());

        if (is_array($watchData)) {
            return $watchData;
        }

        return json_decode($watchData, true);
    }

    /**
     * Get delta links array
     *
     * @return array
     */
    public function getDeltaLinks()
    {
        $params = JComponentHelper::getParams('com_dropfiles');
        $deltaLinks = $params->get('_dropfiles_onedrive_business_delta_links', array());

        if (is_array($deltaLinks)) {
            return $deltaLinks;
        }

        return json_decode($deltaLinks, true);
    }

    /**
     * Stop watch a channel
     *
     * @param Subscription $subscription Subscription
     *
     * @return boolean
     * @throws Exception Throw exception on failed
     */
    public function stopWatch($subscription)
    {
        $graph = new Graph;
        $this->getConfig();
        $graph->setAccessToken($this->accessToken);
        try {
            $response = $graph->createRequest('DELETE', '/subscriptions/' . $subscription['id'])->execute();
        } catch (Exception $e) {
            $this->writeLog(__METHOD__ . ': ' . $e->getMessage());
            throw new \Exception('Unexpected status code produced by \'DELETE /subscriptions/' . $subscription['id'] . '\': ' . $e->getMessage());
        }

        $status = $response->getStatus();

        if ($status !== 204) {
            $this->writeLog(__METHOD__ . ': ' . $status);
            throw new \Exception('Unexpected status code produced by \'GET /subscriptions/' . $subscription['id'] . '\': ' . $status);
        }

        // Clean old delta links
        DropfilesComponentHelper::setParam('_dropfiles_onedrive_business_delta_links', array());
        return true;
    }

    /**
     * Get delta link of drive
     *
     * @param array $subscriptions Subscriptions data array
     *
     * @return DriveItem[]|array
     *
     * @throws Exception Throw when failed
     */
    public function getDeltaLinkData($subscriptions = null)
    {
        $graph = new Graph;
        $this->getConfig();
        $graph->setAccessToken($this->accessToken);
        if (is_null($subscriptions)) {
            // Get resource list
            $subscriptions = $this->getWatchDatas();

            if (empty($subscriptions)) {
                $this->writeLog(__METHOD__ . ' Subscription empty');

                return array();
            }
        }
        $driveItems = array();
        $deltaLinks = array();
        /* @var Subscription $subscription */
        foreach ($subscriptions as $subscription) {
            $resource = is_array($subscription) ? $subscription['resource'] : ($subscription instanceof Microsoft\Graph\Model\Subscription ? $subscription->getResource() : false);
            $id = is_array($subscription) ? $subscription['id'] : ($subscription instanceof Microsoft\Graph\Model\Subscription ? $subscription->getId() : false);
            if ($resource === false) {
                continue;
            }
            $deltaLink = '/' . $resource . '/delta';

            try {
                $this->writeLog('Start travel DeltaLink: ' . $deltaLink);
                list($items, $nextDeltaLink) = $this->travelDeltaLink($deltaLink);
            } catch (Exception $e) {
                $this->writeLog(__METHOD__ . ' Travel Delta Link faild:' . $e->getMessage());
                continue;
            }
            // Save next deltalink for each subscription
            $deltaLinks[$id] = $nextDeltaLink;
            if (is_array($items)) {
                array_merge($driveItems, $items);
            }
        }
        // Update nextDeltaLink
        DropfilesComponentHelper::setParam('_dropfiles_onedrive_business_delta_links', json_encode($deltaLinks));
        return $driveItems;
    }

    /**
     * Sync changes by drive items from delta link response
     *
     * @param array $notifications Subscription information from notification
     *
     * @return void
     *
     * @since 5.9.0
     */
    public function syncChanges($notifications)
    {
        $deltaLinks = $this->getDeltaLinks();

        $subscriptions = $this->getWatchDatas();

        if (empty($subscriptions)) {
            $this->writeLog('No subscriptions!');
            return;
        }

        if (empty($deltaLinks)) {
            $this->writeLog('No detalinks!');
            return;
        }

        DropfilesComponentHelper::setParam('_dropfiles_onedrive_business_on_sync', true);
        // todo: do we need check watch changes or not?
        foreach ($notifications as $notification) {
            if (!isset($notification['subscriptionId'])) {
                // Stop, what is this notification :lol
                $this->writeLog('no subscriptionId');
                continue;
            }
            $subscriptionId = $notification['subscriptionId'];

            if (!isset($subscriptions[$subscriptionId])) {
                // We don't listen for this subscription
                $this->writeLog('We don\'t listen for this subscription' . $subscriptionId);
                continue;
            }

            if (!isset($deltaLinks[$subscriptionId])) {
                // Delta link for this subscription not exists
                $this->writeLog('// Delta link for this subscription not exists');
                // todo: get first delta link for this one, but now just ignore it.
                continue;
            }

            // Get data from delta link
            try {
                list($driveItems, $deltaLink) = $this->travelDeltaLink($deltaLinks[$subscriptionId]);
                $this->writeLog('Drive Items Count: ' . count($driveItems));
            } catch (Exception $e) {
                $this->writeLog(__METHOD__ . ' Travel deltaLink fail :' . $e->getMessage());
                continue;
            }
            // Do sync changes
            $this->updateChanges($driveItems);
            // Save new delta link
            $this->updateDeltaLink($subscriptionId, $deltaLink);
        }
        DropfilesComponentHelper::setParam('_dropfiles_onedrive_business_last_sync_changes', time());
        DropfilesComponentHelper::setParam('_dropfiles_onedrive_business_on_sync', false);
    }

    /**
     * Update changes
     *
     * @param DriveItem $driveItems Onedrive Drive item
     *
     * @return void
     *
     * @since 5.9.0
     */
    public function updateChanges($driveItems)
    {
        if (!is_array($driveItems)) {
            $this->writeLog('This is not a correct drive Item!');
            return;
        }
        $this->writeLog('Start Update Changes');
        /* @var DropfilesModelOnedriveBusinessCategory $onedriveCategory */
        $onedriveCategory = JModelLegacy::getInstance('OnedriveBusinessCategory', 'dropfilesModel');
        /* @var DropfilesModelCategory $modelCategory */
        $modelCategory = JModelLegacy::getInstance('Category', 'dropfilesModel');
        /* @var DropfilesModelCategories $modelCategories */
        $modelCategories = JModelLegacy::getInstance('Categories', 'dropfilesModel');
        /* @var DropfilesModelOneDriveBusinessfiles $onedriveBusinessFileModel */
        $onedriveBusinessFileModel = JModelLegacy::getInstance('OneDriveBusinessfiles', 'DropfilesModel');

        $config = $this->getConfig();
        $baseFolder = isset($config['onedriveBusinessBaseFolder']) ? $config['onedriveBusinessBaseFolder'] : null;
        $baseFolderId = is_array($baseFolder) ? $baseFolder['id'] : (is_object($baseFolder) ? $baseFolder->id : false);
        if (!$baseFolderId) {
            $this->writeLog('Base folder not found!');
            return;
        }
        $this->writeLog('Start Looping Changes');
        /* @var DriveItem $driveItem */
        foreach ($driveItems as $driveItem) {
            // Prevent root folder
            if (is_null($driveItem->getParentReference())) {
                $this->writeLog(__METHOD__ . ' Root detected: ' . $driveItem->getName());
                continue;
            }

            // todo: Sync file
            // todo: clean cache if we use it
//            if (!$driveItem->getDeleted() && $driveItem->getFile()) {
//                $this->writeLog(__METHOD__ . ' File detected: ' . $driveItem->getName());
//                continue;
//            }

            // Prevent base folder, we don't need to do anything with it
            // todo: maybe base folder rename check
            if ($driveItem->getId() === $baseFolderId) {
                $this->writeLog(__METHOD__ . ' Base Folder detected: ' . $driveItem->getName());
                continue;
            }

            // Is file deleted
//            if ($driveItem->getDeleted() && $driveItem->getFile()) {
//                // todo: maybe clean cache
//                $this->writeLog(__METHOD__ . ' File delete detected: ' . $driveItem->getId());
//                continue;
//            }

//            if ((!is_null($driveItem->getParentReference()->getDriveType()) && $driveItem->getParentReference()->getDriveType() !== 'business') || is_null($driveItem->getParentReference()->getId())) {
//                // Made sure this is Onedrive Business and not root folder
//                continue;
//            }

            $action = $this->getChangeAction($driveItem);

            $this->writeLog('Receiver Change Type: ' . $action);

            if (!$action) {
                $this->writeLog(__METHOD__ . ' No action found!');
                continue;
            }
            $cloudParentCatId = $driveItem->getParentReference()->getId();
            $parentCat = $this->getCategoryByOneDriveId($driveItem->getParentReference()->getId());
            $parentCatId = (!is_null($parentCat) && isset($parentCat->id)) ? $parentCat->id : 0;
            $parentLevel = (!is_null($parentCat) && isset($parentCat->level)) ? (int) $parentCat->level : 0;
            switch ($action) {
                case 'file_created':
                    try {
                        $onedriveBusinessFileModel->createFile($driveItem, $cloudParentCatId);
                    } catch (Exception $e) {
                        $this->writeLog('file_created error: ' . $e->getMessage());
                        break;
                    }
                    break;
                case 'file_moved':
                    try {
                        $onedriveBusinessFileModel->moveFile($driveItem, $cloudParentCatId);
                    } catch (Exception $e) {
                        $this->writeLog('file_moved error: ' . $e->getMessage());
                        break;
                    }
                    break;
                case 'file_modified':
                    try {
                        $onedriveBusinessFileModel->updateFile($driveItem, $cloudParentCatId);
                    } catch (Exception $e) {
                        $this->writeLog('file_modified error: ' . $e->getMessage());
                        break;
                    }
                    break;
                case 'file_removed':
                    try {
                        $onedriveBusinessFileModel->deleteFile($driveItem->getId());
                    } catch (Exception $e) {
                        $this->writeLog('file_removed error: ' . $e->getMessage());
                        break;
                    }
                    break;
                case 'folder_created':
                    try {
                        // Single folder created
                        $newCategoryId = $this->addCategoryFromOneDriveBusiness($driveItem->getName(), $driveItem->getId(), $parentCatId, $parentLevel + 1);
                        if (!$newCategoryId) {
                            break;
                        }
                        // When drag and drop a category tree
                        $this->syncOnedriveToLocalFolder($driveItem->getId());
                        $this->writeLog('Folder created sync! New category Id: ' . $newCategoryId);
                    } catch (Exception $e) {
                        $this->writeLog('folder_created: ' . $e->getMessage());
                        break;
                    }
                    break;
                case 'folder_moved':
                    try {
                        if ($baseFolderId === $parentCatId) {
                            $parentCatId = 0;
                        } else {
                            $parentCat  = $modelCategories->getOneCatByCloudId($parentCatId);
                            if (is_null($parentCat)) {
                                break;
                            }
                            $parentCatId = (int) $parentCat->id;
                        }

                        $currentCat = $modelCategories->getOneCatByCloudId($driveItem->getId());
                        if (is_null($currentCat)) {
                            break;
                        }
                        $this->writeLog('Folder Move: Current Category: ' . json_encode($currentCat));
                        $pk       = $currentCat->id; // Catid
                        $ref      = $parentCatId; // Parent
                        $position = 'first-child';

                        $table    = $modelCategory->getTable();
                        $table->moveByReference($ref, $position, $pk);
                    } catch (Exception $e) {
                        $this->writeLog('Move folder fail: ' . $e->getMessage());
                        break;
                    }
                    break;
                case 'folder_modified':
                    try {
                        $newName = $driveItem->getName();
                        $currentCat = $this->getCategoryByOneDriveId($driveItem->getId());
                        if (is_null($currentCat)) {
                            break;
                        }
                        // Rename local category
                        if ($currentCat && $currentCat->title !== $newName) {
                            $modelCategory->setTitle($currentCat->id, $newName);
                        }
                    } catch (Exception $e) {
                        $this->writeLog('folder_modified: ' . $e->getMessage());
                        break;
                    }
                    break;
                case 'folder_removed':
                    try {
                        // Remove in categories
                        $localFolder = $modelCategories->getOneCatByCloudId($driveItem->getId());
                        if (is_null($localFolder)) {
                            break;
                        }
                        $cid = (int) $localFolder->id;

                        $modelCategories->deleteCategoriesRecursive($cid, 'onedrive_business');
                    } catch (Exception $e) {
                        $this->writeLog('folder_removed: ' . $e->getMessage());
                        break;
                    }
                    break;
                default:
                    $this->writeLog('no action');
                    break;
            }

            // Update files count
            $modelCategories->updateFilesCount();
        }
    }

    /**
     * Add category from onedrive business
     *
     * @param string  $title    New category title
     * @param string  $cloudId  Cloud id
     * @param integer $parentId Local parent id
     * @param integer $level    Local level
     *
     * @return boolean
     *
     * @since 5.9.0
     */
    public function addCategoryFromOneDriveBusiness($title, $cloudId, $parentId = 1, $level = 1)
    {
        $categoriesModel = JModelLegacy::getInstance('Categories', 'dropfilesModel');

        $newCatId = $categoriesModel->createOnCategories($title, $parentId, $level);

        if ($newCatId) {
            $categoriesModel->createOnDropfiles($newCatId, 'onedrivebusiness', $cloudId);
            $lstCloudIdOnDropfiles[] = $cloudId;
            return $newCatId;
        }

        return false;
    }

    /**
     * Get change action
     *
     * @param DriveItem $driveItem Onedrive Drive item
     *
     * @return boolean|string
     *
     * @since 5.9.0
     */
    public function getChangeAction($driveItem)
    {
        if (!$driveItem instanceof DriveItem) {
            $this->writeLog('This is not a correct drive Item!');
            return false;
        }

        $config = $this->getConfig();
        $baseFolder = $config['onedriveBusinessBaseFolder'];

        $baseFolderId = is_array($baseFolder) ? $baseFolder['id'] : (is_object($baseFolder) ? $baseFolder->id : false);
        if (!$baseFolderId) {
            $this->writeLog('Base folder not found!');
            return false;
        }

        $id = $driveItem->getId();
        $isFolder = !is_null($driveItem->getFolder()) ? true : false;
        $trashed = !is_null($driveItem->getDeleted()) ? true : false;
        $parent = $driveItem->getParentReference()->getId();

        if ($isFolder && $this->inFolderList($id) && $trashed) {
            return 'folder_removed';
        }
//        if ($id === $baseFolderId) {
//            $this->writeLog('Root folder detected! Return!');
//            return false;
//        }
        if (!$this->inFolderList($parent)) {
            if ($isFolder && $this->inFolderList($id) && $trashed === false) {
                return 'folder_removed'; // Folder move out of base folder
            }
            $this->writeLog('This parent not in list!');
            return false;
        }
        if ($isFolder) {
            // Is folder
            if (!$this->inFolderList($id) && $this->inFolderList($parent) && $trashed === false) {
                return 'folder_created';
            } elseif ($this->inFolderList($id) && $this->inFolderList($parent) && $this->isParentFolderChanged($id, $parent, $baseFolderId) && $parent !== $baseFolderId && $trashed === false) {
                return 'folder_moved';
            } elseif ($trashed) {
                return 'folder_removed';
            } else {
                // Check folder renamed
                $localCategory = $this->getCategoryByOneDriveId($id);
                $this->writeLog('Local Name: ' . $localCategory->title);
                $this->writeLog('Cloud Name: ' . $driveItem->getName());
                if (isset($localCategory->title) && $localCategory->title !== $driveItem->getName()) {
                    return 'folder_modified';
                }
            }
        } else {
            // Is file
            if (!$this->inFileList($id) && !$this->isParentChanged($id, $parent) && $trashed === false) {
                return 'file_created';
            } elseif ($this->inFileList($id) && $this->isParentChanged($id, $parent) && $trashed === false) {
                return 'file_moved';
            } elseif ($trashed) {
                return 'file_removed';
            } else {
                return 'file_modified';
            }
        }

        // This folder
        $this->writeLog('Nothing else! Return!');
        return false;
    }

    /**
     * Is Parent folder changed
     *
     * @param string $folderId     Folder id
     * @param string $newParentId  New parent id
     * @param string $baseFolderId Base folder id
     *
     * @return boolean
     * @since  5.9.0
     */
    private function isParentFolderChanged($folderId, $newParentId, $baseFolderId)
    {
        $localCat         = $this->getCategoryByOneDriveId($folderId);
        $localCatParentId = (int) $localCat->parent_id;

        if ($newParentId === $baseFolderId) {
            $newCatParentId = 0;
        } else {
            $parentCat = $this->getCategoryByOneDriveId($newParentId);
            if (!isset($parentCat->id)) {
                return false;
            }
            $newCatParentId = (int) $parentCat->id;
        }

        if ($localCatParentId !== $newCatParentId) {
            return true;
        }

        return false;
    }
    /**
     * Check is folder in local list
     *
     * @param string $id Folder id
     *
     * @return boolean
     * @since  5.9.0
     */
    private function inFolderList($id)
    {
        $maybeTermId = $this->getCategoryByOneDriveId($id);

        if (!$maybeTermId) {
            // Add onedrive bussiness base folder to folder list
            $config = $this->getConfig();
            $baseFolder = $config['onedriveBusinessBaseFolder'];
            $baseFolderId = is_array($baseFolder) ? $baseFolder['id'] : (is_object($baseFolder) ? $baseFolder->id : false);

            if ($id === $baseFolderId) {
                return true;
            }
        }

        return $maybeTermId;
    }
    /**
     * Check is file in local list
     *
     * @param string $id File id
     *
     * @return boolean
     * @since  5.9.0
     */
    private function inFileList($id)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query
            ->select('*')
            ->from('#__dropfiles_onedrive_business_files')
            ->where('BINARY file_id=' . $db->quote($id));

        $db->setQuery($query);
        $results = $db->loadAssoc();

        if (is_null($results)) {
            return false;
        }

        return true;
    }
    /**
     * Check is parent changed
     *
     * @param string $fileId      Onedrive Business file id
     * @param string $newParentId New parent id
     *
     * @return boolean
     * @since  5.9.0
     */
    private function isParentChanged($fileId, $newParentId)
    {
        $model = JModelLegacy::getInstance('OnedriveBusinessfiles', 'DropfilesModel');
        if (!$model instanceof DropfilesModelOnedriveBusinessfiles) {
            return null;
        }
        $file = $model->getFile($fileId);

        if (!$file) {
            return false;
        }

        if (trim($file->catid) === trim($newParentId)) {
            return false;
        }

        return true;
    }
    /**
     * Get category by cloud id
     *
     * @param string $cloudId Onedrive Business category id
     *
     * @return mixed
     *
     * @since 5.9.0
     */
    protected function getCategoryByOneDriveId($cloudId)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query
            ->select('*')
            ->from('#__categories AS c, #__dropfiles AS d')
            ->where($db->quoteName('c.id') . ' = ' . $db->quoteName('d.id')
                . ' AND BINARY d.cloud_id=' . $db->quote($cloudId));

        $db->setQuery($query);
        $category = $db->loadObject();

        return $category;
    }

    /**
     * Sync A Onedrive Business Category with local
     *
     * @param string $cloudId Cloud category id
     *
     * @return boolean
     * @since  5.9.0
     */
    private function syncOnedriveToLocalFolder($cloudId)
    {
        // Step 1: get category children
        try {
            $newCategories = $this->getListFolder($cloudId);
            /* @var DropfilesModelCategories $categoriesModel */
            $categoriesModel = JModelLegacy::getInstance('Categories', 'dropfilesModel');
            // Step 2: sync with local
            if (count($newCategories) > 0) {
                $lstCloudIdOnDropfiles = $categoriesModel->arrayOneDriveBusinessIdDropfiles();
                $lstCloudIdOnDropfiles = $categoriesModel->arrayOneDriveBusinessIdDropfiles();
                foreach ($newCategories as $CloudId => $folderData) {
                    // If has parent_id.
                    if ($folderData['parent_id'] === 0) {
                        // Create Folder New
                        $newCatId = $categoriesModel->createOnCategories($folderData['title'], 1, 1);
                        if ($newCatId) {
                            $categoriesModel->createOnDropfiles($newCatId, 'onedrivebusiness', $CloudId);
                            $lstCloudIdOnDropfiles[] = $CloudId;
                        }
                    } else {
//                        $lstCloudIdOnDropfiles = $categoriesModel->arrayOneDriveBusinessIdDropfiles();
                        $check = in_array($folderData['parent_id'], $lstCloudIdOnDropfiles);
                        if (!$check) {
                            $lstCloudIdOnDropfiles = $categoriesModel->arrayOneDriveBusinessIdDropfiles();
                            // Create Parent New
                            $ParentCloudInfo = $newCategories[$folderData['parent_id']];
                            $newCatId = $categoriesModel->createOnCategories($ParentCloudInfo['title'], 1, 1);


                            if ($newCatId) {
                                $categoriesModel->createOnDropfiles($newCatId, 'onedrivebusiness', $folderData['parent_id']);
                                $lstCloudIdOnDropfiles[] = $folderData['parent_id'];
                            }

                            //create Children New with parent_id in dropfiles
                            if ($newCatId) {
                                $catRecentCreate = $categoriesModel->getOneCatByLocalId($newCatId);
                                $newChildId = $categoriesModel->createOnCategories(
                                    $folderData['title'],
                                    $catRecentCreate->id,
                                    (int)$catRecentCreate->level + 1
                                );
                                if ($newChildId) {
                                    $categoriesModel->createOnDropfiles(
                                        $newChildId,
                                        'onedrivebusiness',
                                        $CloudId
                                    );
                                    $lstCloudIdOnDropfiles[] = $CloudId;
                                }
                            }
                            $lstCloudIdOnDropfiles = $categoriesModel->arrayOneDriveBusinessIdDropfiles();
                        } else {
                            // Create Children New with parent_id in WPFD
                            $catOldInfo = $this->getCategoryByOneDriveId($folderData['parent_id']);
                            $this->addCategoryFromOneDriveBusiness(
                                $folderData['title'],
                                $CloudId,
                                $catOldInfo->id,
                                $catOldInfo->level + 1
                            );
                            $lstCloudIdOnDropfiles = $categoriesModel->arrayOneDriveBusinessIdDropfiles();
                        }
                    }

                    // Step 3: Sync file with local
                    // $this->syncFileByCloudId($CloudId);
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Update delta link
     *
     * @param string $subscriptionId Subscription id
     * @param string $deltaLink      New delta link
     *
     * @return void
     *
     * @since 5.9.0
     */
    public function updateDeltaLink($subscriptionId, $deltaLink)
    {
        $deltaLinks = $this->getDeltaLinks();

        if (!is_array($deltaLinks)) {
            return;
        }
        $this->writeLog('Updating new deltaLink for subscription Id: ' . $subscriptionId . ' Old deltaLink: ' . $deltaLinks[$subscriptionId]);
        $deltaLinks[$subscriptionId] = $deltaLink;
        $this->writeLog('Update new deltaLink for subscription Id: ' . $subscriptionId . ' New deltaLink: ' . $deltaLink);
        DropfilesComponentHelper::setParam('_dropfiles_onedrive_business_delta_links', $deltaLinks);
    }

    /**
     * Travel delta link
     *
     * @param string $deltaLink Delta link
     *
     * @return array
     *
     * @throws Exception Throw exception when response error
     */
    public function travelDeltaLink($deltaLink)
    {
        $this->writeLog(__METHOD__ . 'Old Delta Link: ' . $deltaLink);
        $graph = new Graph;
        $this->getConfig();
        $graph->setAccessToken($this->accessToken);

        try {
            $response = $graph->createRequest('GET', $deltaLink)->execute();
        } catch (Exception $e) {
            $this->writeLog(__METHOD__ . ': ' . $e->getMessage());
            throw new \Exception('Unexpected status code produced by \'GET \'' . $deltaLink . '\'\': ' . $e->getMessage());
        }
        $status = $response->getStatus();

        if ($status !== 200) {
            $this->writeLog(__METHOD__ . ': ' . $status);
            throw new \Exception('Unexpected status code produced by \'GET /me/drive/root/delta/' . $status);
        }
        /* @var GraphResponse $response */
        $deltaLink = $response->getDeltaLink();
        $nextLink = $response->getNextLink();
        // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- It's OK
        $driveItems = $response->getResponseAsObject(DriveItem::class);
        $this->writeLog(sprintf('Found %d DriveItem in current DeltaLink', count($driveItems)));
        while (is_null($deltaLink)) {
            // Collect items
            try {
                // todo: Find better way to replace this
                $endpoint = str_replace('https://graph.microsoft.com/v1.0', '', $nextLink);
                $response = $graph->createRequest('GET', $endpoint)->execute();
            } catch (Exception $e) {
                $this->writeLog(__METHOD__ . ': ' . $e->getMessage());
                throw new \Exception('Unexpected status code produced by \'GET ' . $endpoint . '\': ' . $e->getMessage());
            }
            $status = $response->getStatus();

            if ($status !== 200) {
                $this->writeLog(__METHOD__ . ': ' . $status);
                throw new \Exception('Unexpected status code produced by \'GET ' . $endpoint . $status);
            }

            // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- It's OK
            $items = $response->getResponseAsObject(DriveItem::class);

            if (is_array($items)) {
                array_merge($driveItems, $items);
            }

            $nextLink = $response->getNextLink();
            $deltaLink = $response->getDeltaLink();
        }
        $this->writeLog(__METHOD__ . ' New Delta Link: ' .  $deltaLink);
        $this->writeLog(sprintf('Finish travel, found %d DriveItem in current DeltaLink', count($driveItems)));
        return array($driveItems, $deltaLink);
    }
    /**
     * Write error log
     *
     * @param string     $message Message
     * @param null|mixed $data    Log data
     *
     * @return void
     */
    public function writeLog($message, $data = null)
    {
        $prefix = '[' . strtoupper(str_replace('_', ' ', $this->type)) . ']';
        if ($this->debug) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug purposed
            error_log($prefix . $message);
            if ($data !== null) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug purposed
                error_log($prefix . print_r($data, true));
            }
        }
    }
    /**
     * Subval Sort
     *
     * @param array  $a         Array
     * @param string $subkey    Sub key
     * @param string $direction Direction
     *
     * @return array
     */
    private function subvalSort($a, $subkey, $direction)
    {
        $c = array();
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
        foreach ($b as $key => $val) {
            $c[] = $a[$key];
        }
        return $c;
    }
}
