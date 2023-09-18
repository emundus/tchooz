<?php
// no direct access
defined('_JEXEC') || die;

if (!class_exists('DropfilesCloudConnector')) {
    require_once(JPATH_ADMINISTRATOR . '/components/com_dropfiles/cloud-connector/CloudConnector.php');
}

/**
 * Google drive class
 */
class GoogleDrive extends DropfilesCloudConnector
{
    /**
     * Init params variable
     *
     * @var array
     */
    private $params;
    /**
     * Init option configuration variable
     *
     * @var string
     */
    private $option_config;
    /**
     * Init old option configuration variable
     *
     * @var string
     */
    private $old_option_config;
    /**
     * Init connect mode option variable
     *
     * @var string
     */
    private $connect_mode_option = 'joom_cloudconnector_ggd_connect_mode';
    /**
     * Init network variable
     *
     * @var string
     */
    private $network = 'google-drive';
    /**
     * Init id button variable
     *
     * @var string
     */
    private $id_button = 'ggdrive-connect';
    /**
     * Default redirect uri
     *
     * @var string
     */
    private $default_redirect_uri = 'https://connector.joomunited.com/cloudconnector/google-drive/auth-callback';

    /**
     * Display connect mode checkbox
     *
     * @return string
     */
    public function displayGGDSettings()
    {
        $connect_mode_list = array(
            'automatic' => JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_AUTOMATIC_CONNECT_MODE'),
            'manual'    => JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_MANUAL_CONNECT_MODE')
        );
        $params = JComponentHelper::getParams('com_dropfiles');
        $config_mode = isset($params['googleConnectMethod']) ? $params['googleConnectMethod'] : 'manual';
        $ggd_config = $this->loadParams();

        $content = '';
        if ($config_mode && $config_mode === 'automatic') {
            $content .= '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.ju-settings-option.jform_google_client_id\').hide();
                        $(\'.ju-settings-option.jform_google_client_secret\').hide();
                        $(\'.jform_googlebtn .btn-google\').hide();
                        $(\'.ggd-ju-connect-message\').show();
                        $(\'.ju-settings-option.jform_googlebtn\').css({\'padding\': \'0\'});
                        $(\'.ju-settings-option.jform_googlebtn .ju-setting-label\').hide();
                        $(\'.ju-settings-option.jform_googlebtn .ju-custom-block\').children().filter(\':not(#dropfiles_btn_google_changes)\').hide();
                    });
                </script>';

            if (!$ggd_config || empty($ggd_config['google_credentials'])) {
                $content .= '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-ggd\').addClass(\'ju-visibled\').show();
                        $(\'#dropfiles-btn-automaticdisconnect-ggd\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($ggd_config && !empty($ggd_config['google_credentials'])) {
                $content .= '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-ggd\').removeClass(\'ju-visibled\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-ggd\').addClass(\'ju-visibled\').show();
                    });
                </script>';
            }
        } else {
            if (!$ggd_config || empty($ggd_config['google_credentials'])) {
                $content .= '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-ggd\').addClass(\'ju-visibled\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-ggd\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($ggd_config && !empty($ggd_config['google_credentials'])) {
                $content .= '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-ggd\').removeClass(\'ju-visibled\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-ggd\').addClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            $content .= '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.ggd-ju-connect-message\').hide();
                        $(\'.ju-settings-option.jform_google_client_id\').show();
                        $(\'.ju-settings-option.jform_google_client_secret\').show();
                        $(\'.ju-settings-option.jform_googlebtn\').css({\'padding\': \'10px 20px\'});
                        $(\'.ju-settings-option.jform_googlebtn .ju-setting-label\').show();
                        $(\'.ju-settings-option.jform_googlebtn .ju-custom-block\').children().filter(\':not(#dropfiles_btn_google_changes, style)\').show();
                    });
                </script>';
        }

        if ($this->checkJoomunitedConnected()) {
            $juChecked = true;
            $message = '<p>'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_GOOGLE_CONNECTION_AUTOMATIC_MESSAGE1') .'</p>';
            $message .= '<p>'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_GOOGLE_CONNECTION_AUTOMATIC_MESSAGE2') .'</p>';
        } else {
            $juChecked = false;
            $message = '<p>'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_GOOGLE_CONNECTION_AUTOMATIC_MESSAGE3');
            $message .= '<strong>'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_GOOGLE_CONNECTION_AUTOMATIC_MESSAGE4') .'</strong>';
            $message .= JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_GOOGLE_CONNECTION_AUTOMATIC_MESSAGE5').
                ' <strong>'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_GOOGLE_CONNECTION_AUTOMATIC_MESSAGE6')
                .'</strong> '. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_GOOGLE_CONNECTION_AUTOMATIC_MESSAGE7') .'</p>';
            $message .= '<p>'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_GOOGLE_CONNECTION_AUTOMATIC_MESSAGE8') .'</p>';
        }

        $content .= '<div id="ggd_connect_mode">';
        $content .= '<div class="ju-ggd-connect-mode full-width">';
        $content .= '<label class="ju-setting-label" for="">'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_CONNECTING_MODE_LABEL') .'</label>';
        $content .= '<div class="ggd-mode-radio-field automatic-radio-group">';
        $content .= '<div class="ju-radio-group">';
        foreach ($connect_mode_list as $k => $v) {
            $checked = (!empty($config_mode) && $config_mode === $k) ? 'checked' : '';
            $content .= '<label><input type="radio" class="ju-radiobox" name="jform[googleConnectMethod]" value="'.$k.'" '.$checked.'><span>'.$v.'</span></label>';
        }
        $content .= '</div>';
        $content .= '<div class="ggd-ju-connect-message">'.$message.'</div>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        $content .= $this->connectButton($this->network, $this->id_button, $juChecked);

        return $content;
    }

    /**
     * Display button connect
     *
     * @param string $network   Network type
     * @param string $id_button Id of button
     * @param string $juChecked Junited connect checked
     *
     * @return string
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
        $content = '<div id="dropfiles-btn-automaticconnect-ggd" class="cloud-connector-title">';
        $content .= '<a class="ju-button ggd-automatic-connect '.($juChecked ? '' : 'ju-disconnected-autoconnect').'" href="#"
                name="' . $prefix . $id_button . '"
                id="' . $prefix . $id_button . '"
                data-network="' . $network . '"
                data-link="' . $this->urlsafeB64Encode($link) . '" >';
        $content .= '<img class="automatic-connect-icon" src="' . JURI::root() . '/components/com_dropfiles/assets/images/drive-icon-colored.png" alt=""/>';
        $content .= '<span class="btn-title">' . JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_AUTOMATIC_CONNECT_BUTTON') . '</span></a>';
        $content .= '</div>';

        $content .= '<div id="dropfiles-btn-automaticdisconnect-ggd" class="cloud-connector-title"">';
        $content .= '<a class="ju-button ggd-automatic-disconnect '.($juChecked ? '' : 'ju-disconnected-autoconnect').'" ';
        $content .= 'href="index.php?option=com_dropfiles&task=googledrive.logout" data-network="' . $network . '">';
        $content .= '<img class="automatic-connect-icon" src="' . JURI::root() . '/components/com_dropfiles/assets/images/drive-icon-colored.png" alt=""/>';
        $content .= '<span class="btn-title">' . JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_AUTOMATIC_DISCONNECT_BUTTON') . '</span></a>';
        $content .= '</div>';

        return $content;
    }

    /**
     * Connect function
     *
     * @throws Exception Fire if errors
     *
     * @return mixed
     */
    public function connect()
    {
        $app = JFactory::getApplication();
        $path_admin_component = JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/component.php';
        JLoader::register('DropfilesComponentHelper', $path_admin_component);
        $bundle = isset($_GET['bundle']) ? json_decode(self::urlsafeB64Decode($_GET['bundle']), true) : array();

        if (!$bundle || empty($bundle['client_id']) || empty($bundle['client_secret'])) {
            return false;
        }

        DropfilesComponentHelper::setParams(array(
            'googleConnectMethod' => 'automatic'
        ));

        $googleParams = $this->loadParams();
        if (!$googleParams) {
            $googleParams = array(
                'google_client_id' => '',
                'google_client_secret' => '',
                'google_credentials' => '',
            );
        }
        $googleParams['google_client_id'] = $bundle['client_id'];
        $googleParams['google_client_secret'] = $bundle['client_secret'];
        $googleParams['google_credentials'] = json_encode($bundle);
        $googleParams['google_base_folder'] = $this->getBasefolder($bundle);

        $this->saveParams($googleParams);
        $app->redirect(JURI::root() . 'administrator/index.php?option=com_dropfiles&view=googledrive&layout=redirect');
    }

    /**
     * Load Params in config
     *
     * @return array|mixed
     */
    public function loadParams()
    {
        $params = JComponentHelper::getParams('com_dropfiles');
        $googleParams = array();
        $googleParams['google_client_id'] = $params->get('google_client_id');
        $googleParams['google_client_secret'] = $params->get('google_client_secret');
        $googleParams['google_credentials'] = $params->get('google_credentials');
        $googleParams['google_base_folder'] = $params->get('google_base_folder');

        return $googleParams;
    }

    /**
     * Save params
     *
     * @param array $params Google values
     *
     * @return void
     */
    public function saveParams($params)
    {
        $path_admin_component = JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/component.php';
        JLoader::register('DropfilesComponentHelper', $path_admin_component);
        DropfilesComponentHelper::setParams(array(
            'google_client_id'     => $params['google_client_id'],
            'google_client_secret' => $params['google_client_secret'],
            'google_credentials'   => $params['google_credentials'],
            'google_base_folder'   => $params['google_base_folder']
        ));
    }

    /**
     * Determines if SSL is used.
     *
     * @since 2.6.0
     * @since 4.6.0 Moved from functions.php to load.php.
     *
     * @return boolean True if SSL, otherwise false.
     */
    public function is_ssl() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- For matching
    {
        if (isset($_SERVER['HTTPS'])) {
            if ('on' === strtolower($_SERVER['HTTPS'])) {
                return true;
            }

            // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- For testing
            if ('1' == $_SERVER['HTTPS']) {
                return true;
            }
            // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- For testing
        } elseif (isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] )) {
            return true;
        }
        return false;
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    public function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * Get base folder id
     *
     * @param array $authenticate Author
     *
     * @throws Exception Message if errors
     *
     * @return string
     */
    public function getBasefolder($authenticate)
    {
        require_once JPATH_ADMINISTRATOR  . '/components/com_dropfiles/classes/GoogleV3/packages/autoload.php';
        $google_client = new Google_Client();
        $google_client->setClientId($authenticate['client_id']);
        $google_client->setClientSecret($authenticate['client_secret']);
        $google_client->setAccessToken($authenticate);

        $data_old = $this->loadParams();

        $check_root_folder = false;
        if (!empty($data_old['google_base_folder'])) {
            $check_root_folder = $this->folderExists($google_client, $data_old['google_base_folder']);
        }

        if ($check_root_folder && !empty($data_old['google_client_id']) && $authenticate['client_id'] === $data_old['google_client_id']) {
            $googleBaseFolder = $data_old['google_base_folder'];
        } else {
            $googleBaseFolder = $this->createFolder($google_client);
        }

        return $googleBaseFolder;
    }

    /**
     * Folder exists
     *
     * @param object $client Googledrive client
     * @param string $id     Folder id
     *
     * @return boolean
     */
    public function folderExists($client, $id)
    {
        try {
            $service = new Google_Service_Drive($client);
            $service->files->get($id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create new folder google drive
     *
     * @param object $client Service
     *
     * @throws Exception Fire if errors
     *
     * @return mixed
     */
    public function createFolder($client)
    {
        $config = JFactory::getConfig();
        $title = 'Dropfiles - ' . $config->get('sitename') . ' - Automatic connect';
        $file = new Google_Service_Drive_DriveFile();
        $file->setName($title);
        $file->setMimeType('application/vnd.google-apps.folder');

        try {
            $service = new Google_Service_Drive($client);
            $fileId = $service->files->create($file, array('fields' => 'id, name'));

            return $fileId->id;
        } catch (\Exception $e) {
            throw new \Exception('Something went wrong when get google base folder id');
        }
    }
}
