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

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;

/**
 * Class initialization and connection Dropbox
 */
class DropfilesDropbox
{
    /**
     * Params
     *
     * @var array
     */
    protected $params;

    /**
     * App name
     *
     * @var string
     */
    protected $appName = 'codeUnited/1.0';

    /**
     * Last error
     *
     * @var mixed
     */
    protected $lastError;


    /**
     * DropfilesDropbox constructor.
     */
    public function __construct()
    {
        require_once 'vendor/autoload.php';
        require_once 'Dropbox/autoload.php';

        $this->loadParams();
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
     * Client
     *
     * @var null
     */
    protected $client = null;


    /**
     * Load params
     *
     * @return void
     */
    protected function loadParams()
    {
        $params = JComponentHelper::getParams('com_dropfiles');
        $this->params = new stdClass();

        $this->params->dropboxKey = trim($params->get('dropbox_key', ''));
        $this->params->dropboxSecret = trim($params->get('dropbox_secret', ''));
        $this->params->dropboxAccessToken = trim($params->get('dropboxAccessToken', ''));
        $this->params->dropboxState = trim($params->get('dropboxState', ''));
    }


    /**
     * Save params
     *
     * @return void
     */
    protected function saveParams()
    {
        $path_admin_component = JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/component.php';
        JLoader::register('DropfilesComponentHelper', $path_admin_component);
        DropfilesComponentHelper::setParams(array(
            'dropbox_key' => $this->params->dropboxKey,
            'dropbox_secret' => $this->params->dropboxSecret,
            'dropboxAccessToken' => $this->params->dropboxAccessToken,
            'dropboxState' => $this->params->dropboxState
            ));
    }

    /**
     * Get Redirect URL
     *
     * @return string|void
     */
    public function getRedirectUrl()
    {
        return JURI::root() . 'administrator/index.php?option=com_dropfiles&task=dropbox.authenticated';
    }

    /**
     * Generate Dropbox Authorization Provider
     *
     * @return \League\OAuth2\Client\Provider\GenericProvider
     */
    public function getAuthorizationProvider()
    {
        $dropboxKey = '';
        $dropboxSecret = 'dropboxSecret';

        if (!empty($this->params->dropboxKey)) {
            $dropboxKey = $this->params->dropboxKey;
        }
        if (!empty($this->params->dropboxSecret)) {
            $dropboxSecret = $this->params->dropboxSecret;
        }
        $provider = new League\OAuth2\Client\Provider\GenericProvider(array(
            'clientId'                => $dropboxKey,    // The client ID assigned to you by the provider
            'clientSecret'            => $dropboxSecret,    // The client password assigned to you by the provider
            'redirectUri'             => $this->getRedirectUrl(),
            'urlAuthorize'            => 'https://www.dropbox.com/oauth2/authorize',
            'urlAccessToken'          => 'https://api.dropboxapi.com/oauth2/token',
            'urlResourceOwnerDetails' => 'https://api.dropboxapi.com/2/check/user'
        ));

        return $provider;
    }

    /**
     * Get author Url allow user
     *
     * @return string
     */
    public function getAuthorizeDropboxUrl()
    {
        $provider = $this->getAuthorizationProvider();
        // Fetch the authorization URL from the provider; this returns the
        // urlAuthorize option and generates and applies any necessary parameters
        // (e.g. state).
        $authorizationUrl = $provider->getAuthorizationUrl(array(
            'token_access_type' => 'offline',
        ));

        // Get the state generated for you and store it to the session.
        $this->params->dropboxState = $provider->getState();
        $this->saveParams();

        return $authorizationUrl;
    }
    /**
     * Authorization dropbox request
     *
     * @return boolean
     */
    public function authorization()
    {
        $application = JFactory::getApplication();
        $input = $application->input;
        $provider = $this->getAuthorizationProvider();
        $this->loadParams();
        $authState = $this->params->dropboxState;

        if (!$authState) {
            // No valid authstate
            return false;
        }

        // Get code
        $code = $input->get('code');
        $state = $input->get('state');
        if (empty($code)) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- It's OK
            // Authorization failed
            return false;
            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($state) || ($authState && $state !== $authState)) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- It's OK
            if ($authState) {
                $this->params->dropboxState = '';
                $this->saveParams();
            }

            $this->lastError = 'Invalid state';

            return false;
        } else {
            try {
                // Try to get an access token using the authorization code grant.
                $accessToken = $provider->getAccessToken('authorization_code', array(
                    'code' => $code // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- It's OK
                ));

                // Save the access token
                $this->params->dropboxAccessToken = json_encode($accessToken->jsonSerialize());
                $this->saveParams();

                return true;
            } catch (IdentityProviderException $e) {
                // Failed to get the access token or user details.
                $this->lastError = $e->getMessage();

                return false;
            } catch (Exception $e) {
                $this->lastError = $e->getMessage();

                return false;
            }
        }
    }
    /**
     * Check Author
     *
     * @return boolean
     */
    public function checkAuth()
    {
        $dropboxAccessToken = $this->params->dropboxAccessToken;
        if (!empty($dropboxAccessToken)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Logout
     *
     * @return void
     */
    public function logout()
    {
        $this->params->dropboxAccessToken = '';
        $this->saveParams();
    }

    /**
     * Get Dropbox Account
     *
     * @return \Dropbox\Client
     */
    public function getAccount()
    {
        $accessToken = $this->checkAndRefreshToken();

        if (false === $accessToken) {
            throw new Exception('CheckAndRefreshToken error');
        }

        if (is_null($this->client)) {
            $this->client = new Dropbox\Client($accessToken->getToken(), $this->appName);
        }

        return $this->client;
    }

    /**
     * Check and refresh accessToken
     *
     * @return boolean|\League\OAuth2\Client\Token\AccessToken
     */
    public function checkAndRefreshToken()
    {
        try {
            $storedAccessToken = json_decode($this->params->dropboxAccessToken, true);
            if (is_null($storedAccessToken)) {
                throw new \Exception('Store Access Token not vaild');
            }
            $existingAccessToken = new League\OAuth2\Client\Token\AccessToken($storedAccessToken);

            if ($existingAccessToken->hasExpired()) {
                $newAccessToken = $this->refreshDropboxToken($existingAccessToken->getRefreshToken());
                $storedAccessToken['access_token'] = $newAccessToken->getToken();
                $storedAccessToken['expires'] = $newAccessToken->getExpires();
                $renewedAccessToken = new League\OAuth2\Client\Token\AccessToken($storedAccessToken);
                $this->params->dropboxAccessToken = json_encode($renewedAccessToken->jsonSerialize());

                $this->saveParams();

                return $renewedAccessToken;
            }

            return $existingAccessToken;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            return false;
        }
    }

    /**
     * Refresh the Dropbox token
     *
     * @param string $refreshToken Refresh token
     *
     * @return \League\OAuth2\Client\Token\AccessToken Access token object
     *
     * @throws Exception Throw exception on error
     */
    public function refreshDropboxToken($refreshToken)
    {
        $curl = curl_init();
        $basicAuthString = base64_encode($this->params->dropboxKey . ':' . $this->params->dropboxSecret);
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.dropbox.com/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=refresh_token&refresh_token=' . $refreshToken,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . $basicAuthString,
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        if (curl_errno($curl) || intval($info['http_code']) !== 200) {
            /*
             * https://www.dropbox.com/developers/documentation/http/documentation#error-handling
             *
             * 400  Bad input parameter. The response body is a plaintext message with more information.
             * 401  Bad or expired token. This can happen if the access token is expired or if the access token has been revoked by Dropbox or the user. To fix this, you should re-authenticate the user.
             *      The Content-Type of the response is JSON of typeAuthError
             * 403  The user or team account doesn't have access to the endpoint or feature.
             *      The Content-Type of the response is JSON of typeAccessError
             * 409  Endpoint-specific error. Look to the JSON response body for the specifics of the error.
             * 429  Your app is making too many requests for the given user or team and is being rate limited. Your app should wait for the number of seconds specified in the "Retry-After" response header before trying again.
             *      The Content-Type of the response can be JSON or plaintext. If it is JSON, it will be typeRateLimitErrorYou can find more information in the data ingress guide.
             * 5xx  An error occurred on the Dropbox servers. Check status.dropbox.com for announcements about Dropbox service issues.
            */
            throw new Exception('Failed to refresh the Access Token! Error code: ' . $info['http_code']);
        }
        curl_close($curl);

        $accessTokenArray = $this->parseJson($response);

        return new League\OAuth2\Client\Token\AccessToken($accessTokenArray);
    }

    /**
     * Attempts to parse a JSON response.
     *
     * @param string $content JSON content from response body
     *
     * @return array Parsed JSON data
     *
     * @throws UnexpectedValueException If the content could not be parsed
     */
    protected function parseJson($content)
    {
        $content = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new UnexpectedValueException(sprintf(
                'Failed to parse JSON response: %s',
                json_last_error_msg() // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.json_last_error_msgFound -- Minimum php version is 5.6
            ));
        }

        return $content;
    }

    /**
     * Create Folder to dropbox
     *
     * @param string $title Title
     *
     * @return array|boolean|null
     */
    public function createDropFolder($title)
    {
        try {
            $dropbox = $this->getAccount();
            $path = '/' . $title;

            $result = $dropbox->createFolder($path);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return $result;
    }


    /**
     * Delete Folder to dropbox
     *
     * @param string $id Dropbox category id
     *
     * @return boolean
     */
    public function deleteDropbox($id)
    {
        try {
            $dropbox = $this->getAccount();
            $dropbox->delete($id);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }

        return true;
    }


    /**
     * Rename Folder Dropbox
     *
     * @param string $id       File id
     * @param string $filename File name
     *
     * @return boolean
     */
    public function changeDropboxFilename($id, $filename)
    {
        try {
            $dropbox = $this->getAccount();
            $dropbox->move($id, $filename);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }

        return true;
    }


    /**
     * Rename Folder Dropbox
     *
     * @param string $from From
     * @param string $to   To
     *
     * @return boolean|mixed
     */
    public function moveFile($from, $to)
    {
        try {
            $dropbox = $this->getAccount();
            return $dropbox->move($from, $to);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Upload file to Folder Dropbox
     *
     * @param string  $filename  File name
     * @param string  $fileTemp  Temp path
     * @param integer $size      File size
     * @param string  $id_folder Folder id
     *
     * @return boolean|mixed
     */
    public function uploadFile($filename, $fileTemp, $size, $id_folder)
    {
        $f = fopen($fileTemp, 'rb');
        $path = $id_folder . '/' . $filename;

        try {
            $dropbox = $this->getAccount();
            $result = $dropbox->uploadFile($path, 'add', $f, $size);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            return false;
        }

        return $result;
    }

    /**
     * Get All item in folder id of Dropbox
     *
     * @param string $idFolder Folder id
     *
     * @return mixed
     */
    public function getAllFiles($idFolder)
    {
        try {
            $dropbox = $this->getAccount();
            $fs = $dropbox->getMetadataWithChildren($idFolder);

            return $fs['entries'];
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            return array();
        }
    }


    /**
     * List dropbox file
     *
     * @param string $folder_id Folder id
     *
     * @return array|boolean
     */
    public function listDropboxFiles($folder_id)
    {
        try {
            $dropbox = $this->getAccount();
            $fs = $dropbox->getMetadataWithChildren($folder_id);
            if (empty($fs)) {
                return false;
            }
            $files = array();
            foreach ($fs['entries'] as $f) {
                if ($f['.tag'] === 'file') {
                    $files[] = $f;
                }
            }
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return $files;
    }


    /**
     * Delete file in Dropbox
     *
     * @param string $id_file File id
     *
     * @return boolean
     */
    public function deleteFileDropbox($id_file)
    {
        try {
            $dropbox = $this->getAccount();
            $fs = $dropbox->getMetadata($id_file);
            $dropbox->delete($fs['path_lower']);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Copy item in dropbox
     *
     * @param string $path_from Path from
     * @param string $path_to   Path to
     *
     * @return boolean|mixed|string
     */
    public function copyFileDropbox($path_from, $path_to)
    {
        try {
            $dropbox = $this->getAccount();
            $fs = $dropbox->copy($path_from, $path_to);
            return $fs;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return $e->getMessage();
        }
    }


    /**
     * Get file info in dropbox
     *
     * @param string $idFile File id
     *
     * @return boolean|mixed|null
     */
    public function getDropboxFileInfos($idFile)
    {
        try {
            $dropbox = $this->getAccount();
            $v = $dropbox->getFileMetadata($idFile);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return $v;
    }


    /**
     * Change title
     *
     * @param string $id    File id
     * @param string $title New file title
     *
     * @return boolean
     */
    public function changeFileName($id, $title)
    {
        try {
            $dropbox = $this->getAccount();
            $getFile = $dropbox->getMetadata($id);
            $fpath = pathinfo($getFile['path_lower']);
            $newpath = $fpath['dirname'] . '/' . $title . '.' . $fpath['extension'];
            $dropbox->move($getFile['path_lower'], $newpath);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Save version item
     *
     * @param array $datas Data
     *
     * @return boolean|mixed
     */
    public function saveDropboxVersion($datas)
    {
        try {
            $dropbox = $this->getAccount();
            $getFile = $dropbox->getMetadata($datas['old_file']);
            $f = fopen($datas['new_tmp_name'], 'rb');
            $result = $dropbox->updateFile(
                $getFile['path_display'],
                $getFile['rev'],
                'update',
                $f,
                $datas['new_file_size']
            );
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return $result;
    }

    /**
     * Get version info
     *
     * @param string $idFile File id
     *
     * @return array|boolean
     */
    public function displayDropboxVersionInfo($idFile)
    {
        try {
            $dropbox = $this->getAccount();
            $getFile = $dropbox->getMetadata($idFile);
            $result = $dropbox->listRevisions($getFile['path_lower'], 10);
            $versions = array();
            foreach ($result['entries'] as $v) {
                if ($getFile['rev'] !== $v['rev']) {
                    $fpath = pathinfo($v['path_lower']);
                    $version = new stdClass();
                    $version->ext = $fpath['extension'];
                    $version->size = $v['size'];
                    $version->id = $v['id'];
                    $version->created_time = $v['client_modified'];
                    $version->meta_id = $v['rev'];
                    $versions[] = $version;
                }
            }
            return $versions;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Restore version dropbox
     *
     * @param string $id_file File id
     * @param string $vid     Version id
     *
     * @return boolean
     */
    public function restoreVersion($id_file, $vid)
    {
        try {
            $dropbox = $this->getAccount();
            $getFile = $dropbox->getMetadata($id_file);
            $dropbox->restoreFile($getFile['path_lower'], $vid);
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Download item version
     *
     * @param string $id_file File id
     * @param string $vid     Version id
     *
     * @return boolean
     */
    public function downloadVersion($id_file, $vid)
    {
        try {
            $dropbox = $this->getAccount();
            $getFile = $dropbox->getMetadata($id_file);
            $pinfo = pathinfo($getFile['path_lower']);
            $tempfile = $pinfo['basename'];
            $fd = fopen($tempfile, 'wb');
            $dropbox->getFile($getFile['path_lower'], $fd, $vid);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($tempfile) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($tempfile));
            readfile($tempfile);
            unlink($tempfile);
            exit;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Change Order items in dropbox
     *
     * @param string  $move     Source file
     * @param string  $location Location move to
     * @param integer $parent   Parent id
     *
     * @return boolean|mixed
     */
    public function changeDropboxOrder($move, $location, $parent)
    {
        try {
            $dropbox = $this->getAccount();
            if ($parent !== 0) {
                $fpath = pathinfo($move);
                $baseMove = '/' . $fpath['basename'];
                $newlocation = $location . $baseMove;

                $result = $dropbox->move($move, $newlocation);
            } else {
                $pinfo = pathinfo($move);
                $basemove = '/' . $pinfo['basename'];
                $result = $dropbox->move($move, $basemove);
            }
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return $result;
    }

    /**
     * Get all folder in dropbox
     *
     * @return array|boolean
     */
    public function listAllFolders()
    {
        try {
            $dropbox = $this->getAccount();
            $listfolder = array();
            $folderMetadatas = $dropbox->getMetadataWithChildren('', true);

            if (count($folderMetadatas['entries']) > 0) {
                foreach ($folderMetadatas['entries'] as $f) {
                    if ($f['.tag'] === 'folder') {
                        $listfolder[$f['id']] = $f;
                    }
                }
            }
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }

        return $listfolder;
    }

    /**
     * Get path by id item
     *
     * @param array $diff_add Diff
     *
     * @return array|boolean
     */
    public function getPathById($diff_add)
    {
        try {
            $dropbox = $this->getAccount();
            $listPaths = array();
            foreach ($diff_add as $v) {
                $content = $dropbox->getMetadata($v);
                $listPaths[$content['path_lower']] = array('path' => $content['path_lower'],
                    'id' => $content['id'],
                    'name' => $content['name'],
                );
            }
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return $listPaths;
    }

    /**
     * Download item Dropbox
     *
     * @param string $id_file File id
     *
     * @return array
     */
    public function downloadDropbox($id_file)
    {
        $tempfile = JPATH_COMPONENT_ADMINISTRATOR . '/tmp';
        try {
            $dropbox = $this->getAccount();


            $fd = fopen($tempfile, 'wb');
            $fMeta = $dropbox->getFile($id_file, $fd);

            return array($tempfile, $fMeta);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            return false;
        }

        return array($tempfile, array());
    }


    /**
     * Get path file
     *
     * @param string $id File id
     *
     * @return mixed
     */
    public function getPathFile($id)
    {
        try {
            $dropbox = $this->getAccount();
            $meta = $dropbox->getMetadata($id);
            $fpath = pathinfo($meta['path_lower']);

            return $fpath['dirname'];
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            return '';
        }
    }

    /**
     * Get Dropbox TemporaryDirectLink
     *
     * @param string $id File id
     *
     * @return string
     */
    public function getTemporaryDirectLink($id)
    {
        try {
            $dropbox = $this->getAccount();
            $meta = $dropbox->getMetadata($id);
            $temporaryDirectLink  = $dropbox->createTemporaryDirectLink($meta['path_lower']);

            return $temporaryDirectLink;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            return false;
        }
    }

    /**
     * Get Dropbox parent folder id
     *
     * @param string $folderPath Folder path
     *
     * @throws Exception Fire if errors
     *
     * @return string
     */
    public function getDropboxParentFolderId($folderPath)
    {
        if (empty($folderPath)) {
            return null;
        }

        // Initialize client
        $client = $this->getAccount();

        try {
            $parentFolderPath = substr($folderPath, 0, strrpos($folderPath, '/'));

            if (!empty($parentFolderPath)) {
                // Get metadata for the specified folder
                $parentFolderMetadata = $client->getMetadata($parentFolderPath);
                return isset($parentFolderMetadata['id']) ? $parentFolderMetadata['id'] : null;
            } else {
                return null;
            }
        } catch (Exception $e) {
            // Handle any errors
            return null;
        }
    }
}
