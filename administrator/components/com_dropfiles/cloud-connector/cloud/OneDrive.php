<?php
// no direct access
defined('_JEXEC') || die;

use GuzzleHttp\Client as GuzzleHttpClient;
use Krizalys\Onedrive\Client as Client;
use Krizalys\Onedrive\Exception\ConflictException;
use Microsoft\Graph\Exception\GraphException;
use Microsoft\Graph\Graph;
use Krizalys\Onedrive\Proxy\FileProxy;
use Microsoft\Graph\Model\DriveItem;
use Microsoft\Graph\Model;
use Microsoft\Graph\Model\UploadSession;
use Krizalys\Onedrive\Constant\ConflictBehavior;
use Krizalys\Onedrive\Constant\AccessTokenStatus;

if (!class_exists('DropfilesCloudConnector')) {
    JLoader::register('DropfilesCloudConnector', JPATH_ADMINISTRATOR . '/components/com_dropfiles/cloud-connector/CloudConnector.php');
}

if (!class_exists('DropfilesCloudHelper')) {
    JLoader::register('DropfilesCloudHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/dropfilescloud.php');
}

/**
 * Onedrive connector class
 */
class OneDrive extends DropfilesCloudConnector
{
    /**
     * Init params variable
     *
     * @var array
     */
    public $params = null;
    /**
     * Init option configuration variable
     *
     * @var string
     */
    public $option_config = '';
    /**
     * Init old option configuration variable
     *
     * @var string
     */
    public $old_option_config = '';
    /**
     * Init connect mode option variable
     *
     * @var string
     */
    public $connect_mode_option = 'joom_cloudconnector_onedrive_connect_mode';
    /**
     * Init network variable
     *
     * @var string
     */
    public $network = 'one-drive';
    /**
     * Init id button variable
     *
     * @var string
     */
    public $id_button = 'onedrive-connect';

    /**
     * Connect function
     *
     * @return mixed
     */
    public function connect()
    {
        $app = JFactory::getApplication();
        if (!class_exists('DropfilesComponentHelper')) {
            $path_admin_component = JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/component.php';
            JLoader::register('DropfilesComponentHelper', $path_admin_component);
        }
        $params = JComponentHelper::getParams('com_dropfiles');
        $mode   = $params->get('onedriveConnectMethod', 'manual');
        $bundle = isset($_GET['bundle']) ? json_decode($this->urlsafeB64Decode($_GET['bundle'])) : array();
        if (empty($bundle->onedriveKey) || empty($bundle->onedriveSecret)) {
            return false;
        }
        $oneDriveParams = $this->getAllOneDriveConfigs();
        if (!$oneDriveParams) {
            $oneDriveParams = array(
                'onedriveKey'            => '',
                'onedriveSecret'         => '',
                'onedriveCredentials'    => '',
                'onedriveConnected'      => '0',
                'onedriveBaseFolderId'   => '',
                'onedriveBaseFolderName' => '',
                'onedriveSyncTime'       => '30',
                'onedrive_last_log'      => '',
                'onedriveSyncMethod'     => 'sync_page_curl'
            );
        }

        $oneDriveParams['onedriveKey']            = $bundle->onedriveKey;
        $oneDriveParams['onedriveSecret']         = $bundle->onedriveSecret;
        $oneDriveParams['onedriveCredentials']    = json_encode($bundle->onedriveState->token->data);
        $oneDriveParams['onedriveConnected']      = 1;
        $oneDriveParams['onedriveState']          = (!empty($bundle->onedriveState) ? $bundle->onedriveState : array());
        $oneDriveBaseFolder                       = $this->getBasefolder($oneDriveParams);
        $oneDriveParams['onedriveBaseFolderId']   = ($oneDriveBaseFolder['id']) ? $oneDriveBaseFolder['id'] : '';
        $oneDriveParams['onedriveBaseFolderName'] = ($oneDriveBaseFolder['name']) ? $oneDriveBaseFolder['name'] : '';

        // Save OneDrive params
        $this->saveParams($oneDriveParams);

        if ($mode !== 'automatic') {
            DropfilesComponentHelper::setParams(array('onedriveConnectMethod' => 'automatic'));
        }
        $app->redirect(JURI::root() . 'administrator/index.php?option=com_dropfiles&view=onedrive&layout=redirect');
    }

    /**
     * Display connect mode checkbox
     *
     * @return void
     */
    public function displayODSettings()
    {
        // phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain -- It is string from object
        $connect_mode_list = array(
            'automatic' => JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_AUTOMATIC_CONNECT_MODE'),
            'manual' => JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_MANUAL_CONNECT_MODE')
        );
        $params = JComponentHelper::getParams('com_dropfiles');
        $onedrive_config = $this->getAllOneDriveConfigs();
        $onedrive_config['onedriveConnected'] = $params->get('onedriveConnected', 0);
        $config_mode = $params->get('onedriveConnectMethod', 'manual');

        if ($config_mode && $config_mode === 'automatic') {
            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.ju-settings-option.jform_onedriveKey\').hide();
                        $(\'.ju-settings-option.jform_onedriveSecret\').hide();
                        $(\'.ju-settings-option.jform_onedrivebtn .btn-onedrive\').hide();
                        $(\'.od-ju-connect-message\').show();
                        $(\'.ju-settings-option.jform_onedrivebtn\').css({\'padding\': \'0\'});
                        $(\'.ju-settings-option.jform_onedrivebtn .ju-setting-label\').hide();
                        $(\'.ju-settings-option.jform_onedrivebtn .ju-custom-block\').children().filter(\':not(.btn-onedrive)\').hide();
                    });
                </script>';

            if (!$onedrive_config || empty($onedrive_config['onedriveConnected']) || empty($onedrive_config['onedriveCredentials'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-onedrive\').addClass(\'ju-visibled\').show();
                        $(\'#dropfiles-btn-automaticdisconnect-onedrive\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($onedrive_config && !empty($onedrive_config['onedriveConnected']) && !empty($onedrive_config['onedriveCredentials'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-onedrive\').removeClass(\'ju-visibled\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-onedrive\').addClass(\'ju-visibled\').show();
                    });
                </script>';
            }
        } else {
            if (!$onedrive_config || empty($onedrive_config['onedriveConnected'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-onedrive\').addClass(\'ju-visibled\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-onedrive\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if (!$onedrive_config || empty($onedrive_config['onedriveKey']) || empty($onedrive_config['onedriveSecret'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.ju-settings-option.jform_onedrivebtn .btn-onedrive\').addClass(\'ju-no-configs\');
                    });
                </script>';
            }

            if ($onedrive_config && !empty($onedrive_config['onedriveConnected'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-onedrive\').removeClass(\'ju-visibled\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-onedrive\').addClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.od-ju-connect-message\').hide();
                        $(\'.ju-settings-option.jform_onedrivebtn\').css({\'padding\': \'10px 20px\'});
                        $(\'.ju-settings-option.jform_onedrivebtn .ju-setting-label\').show();
                        $(\'.ju-settings-option.jform_onedrivebtn .ju-custom-block\').children().filter(\':not(.btn-onedrive, style)\').show();
                    });
                </script>';
        }

        if ($this->checkJoomunitedConnected()) {
            $juChecked = true;
            $message = '<p>'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_ONEDRIVE_CONNECTION_AUTOMATIC_MESSAGE1') .'</p>';
            $message .= '<p>'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_ONEDRIVE_CONNECTION_AUTOMATIC_MESSAGE2') .'</p>';
        } else {
            $juChecked = false;
            $message = '<p>'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_ONEDRIVE_CONNECTION_AUTOMATIC_MESSAGE3');
            $message .= '<strong>'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_ONEDRIVE_CONNECTION_AUTOMATIC_MESSAGE4') .'</strong>';
            $message .= JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_ONEDRIVE_CONNECTION_AUTOMATIC_MESSAGE5') .
                ' <strong>' . JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_ONEDRIVE_CONNECTION_AUTOMATIC_MESSAGE6') . '</strong> '
                . JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_ONEDRIVE_CONNECTION_AUTOMATIC_MESSAGE7') .'</p>';
            $message .= '<p>' . JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_ONEDRIVE_CONNECTION_AUTOMATIC_MESSAGE8') . '</p>';
        }

        echo '<div id="onedrive_connect_mode">';
        echo '<div class="ju-od-connect-mode full-width">';
        echo '<label class="ju-setting-label" for="">'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_CONNECTING_MODE_LABEL') .'</label>';
        echo '<div class="od-mode-radio-field automatic-radio-group">';
        echo '<div class="ju-radio-group">';
        foreach ($connect_mode_list as $k => $v) {
            $checked = (!empty($config_mode) && $config_mode === $k) ? 'checked' : '';
            echo '<label><input type="radio" class="ju-radiobox" name="jform[onedriveConnectMethod]" value="'.$k.'" '.$checked.'><span>'.$v.'</span></label>';
        }
        echo '</div>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- String is escaped
        echo '<div class="od-ju-connect-message">'.$message.'</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        $this->connectButton($this->network, $this->id_button, $juChecked);
    }

    /**
     * Display button connect
     *
     * @param string $network   Network type
     * @param string $id_button Id of button
     * @param string $juChecked Junited connect checked
     *
     * @return void
     */
    public function connectButton($network, $id_button, $juChecked)
    {
        $current_url = ($this->is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $type = 'dropfiles';
        $prefix = $type . '_';
        $link = JURI::base() . 'index.php?option=com_dropfiles&task=config.cloudAutoConnect&cloudconnector=1';
        $link .= '&network=' . $network;
        $link .= '&plugin_type=' . $type;
        $link .= '&current_backlink=' . $this->urlsafeB64Encode($current_url);
        $link .= '&cloudconnect_nonce=' . hash('md5', '_cloudconnect_nonce');
        $btnTooltip = ($juChecked === true) ? '' : JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_ONEDRIVE_CONNECTION_AUTOMATIC_MESSAGE9');

        echo '<div id="dropfiles-btn-automaticconnect-onedrive" class="cloud-connector-title" title="'.$btnTooltip .'">';
        echo '<a class="ju-button onedrive-automatic-connect '.($juChecked ? '' : 'ju-disconnected-autoconnect').'" href="#"
                name="' . $prefix . $id_button . '" 
                id="' . $prefix . $id_button . '" 
                data-network="' . $network . '" 
                data-link="' . $this->urlsafeB64Encode($link) . '" >';
        echo '<img class="automatic-connect-icon" src="' . JURI::root() . '/components/com_dropfiles/assets/images/icon-onedrive.svg" alt=""/>';
        echo '<span class="btn-title">' . JTEXT::sprintf('COM_DROPFILES_CONFIG_CLOUD_ONEDRIVE_CONNECTION_AUTOMATIC_CONNECT_ONEDRIVE'). '</span></a>';
        echo '</div>';

        echo '<div id="dropfiles-btn-automaticdisconnect-onedrive" class="cloud-connector-title" title="'. $btnTooltip .'">';
        echo '<a class="ju-button onedrive-automatic-disconnect '.($juChecked ? '' : 'ju-disconnected-autoconnect')
            .'" href="index.php?option=com_dropfiles&task=onedrive.logout" data-network="' . $network . '">';
        echo '<img class="automatic-connect-icon" src="' . JURI::root() . '/components/com_dropfiles/assets/images/icon-onedrive.svg" alt=""/>';
        echo '<span class="btn-title">' . JTEXT::sprintf('COM_DROPFILES_CONFIG_CLOUD_ONEDRIVE_CONNECTION_AUTOMATIC_CONNECT_DISCONNECT_ONEDRIVE'). '</span></a>';
        echo '</div>';
        // phpcs:enable
    }

    /**
     * Get base folder id
     *
     * @param array $option Option config
     *
     * @return array
     */
    public function getBasefolder($option)
    {
        try {
            require_once JPATH_ADMINISTRATOR  . '/components/com_dropfiles/classes/vendor/autoload.php';
            $client = new Client(
                $option['onedriveKey'],
                new Graph(),
                new GuzzleHttpClient(),
                \Krizalys\Onedrive\Onedrive::buildServiceDefinition(),
                array(
                    'state' => isset($option['onedriveState']) && !empty($option['onedriveState']) ? $option['onedriveState'] : array()
                )
            );

            $config = JFactory::getConfig();
            $blogname = ($config->get('sitename')) ? $config->get('sitename') : '';
            $blogname = trim(str_replace(array(':', '~', '"', '%', '&', '*', '<', '>', '?', '/', '\\', '{', '|', '}'), '', $blogname));

            // Fix onedrive bug, last folder name can not be a dot
            if (substr($blogname, -1) === '.') {
                $blogname = substr($blogname, 0, strlen($blogname) - 1);
            }

            $graph = new Graph();
            $graph->setAccessToken($client->getState()->token->data->access_token);
            $basefolder = array();

            if (empty($option['onedriveBaseFolderId'])) {
                $folderName = 'Dropfiles Automatic - ' . $blogname;
                $folderName = preg_replace('@["*:<>?/\\|]@', '', $folderName);
                $folderName = rtrim($folderName);

                try {
                    $root = $client->getRoot()->createFolder($folderName);
                    $basefolder = array(
                        'id'    => $root->id,
                        'name'  => $root->name
                    );
                } catch (ConflictException $e) {
                    $root = $client->getDriveItemByPath('/' . $folderName);
                    $basefolder = array(
                        'id'    => $root->id,
                        'name'  => $root->name
                    );
                }
            } else {
                try {
                    $root = $graph
                        ->createRequest('GET', '/me/drive/items/' . $option['onedriveBaseFolderId'])
                        ->setReturnType(Model\DriveItem::class) // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- For testing
                        ->execute();
                    $basefolder = array(
                        'id'    => $root->getId(),
                        'name'  => $root->getName()
                    );
                } catch (\Exception $ex) {
                    $folderName = 'Dropfiles Automatic - ' . $blogname;
                    $folderName = preg_replace('@["*:<>?/\\|]@', '', $folderName);
                    $folderName = rtrim($folderName);
                    $results = $graph->createRequest('GET', '/me/drive/search(q=\'' . $folderName . '\')')
                        ->setReturnType(Model\DriveItem::class) // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- For testing
                        ->execute();
                    if (isset($results[0])) {
                        $root       = new \stdClass;
                        $root->id   = $results[0]->getId();
                        $root->name = $results[0]->getName();
                    } else {
                        $root = $client->getRoot()->createFolder($folderName);
                    }

                    $basefolder = array(
                        'id'    => $root->id,
                        'name'  => $root->name
                    );
                }
            }

            return $basefolder;
        } catch (\Exception $ex) {
            return array();
        }
    }

    /**
     * Load Params in config
     *
     * @return array|mixed
     */
    public function loadParams()
    {
        $params = JComponentHelper::getParams('com_dropfiles');
        $oneDriveParams = array();
        $oneDriveParams['onedriveKey'] = $params->get('onedriveKey');
        $oneDriveParams['onedriveSecret'] = $params->get('onedriveSecret');
        $oneDriveParams['onedriveCredentials'] = $params->get('onedriveCredentials');
        $oneDriveParams['onedriveConnected'] = $params->get('onedriveConnected');
        $oneDriveParams['onedriveBaseFolderId'] = $params->get('onedriveBaseFolderId');
        $oneDriveParams['onedriveBaseFolderName'] = $params->get('onedriveBaseFolderName');

        return $oneDriveParams;
    }

    /**
     * Save params
     *
     * @param array $params OneDrive values
     *
     * @return void
     */
    public function saveParams($params)
    {
        $oneDriveParams = $this->getAllOneDriveConfigs();
        $oneDriveParams['onedriveKey'] = $params['onedriveKey'];
        $oneDriveParams['onedriveSecret'] = $params['onedriveSecret'];
        $oneDriveParams['onedriveCredentials'] = $params['onedriveCredentials'];
        $oneDriveParams['onedriveBaseFolderId'] = $params['onedriveBaseFolderId'];
        $oneDriveParams['onedriveBaseFolderName'] = $params['onedriveBaseFolderName'];
        $oneDriveParams['onedriveState'] = $params['onedriveState'];
        $oneDriveParams['onedriveConnected'] = $params['onedriveConnected'];
        $this->saveOneDriveConfigs($oneDriveParams);
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
     * Get config old param onedrive
     *
     * @return array
     * @since  version
     */
    public function getAllOneDriveConfigsOld()
    {
        return DropfilesCloudHelper::getAllOneDriveConfigsOld();
    }

    /**
     * Get param config OneDrive
     *
     * @param string $name Name
     *
     * @return array|null
     */
    public function getDataConfigByOneDrive($name)
    {
        return DropfilesCloudHelper::getDataConfigByOneDrive($name);
    }

    /**
     * Save OneDrive Configs
     *
     * @param array $data Data
     *
     * @return boolean
     */
    public function saveOneDriveConfigs($data)
    {
        return DropfilesCloudHelper::setParamsConfigs($data);
    }

    /**
     * Add new root folder
     *
     * @param DropfilesOneDrive $onedrive Onedrive instance
     *
     * @return OneDrive_Service_Drive_Item
     * @throws Exception Throw when application can not start
     * @since  version
     */
    public function newEntryOnedrive($onedrive)
    {
        $config = JFactory::getConfig();
        $blogname = ($config->get('sitename')) ? $config->get('sitename') : '';
        $folderName = 'Dropfiles Automatic - ' . $blogname;
        $folderName = preg_replace('@["*:<>?/\\|]@', '', $folderName);
        $folderName = rtrim($folderName);

        $newEntry = $this->addFolderRoot($folderName);
        $decoded = json_decode($newEntry['responsebody'], true);
        return new OneDrive_Service_Drive_Item($decoded);
    }

    /**
     * Save config old param onedrive
     *
     * @param array $data Data
     *
     * @return void
     * @since  version
     */
    public function setParamsOld($data)
    {
        DropfilesCloudHelper::setParamsConfigsOld($data);
    }

    /**
     * Save config param onedrive
     *
     * @param array $data Data
     *
     * @return void
     * @since  version
     */
    public function setParams($data)
    {
        DropfilesCloudHelper::setParamsConfigs($data);
    }

    /**
     * Create new root folder
     *
     * @param null|string $new_folder New folder name
     *
     * @return boolean|OneDrive_Service_Drive_Item
     */
    public function addFolderRoot($new_folder = null)
    {
        $params = $this->params;
        $client = new OneDrive_Client();
        $client->setClientId($params['onedriveKey']);
        $client->setClientSecret($params['onedriveSecret']);
        $credentials = $params['onedriveCredentials'];
        $client->setAccessToken($credentials);
        if ($client === null) {
            return false;
        }
        $service = new OneDrive_Service_Drive($client);
        /* Create new folder object */
        $newFolder = new OneDrive_Service_Drive_Item();
        $newFolder->setName($new_folder);
        $newFolder->setFolder(new OneDrive_Service_Drive_FolderFacet());
        $newFolder['@name.conflictBehavior'] = 'rename';

        /* Do the insert call */
        $newentry = null;
        try {
            $newentry = $service->items->insert('root', $newFolder);
        } catch (Exception $ex) {
            $erros = $ex->getMessage() . $ex->getTraceAsString() . PHP_EOL;
            JLog::add($erros, JLog::ERROR, 'com_dropfiles');
        }

        if ($newentry) {
            return $newentry;
        } else {
            return null;
        }
    }
}
