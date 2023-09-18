<?php
// no direct access
defined('_JEXEC') || die;

if (!class_exists('DropfilesCloudConnector')) {
    JLoader::register('DropfilesCloudConnector', JPATH_ADMINISTRATOR . '/components/com_dropfiles/cloud-connector/CloudConnector.php');
}

/**
 * Dropbox connector class
 */
class Dropbox extends DropfilesCloudConnector
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
     * Init connect mode option variable
     *
     * @var string
     */
    public $connect_mode_option = 'joom_cloudconnector_dropbox_connect_mode';
    /**
     * Init network variable
     *
     * @var string
     */
    public $network = 'dropbox';
    /**
     * Init id button variable
     *
     * @var string
     */
    public $id_button = 'dropbox-connect';

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
        $mode = $params->get('dropboxConnectMethod', 'manual');
        $bundle = isset($_GET['bundle']) ? json_decode(self::urlsafeB64Decode($_GET['bundle'])) : array();

        if (empty($bundle->app_key) || empty($bundle->app_secret)) {
            return false;
        }
        $dropboxParams = $this->loadParams();
        if (!$dropboxParams) {
            $dropboxParams = array(
                'dropbox_key'         => '',
                'dropbox_secret'      => '',
                'dropboxAccessToken'  => '',
                'dropbox_sync_method' => 'dropbox_sync_page_curl_ajax',
                'dropbox_sync_time'   => '30',
                'dropbox_last_log'    => date('Y-m-d H:i:s'),
                'dropboxBaseFolderId' => '',
            );
        }

        $dropboxParams['dropbox_key']                = $bundle->app_key;
        $dropboxParams['dropbox_secret']             = $bundle->app_secret;
        $dropboxParams['dropboxAccessToken']         = (!empty($bundle->accessToken) ? $bundle->accessToken : '');
        $dropboxParams['dropbox_last_log']           = date('Y-m-d H:i:s');

        // Save Dropbox params
        $this->saveParams($dropboxParams);

        if ($mode !== 'automatic') {
            DropfilesComponentHelper::setParams(array('dropboxConnectMethod' => 'automatic'));
        }
        $app->redirect(JURI::root() . 'administrator/index.php?option=com_dropfiles&view=dropbox&layout=redirect');
    }

    /**
     * Display connect mode checkbox
     *
     * @return void
     */
    public function displayDropboxSettings()
    {
        $params = JComponentHelper::getParams('com_dropfiles');
        $connect_mode_list = array(
            'automatic' => JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_AUTOMATIC_CONNECT_MODE'),
            'manual'    => JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_MANUAL_CONNECT_MODE')
        );
        $dropbox_config = $this->loadParams();
        $config_mode = $params->get('dropboxConnectMethod', 'manual');

        if ($config_mode && $config_mode === 'automatic') {
            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.ju-settings-option.jform_dropbox_key\').hide();
                        $(\'.ju-settings-option.jform_dropbox_secret\').hide();
                        $(\'.ju-settings-option.jform_dropbox_authorization_code\').hide();
                        $(\'.ju-settings-option.jform_dropboxbtn\').hide();
                        $(\'.btn-dropbox\').hide();
                        $(\'.dropbox-ju-connect-message\').show();
                    });
                </script>';

            if (!$dropbox_config || empty($dropbox_config['dropboxAccessToken'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-dropbox\').addClass(\'ju-visibled\').show();
                        $(\'#dropfiles-btn-automaticdisconnect-dropbox\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($dropbox_config && !empty($dropbox_config['dropboxAccessToken'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-dropbox\').removeClass(\'ju-visibled\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-dropbox\').addClass(\'ju-visibled\').show();
                    });
                </script>';
            }
        } else {
            if (!$dropbox_config || empty($dropbox_config['dropboxAccessToken'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-dropbox\').addClass(\'ju-visibled\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-dropbox\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($dropbox_config && !empty($dropbox_config['dropboxAccessToken'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#dropfiles-btn-automaticconnect-dropbox\').removeClass(\'ju-visibled\').hide();
                        $(\'#dropfiles-btn-automaticdisconnect-dropbox\').addClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.dropbox-ju-connect-message\').hide();
                        $(\'.ju-settings-option.jform_dropboxbtn\').show();
                    });
                </script>';
        }

        if ($this->checkJoomunitedConnected()) {
            $juChecked = true;
            $message = '<p>' . JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_DROPBOX_CONNECTION_AUTOMATIC_MESSAGE1') . '</p>';
            $message .= '<p>' . JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_DROPBOX_CONNECTION_AUTOMATIC_MESSAGE2') . '</p>';
        } else {
            $juChecked = false;
            $message = '<p>' . JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_DROPBOX_CONNECTION_AUTOMATIC_MESSAGE3');
            $message .= '<strong>' . JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_DROPBOX_CONNECTION_AUTOMATIC_MESSAGE4') . '</strong>';
            $message .= JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_DROPBOX_CONNECTION_AUTOMATIC_MESSAGE5') .
                ' <strong> ' . JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_DROPBOX_CONNECTION_AUTOMATIC_MESSAGE6')
                . '</strong> '. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_DROPBOX_CONNECTION_AUTOMATIC_MESSAGE7') .'</p>';
            $message .= '<p>'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_DROPBOX_CONNECTION_AUTOMATIC_MESSAGE8') .'</p>';
        }

        echo '<div id="dropbox_connect_mode">';
        echo '<div class="ju-dropbox-connect-mode full-width">';
        echo '<label class="ju-setting-label" for="">'. JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_CONNECTING_MODE_LABEL') .'</label>';
        echo '<div class="dropbox-mode-radio-field automatic-radio-group">';
        echo '<div class="ju-radio-group">';
        foreach ($connect_mode_list as $k => $v) {
            $checked = (!empty($config_mode) && $config_mode === $k) ? 'checked' : '';
            echo '<label><input type="radio" class="ju-radiobox" name="jform[dropboxConnectMethod]" value="'. $k .'" '. $checked .'><span>'. $v .'</span></label>';
        }
        echo '</div>';
        echo '<div class="dropbox-ju-connect-message">'.$message.'</div>';
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
        $btnTooltip = ($juChecked === true) ? '' : JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_DROPBOX_CONNECTION_AUTOMATIC_MESSAGE9');

        echo '<div id="dropfiles-btn-automaticconnect-dropbox" class="cloud-connector-title" title="'. $btnTooltip .'">';
        echo '<a class="ju-button dropbox-automatic-connect '.($juChecked ? '' : 'ju-disconnected-autoconnect').'" href="#"
                name="' . $prefix . $id_button . '" 
                id="' . $prefix . $id_button . '" 
                data-network="' . $network . '" 
                data-link="' . $this->urlsafeB64Encode($link) . '">';
        echo '<img class="automatic-connect-icon" src="'. JURI::root() . '/components/com_dropfiles/assets/images/dropbox_icon_colored.png" alt=""/>';
        echo '<span class="btn-title">' . JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_DROPBOX_CONNECTION_AUTOMATIC_CONNECT_DROPBOX') . '</span></a>';
        echo '</div>';

        echo '<div id="dropfiles-btn-automaticdisconnect-dropbox" class="cloud-connector-title" title="'. $btnTooltip .'">';
        echo '<a class="ju-button dropbox-automatic-disconnect '.($juChecked ? '' : 'ju-disconnected-autoconnect').'" 
        href="index.php?option=com_dropfiles&task=config.logoutDropbox" data-network="' . $network . '">';
        echo '<img class="automatic-connect-icon" src="'. JURI::root() . '/components/com_dropfiles/assets/images/dropbox_icon_colored.png" alt=""/>';
        echo '<span class="btn-title">' . JText::sprintf('COM_DROPFILES_CONFIG_CLOUD_DROPBOX_CONNECTION_AUTOMATIC_DISCONNECT_DROPBOX') . '</span></a>';
        echo '</div>';
    }

    /**
     * Load params
     *
     * @return array
     */
    public function loadParams()
    {
        $params = JComponentHelper::getParams('com_dropfiles');
        $dropboxParams = array();

        $dropboxParams['dropbox_key']                = trim($params->get('dropbox_key'));
        $dropboxParams['dropbox_secret']             = trim($params->get('dropbox_secret'));
        $dropboxParams['dropboxAccessToken']         = isset($params['dropboxAccessToken']) ? $params['dropboxAccessToken'] : '';

        return $dropboxParams;
    }

    /**
     * Save params
     *
     * @param array $params Dropbox params
     *
     * @return void
     */
    public function saveParams($params)
    {
        $path_admin_component = JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/component.php';
        JLoader::register('DropfilesComponentHelper', $path_admin_component);
        DropfilesComponentHelper::setParams(
            array(
                'dropbox_key'                => $params['dropbox_key'],
                'dropbox_secret'             => $params['dropbox_secret'],
                'dropboxAccessToken'         => $params['dropboxAccessToken']
            )
        );
    }
}
