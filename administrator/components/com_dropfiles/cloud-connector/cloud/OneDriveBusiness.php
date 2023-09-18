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

/**
 * Onedrive business connector class
 */
class OneDriveBusiness extends DropfilesCloudConnector
{
    /**
     * Init params variable
     *
     * @var array
     */
    private static $params = null;
    /**
     * Init option configuration variable
     *
     * @var string
     */
    private static $option_config = '';
    /**
     * Init connect mode option variable
     *
     * @var string
     */
    private static $connect_mode_option = 'joom_cloudconnector_onedrive_business_connect_mode';
    /**
     * Init network variable
     *
     * @var string
     */
    private $network = 'one-drive-business';
    /**
     * Init id button variable
     *
     * @var string
     */
    private $id_button = 'onedrive-business-connect';

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
        $mode   = $params->get('onedriveBusinessConnectMethod', 'manual');
        $bundle = isset($_GET['bundle']) ? json_decode(self::urlsafeB64Decode($_GET['bundle'])) : array();

        if (empty($bundle->onedriveBusinessKey) || empty($bundle->onedriveBusinessSecret)) {
            return false;
        }
        $option = $this->getConfig();
        if (!$option) {
            $option = array(
                'onedriveBusinessKey'         => '',
                'onedriveBusinessSecret'      => '',
                'onedriveBusinessSyncTime'    => '30',
                'onedriveBusinessSyncMethod'  => 'sync_page_curl',
                'onedriveBusinessConnectedBy' => (int)JFactory::getUser()->id,
                'state'                       => array(),
                'onedriveBusinessBaseFolder'  => array()
            );
        }

        $option['onedriveBusinessKey']         = $bundle->onedriveBusinessKey;
        $option['onedriveBusinessSecret']      = $bundle->onedriveBusinessSecret;
        $option['connected']                   = 1;
        $option['onedriveBusinessConnectedBy'] = (int)JFactory::getUser()->id;
        $option['state']                       = (!empty($bundle->onedriveBusinessState) ? $bundle->onedriveBusinessState : array());
        $option['onedriveBusinessBaseFolder']  = self::getBasefolder($option);

        // Save OneDrive Business configs
        $this->saveConfig($option);

        if ($mode !== 'automatic') {
            DropfilesComponentHelper::setParams(array('onedriveBusinessConnectMethod' => 'automatic'));
        }

        $app->redirect(JURI::root() . 'administrator/index.php?option=com_dropfiles&view=onedrivebusiness&layout=redirect');
    }

    /**
     * Display connect mode checkbox
     *
     * @return void
     */
    public function displayODBSettings()
    {
        $connect_mode_list = array(
            'automatic' => JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_AUTOMATIC_CONNECT_MODE'),
            'manual'    => JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_MANUAL_CONNECT_MODE')
        );

        $params = JComponentHelper::getParams('com_dropfiles');
        $onedrive_config = $this->getConfig();
        $config_mode = $params->get('onedriveBusinessConnectMethod', 'manual');

        if ($config_mode && $config_mode === 'automatic') {
            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.ju-settings-option.jform_onedriveBusinessKey\').hide();
                        $(\'.ju-settings-option.jform_onedriveBusinessSecret\').hide();
                        $(\'.btn-onedrivebusiness\').hide();
                        $(\'.odb-ju-connect-message\').show();
                        $(\'.ju-settings-option.jform_onedrivebusinessbtn\').css({\'padding\': \'0\'});
                        $(\'.ju-settings-option.jform_onedrivebusinessbtn .ju-setting-label\').hide();
                        $(\'.ju-settings-option.jform_onedrivebusinessbtn .ju-custom-block\').children().filter(\':not(#dropfiles-btnpush-onedrive-business)\').hide();
                    });
                </script>';

            if (!$onedrive_config || empty($onedrive_config['connected'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-onedrive-business\').addClass(\'ju-visibled\').show();
                        $(\'#dropfiles-btn-automaticdisconnect-onedrive-business\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($onedrive_config && !empty($onedrive_config['connected'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-onedrive-business\').removeClass(\'ju-visibled\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-onedrive-business\').addClass(\'ju-visibled\').show();
                    });
                </script>';
            }
        } else {
            if (!$onedrive_config || empty($onedrive_config['connected'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-onedrive-business\').addClass(\'ju-visibled\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-onedrive-business\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($onedrive_config && !empty($onedrive_config['connected'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-onedrive-business\').removeClass(\'ju-visibled\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-onedrive-business\').addClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.ju-settings-option.jform_onedriveBusinessKey\').show();
                        $(\'.ju-settings-option.jform_onedriveBusinessSecret\').show();
                        $(\'.btn-onedrivebusiness\').show();
                        $(\'#dropfiles-btn-automaticconnect-onedrive-business\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-onedrive-business\').hide();
                        $(\'.odb-ju-connect-message\').hide();
                        $(\'.ju-settings-option.jform_onedrivebusinessbtn\').css({\'padding\': \'10px 20px\'});
                        $(\'.ju-settings-option.jform_onedrivebusinessbtn .ju-setting-label\').show();
                        $(\'.ju-settings-option.jform_onedrivebusinessbtn .ju-custom-block\').children().filter(\':not(#dropfiles-btnpush-onedrive-business, style)\').show();
                    });
                </script>';
        }

        if ($this->checkJoomunitedConnected()) {
            $juChecked = true;
            $message = '<p>' . JText::sprintf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECTION_AUTOMATIC_MESSAGE1') . '</p>';
            $message .= '<p>' . JText::sprintf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECTION_AUTOMATIC_MESSAGE2') . '</p>';
        } else {
            $juChecked = false;
            $message = '<p>' . JText::sprintf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECTION_AUTOMATIC_MESSAGE3');
            $message .= '<strong>' . JText::sprintf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECTION_AUTOMATIC_MESSAGE4') . '</strong>';
            $message .= JText::sprintf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECTION_AUTOMATIC_MESSAGE5') . '  
            <strong>' . JText::sprintf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECTION_AUTOMATIC_MESSAGE6') . '</strong> 
             ' . JText::sprintf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECTION_AUTOMATIC_MESSAGE7') . '</p>';
            $message .= '<p>' . JText::sprintf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECTION_AUTOMATIC_MESSAGE8') . '</p>';
        }

        echo '<div id="onedrive_business_connect_mode">';
        echo '<div class="ju-odb-connect-mode full-width">';
        echo '<label class="ju-setting-label" for="">' . JText::sprintf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECTION_AUTOMATIC_MESSAGE9') . '</label>';
        echo '<div class="odb-mode-radio-field automatic-radio-group">';
        echo '<div class="ju-radio-group">';
        foreach ($connect_mode_list as $k => $v) {
            $checked = (!empty($config_mode) && $config_mode === $k) ? 'checked' : '';
            echo '<label><input type="radio" class="ju-radiobox" name="jform[onedriveBusinessConnectMethod]" value="'. $k .'" '. $checked .'><span>'. $v .'</span></label>';
        }
        echo '</div>';
        echo '<div class="odb-ju-connect-message">'.$message.'</div>';
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
        $link = str_replace('https', 'http', $link);
        $btnTooltip = ($juChecked === true) ? '' : JText::sprintf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECTION_AUTOMATIC_MESSAGE10');

        echo '<div id="dropfiles-btn-automaticconnect-onedrive-business" class="cloud-connector-title" title="'. $btnTooltip .'">';
        echo '<a class="ju-button onedrive-business-automatic-connect '.($juChecked ? '' : 'ju-disconnected-autoconnect').'" href="#"
                name="' . $prefix . $id_button . '" 
                id="' . $prefix . $id_button . '" 
                data-network="' . $network . '" 
                data-link="' . $this->urlsafeB64Encode($link) . '" >';
        echo '<img class="automatic-connect-icon" src="' . JURI::root() . '/components/com_dropfiles/assets/images/onedrive_white.png" alt=""/>';
        echo '<span class="btn-title">' . JText::sprintf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECTION_AUTOMATIC_CONNECT_ONEDRIVE_BUSINESS') . '</span></a>';
        echo '</div>';

        echo '<div id="dropfiles-btn-automaticdisconnect-onedrive-business" class="cloud-connector-title" title="'. $btnTooltip .'">';
        echo '<a class="ju-button onedrive-business-automatic-disconnect '.($juChecked ? '' : 'ju-disconnected-autoconnect').'"   
        href="index.php?option=com_dropfiles&task=onedrivebusiness.logout" data-network="' . $network . '">';
        echo '<img class="automatic-connect-icon" src="' . JURI::root() . '/components/com_dropfiles/assets/images/onedrive-business-disconnect.svg" alt=""/>';
        echo '<span class="btn-title">' . JText::sprintf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECTION_AUTOMATIC_DISCONNECT_ONEDRIVE_BUSINESS'). '</span></a>';
        echo '</div>';
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
            require_once JPATH_ADMINISTRATOR  . '/components/com_dropfiles/classes/OneDriveBusiness/packages/autoload.php';
            $client = new Client(
                $option['onedriveBusinessKey'],
                new Graph(),
                new GuzzleHttpClient(),
                \Krizalys\Onedrive\Onedrive::buildServiceDefinition(),
                array(
                    'state' => isset($option['state']) && !empty($option['state']) ? $option['state'] : array()
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

            $baseFolder = array();
            if (empty($option['onedriveBusinessBaseFolder'])) {
                $folderName = 'Dropfiles Automatic - ' . $blogname;
                $folderName = preg_replace('@["*:<>?/\\|]@', '', $folderName);

                try {
                    $root = $client->getRoot()->createFolder($folderName);
                    $baseFolder = array(
                        'id'   => $root->id,
                        'name' => $root->name
                    );
                } catch (ConflictException $e) {
                    $root = $client->getDriveItemByPath('/' . $folderName);
                    $baseFolder = array(
                        'id'   => $root->id,
                        'name' => $root->name
                    );
                }
            } else {
                try {
                    $root = $graph
                        ->createRequest('GET', '/me/drive/items/' . $option['onedriveBusinessBaseFolder']->id)
                        ->setReturnType(Model\DriveItem::class) // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- For testing
                        ->execute();
                    $baseFolder = array(
                        'id'   => $root->getId(),
                        'name' => $root->getName()
                    );
                } catch (\Exception $ex) {
                    $folderName = 'Dropfiles Automatic - ' . $blogname;
                    $folderName = preg_replace('@["*:<>?/\\|]@', '', $folderName);
                    $folderName = rtrim($folderName);
                    $results    = $graph->createRequest('GET', '/me/drive/search(q=\'' . $folderName . '\')')
                        ->setReturnType(Model\DriveItem::class) // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- For testing
                        ->execute();
                    if (isset($results[0])) {
                        $root       = new \stdClass;
                        $root->id   = $results[0]->getId();
                        $root->name = $results[0]->getName();
                    } else {
                        $root = $client->getRoot()->createFolder($folderName);
                    }

                    $baseFolder = array(
                        'id'   => $root->id,
                        'name' => $root->name
                    );
                }
            }

            return $baseFolder;
        } catch (\Exception $ex) {
            return array();
        }
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

        return $config;
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
            if (isset($config[''])) {
                unset($config['onedriveBusinessState']);
            }
            unset($config['onedriveBusinessLogout']);
        }

        DropfilesComponentHelper::setParams($config);
    }
}
