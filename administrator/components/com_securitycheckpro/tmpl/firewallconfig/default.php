<?php 
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Filesystem\Path;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use Joomla\CMS\Application\CMSApplication;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */

Session::checkToken('get') or die('Invalid Token');

// Carga del plugin principal de sistema del firewall
$app       = Factory::getApplication();
$lang = $app->getLanguage();
$lang->load('plg_system_securitycheckpro', JPATH_ADMINISTRATOR) || $lang->load('plg_system_securitycheckpro', JPATH_ADMINISTRATOR, 'en-GB');

$site_url = Uri::root();

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
$basemodel = $this->basemodel;

// --- Acción del formulario (sin token en la URL)
$action = Route::_('index.php?option=com_securitycheckpro&view=firewallconfig&' . Session::getFormToken() . '=1');
?>

<form
    action="<?php echo $action; ?>"
    class="margin-left-10 margin-right-10"
    enctype="multipart/form-data"
    method="post"
    name="adminForm"
    id="adminForm"
>
    <?php
    // Navegación (include robusto)
    $navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
    if (is_file($navFile)) {
        require $navFile;
    }
    ?>

    <div class="card mb-3">
        <div class="card-body">
            <?php
            // Tabset con uitab (compatible Joomla 4/5/6)
			// Importante: el id 'WafConfigurationTabs' debe ser único en la página
			echo HTMLHelper::_('uitab.startTabSet', 'WafConfigurationTabs', [
				'active'    => $this->activeParent,  // id				
				'breakpoint'=> 768              // Controla en qué ancho de pantalla los tabs se convierten en acordeón
			]);	
			
            // --- Pestaña: Listas
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_lists_tab', Text::_('PLG_SECURITYCHECKPRO_LISTS_LABEL'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_list_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            // --- Pestaña: Métodos inspeccionados
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_methods_tab', Text::_('PLG_SECURITYCHECKPRO_METHODS_INSPECTED_LABEL'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_methods_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            // --- Pestaña: Modo
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_mode_tab', Text::_('PLG_SECURITYCHECKPRO_MODE_FIELDSET_LABEL'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_mode_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            // --- Pestaña: Logs
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_logs_tab', Text::_('PLG_SECURITYCHECKPRO_LOGS_LABEL'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_logs_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            // --- Pestaña: Redirecciones
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_redirection_tab', Text::_('PLG_SECURITYCHECKPRO_REDIRECTION_LABEL'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_redirection_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            // --- Pestaña: Segundo factor
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_second_tab', Text::_('PLG_SECURITYCHECKPRO_SECOND_LABEL'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_second_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            // --- Pestaña: Notificaciones email
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_email_notifications_tab', Text::_('PLG_SECURITYCHECKPRO_EMAIL_NOTIFICATIONS_LABEL'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_notification_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            // --- Pestaña: Excepciones
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_exceptions_tab', Text::_('PLG_SECURITYCHECKPRO_EXCEPTIONS_LABEL'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_exceptions_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            // --- Pestaña: Protección de sesión
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_session_protection_tab', Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_LABEL'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_session_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            // --- Pestaña: Upload Scanner
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_upload_scanner_tab', Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_LABEL'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_upload_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            // --- Pestaña: Spam protection
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_spam_protection_tab', Text::_('COM_SECURITYCHECKPRO_SPAM_PROTECTION'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_spam_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            // --- Pestaña: URL Inspector
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_url_inspector_tab', Text::_('COM_SECURITYCHECKPRO_CPANEL_URL_INSPECTOR_TEXT'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_url_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            // --- Pestaña: Track actions
            echo HTMLHelper::_('uitab.addTab', 'WafConfigurationTabs', 'li_track_actions_tab', Text::_('COM_SECURITYCHECKPRO_TRACK_ACTIONS'));
            $tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_track_tab.php');
            if (is_file($tabFile)) {
                include $tabFile;
            }
            echo HTMLHelper::_('uitab.endTab');

            echo HTMLHelper::_('uitab.endTabSet');
            ?>
        </div>
    </div>

    <input type="hidden" name="option" value="com_securitycheckpro">
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="1">
    <input type="hidden" name="controller" value="firewallconfig">
	<!-- Hidden por TABSET -->
	<input type="hidden" name="activeTab_WafConfigurationTabs"
         value="<?php echo htmlspecialchars($this->activeParent, ENT_QUOTES, 'UTF-8'); ?>">
	<input type="hidden" name="activeTab_ListsTabs"
         value="<?php echo htmlspecialchars($this->activeChild, ENT_QUOTES, 'UTF-8'); ?>">
	<input type="hidden" name="activeTab_ExceptionsTabs"
         value="<?php echo htmlspecialchars($this->activeExceptions, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>