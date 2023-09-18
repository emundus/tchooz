<?php
// no direct access
defined('_JEXEC') || die;

/**
 * Class DropfilesCloudConnector
 */
class DropfilesCloudConnector
{
    /**
     * Generated instances of application
     *
     * @var array
     */
    public static $instance = array();
    /**
     * Init path parameter
     *
     * @var string
     */
    public $path = '';
    /**
     * Init prefix of plugin parameter
     *
     * @var string
     */
    public $prefix = 'dropfiles';
    /**
     * Init name of plugin parameter
     *
     * @var string
     */
    public $name = '';
    /**
     * Init traslation text parameter
     *
     * @var string
     */
    public $text_domain;
    /**
     * Init cloud server link parameter
     *
     * @var string
     */
    public $connector = 'https://connector.joomunited.com/cloudconnector/';
    /**
     * Init google connector instance
     *
     * @var string
     */
    public $googleDrive;
    /**
     * Init Dropbox connector instance
     *
     * @var string
     */
    public $dropbox;
    /**
     * Init OneDrive connector instance
     *
     * @var string
     */
    public $onedrive;
    /**
     * Init OneDrive Business connector instance
     *
     * @var string
     */
    public $onedrivebusiness;

    /**
     * Init function
     *
     * @throws Exception Fire message if error
     *
     * @return mixed
     */
    public function init()
    {
        $app = JFactory::getApplication();
        if (!$app->isClient('administrator')) {
            return;
        }
        $this->initCloud();
        $this->initAssets();
    }

    /**
     * Init cloud function
     *
     * @return void
     */
    public function initCloud()
    {
        require_once 'cloud/GoogleDrive.php';
        $this->googleDrive = new GoogleDrive();

        require_once 'cloud/Dropbox.php';
        $this->dropbox = new Dropbox();

        require_once 'cloud/OneDrive.php';
        $this->onedrive = new OneDrive();

        require_once 'cloud/OneDriveBusiness.php';
        $this->onedrivebusiness = new OneDriveBusiness();
    }

    /**
     * Initializes the Assets
     *
     * @return void
     */
    public function initAssets()
    {
        $document = JFactory::getDocument();
        $nonce = JFactory::getSession()->getFormToken() ? JFactory::getSession()->getFormToken() : '';
        $cloudConnector = array(
            'ajaxurl' => JUri::root(),
            'connector' => $this->connector,
            'ju_token' => $this->checkJoomunitedConnected(),
            'nonce' => $nonce
        );
        $document->addScript(JURI::root() . 'components/com_dropfiles/assets/js/cloudconnector_script.js');
        $document->addScriptDeclaration('dropfiles_cloud_connector_var="' . $cloudConnector['connector'] . '";');
        $document->addScriptDeclaration('dropfiles_cloud_connector_ju_token_var="' . $cloudConnector['ju_token'] . '";');
        $document->addScriptDeclaration('dropfiles_cloud_connector_ajax_url_var="' . $cloudConnector['ajaxurl'] . '";');
    }

    /**
     * Check user connected to joomunited account
     *
     * @return boolean
     */
    public function checkJoomunitedConnected()
    {
        $token = null;
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('value');
        $query->from('#__joomunited_config');
        $query->where('name="ju_user_token"');
        $db->setQuery($query);
        $res = $db->loadObject();

        if (!empty($res) && !empty($res->value)) {
            $token = str_replace('token=', '', $res->value);
        }

        return $token;
    }

    /**
     * Execute function
     *
     * @throws Exception Fire message if error
     *
     * @return mixed
     */
    public function executeAction()
    {
        if (empty($_GET['cloudconnector']) ||
            empty($_GET['cloudconnect_nonce']) ||
            // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.hash_equalsFound -- PHP version
            !hash_equals($_GET['cloudconnect_nonce'], hash('md5', '_cloudconnect_nonce'))) {
            return false;
        }

        if (isset($_GET['plugin_type']) && $_GET['plugin_type'] !== $this->prefix) {
            return false;
        }

        if (isset($_GET['task']) && $_GET['task'] === 'config.cloudAutoConnect' && isset($_GET['network'])) {
            switch ($_GET['network']) {
                case 'google-drive':
                    if (!class_exists('GoogleDrive')) {
                        require_once 'cloud/GoogleDrive.php';
                    }
                    $googleConnector = new GoogleDrive();
                    $googleConnector->connect();
                    break;
                case 'dropbox':
                    if (!class_exists('Dropbox')) {
                        require_once 'cloud/Dropbox.php';
                    }
                    $dropboxConnector = new Dropbox();
                    $dropboxConnector->connect();
                    break;
                case 'one-drive':
                    if (!class_exists('OneDrive')) {
                        require_once 'cloud/OneDrive.php';
                    }
                    $oneDriveConnector = new OneDrive();
                    $oneDriveConnector->connect();
                    break;
                case 'one-drive-business':
                    if (!class_exists('OneDriveBusiness')) {
                        require_once 'cloud/OneDriveBusiness.php';
                    }
                    $oneDriveBusinessConnector = new OneDriveBusiness();
                    $oneDriveBusinessConnector->connect();
                    break;
            }

            $this->closeApp();
        }
    }

    /**
     * Close application function
     *
     * @return void
     */
    public function closeApp()
    {
        $script_reload = '';
        if (isset($_GET['current_backlink'])) {
            $script_reload = 'window.opener.location.href = "' . $this->urlsafeB64Decode($_GET['current_backlink']) . '";';
        }

        echo "<script type='text/javascript'>" . $script_reload . 'window.close();</script>';
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
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
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
}
