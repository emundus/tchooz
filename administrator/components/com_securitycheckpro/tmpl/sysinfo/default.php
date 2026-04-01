<?php
/**
 * @package     com_securitycheckpro
 * @subpackage  Administrator\View\Systeminfo\tmpl
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Application\CMSApplication;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Sysinfo\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\SysinfoModel $model */

// Behaviors
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');

// Carga del plugin principal de sistema del firewall
/** @var \Joomla\CMS\Application\CMSApplication $app */
$app       = Factory::getApplication();
$lang = $app->getLanguage();
$lang->load('plg_system_securitycheckpro', JPATH_ADMINISTRATOR) || $lang->load('plg_system_securitycheckpro', JPATH_ADMINISTRATOR, 'en-GB');

$tabSetId  = 'scp-systeminfo-tabs';
$activeTab = (string) $app->getUserStateFromRequest('com_securitycheckpro.' . $tabSetId . '.active', 'active_tab', 'overall');

// Porcentaje de la barra de estado overall
$value = (int) ($this->model->getOverall($this->system_info,1));

// Porcentaje de la barra de estado de la extensión
$wf = (int) ($this->system_info['overall_web_firewall'] ?? 0);
$wfClass = ($wf <= 50) ? 'bg-danger' : (($wf <= 70) ? 'bg-warning' : 'bg-success');

switch (true) {
    case $value <= 50:
        $barClass = 'bg-danger';
        break;
    case $value <= 70:
        $barClass = 'bg-warning';
        break;
    default:
        $barClass = 'bg-success';
        break;
}
?>


<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=' . $this->getName()); ?>"
      method="post"
      name="adminForm"
      id="adminForm"
      class="form-validate">
	  
	  <?php
    // Navegación superior del componente
    require JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php';
    ?>

	
	<h3 class="mb-3">
		<span class="icon-grid" aria-hidden="true"></span>
		<?php echo Text::_('COM_SECURITYCHECKPRO_SYSTEM_INFORMATION'); ?>
	</h3>

	<?php
	echo HTMLHelper::_(
		'uitab.startTabSet',
		$tabSetId,
		[
			'active'     => $activeTab,
			'recall'     => true,
			'breakpoint' => 768,
		]
	);

	// ------------- TAB: OVERALL STATUS -------------
	echo HTMLHelper::_('uitab.addTab', $tabSetId, 'overall', Text::_('COM_SECURITYCHECKPRO_SECURITY_OVERALL_STATUS'));
	?>
		<div class="scp-overall-bar mb-3">
			<div class="progress">
				<div class="progress-bar <?php echo $barClass; ?>"
					role="progressbar"
						 aria-label="<?php echo Text::_('COM_SECURITYCHECKPRO_SECURITY_OVERALL_STATUS'); ?>"
						 style="width: <?php echo $value; ?>%;"
						 aria-valuenow="<?php echo $value; ?>"
						 aria-valuemin="0"
						 aria-valuemax="100">
						<?php echo $value; ?>%
				</div>				
			</div>
		</div>
		<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
		<?php
		$kickstartExists = !empty($this->system_info['kickstart_exists']);

		echo $this->model->renderStatusItem([
			'title'      => Text::_('COM_SECURITYCHECKPRO_AKEEBA_RESTORATION_FILES_FOUND'),
			'ok'         => !$kickstartExists,
			'problems'   => 1,
			'fixButtonId'=> '',
			'modalId'    => 'modal_akeeba_restoration',
			'modalText'  => Text::_('COM_SECURITYCHECKPRO_AKEEBA_RESTORATION_FILES_INFO'),
		]);

		$installed = (string) ($this->system_info['coreinstalled'] ?? '');
		$latest    = (string) ($this->system_info['corelatest'] ?? '');

		echo $this->model->renderStatusItem([
			'title'      => Text::_('COM_SECURITYCHECKPRO_SECURITY_UP_TO_DATE'),
			'ok'         => ($installed !== '' && $latest !== '' && version_compare($installed, $latest, '==')),
			'problems'   => 1,
			'valueBadge' => $installed ?: '',
			'fixButtonId'=> 'GoToJoomlaUpdate_button',
			// sin modal aquí
		]);

		$vuln = intval($this->system_info['vuln_extensions'] ?? 0);
		echo $this->model->renderStatusItem([
			'title'      => Text::_('COM_SECURITYCHECKPRO_SECURITY_VULNERABLE_EXTENSIONS'),
			'ok'         => ($vuln === 0),
			'problems'   => $vuln,
			'fixButtonId'=> 'GoToVuln_button',
			'modalId'    => 'modal_vuln_extensions',
			'modalText'  => Text::_('COM_SECURITYCHECKPRO_VULN_EXTENSIONS_INFO'),
		]);

		$logs_pending = isset($logs_pending) && is_numeric($logs_pending) ? (int) $logs_pending : 0;
		echo $this->model->renderStatusItem([
			'title'      => Text::_('COM_SECURITYCHECKPRO_UNREAD_LOGS'),
			'ok'         => ($logs_pending <= 10),
			'problems'   => 1,
			'fixButtonId'=> 'GoToLogs_button',
			'modalId'    => 'modal_unread_logs',
			'modalText'  => Text::_('COM_SECURITYCHECKPRO_UNREAD_LOGS_INFO'),
		]);

		$suspicious = intval($this->system_info['suspicious_files'] ?? 0);
		echo $this->model->renderStatusItem([
			'title'      => Text::_('COM_SECURITYCHECKPRO_SECURITY_MALWARE_FOUND'),
			'ok'         => ($suspicious === 0),
			'problems'   => $suspicious,
			'fixButtonId'=> 'GoToMalware_button',
			'modalId'    => 'modal_malware_found',
			'modalText'  => Text::_('COM_SECURITYCHECKPRO_MALWARE_FOUND_INFO'),
		]);

		$badIntegrity = intval($this->system_info['files_with_bad_integrity'] ?? 0);
		echo $this->model->renderStatusItem([
			'title'      => Text::_('COM_SECURITYCHECKPRO_SECURITY_NO_FILES_MODIFIED'),
			'ok'         => ($badIntegrity === 0),
			'problems'   => $badIntegrity,
			'fixButtonId'=> 'GoToIntegrity_button',
			'modalId'    => 'modal_files_with_bad_integrity',
			'modalText'  => Text::_('COM_SECURITYCHECKPRO_FILES_BAD_INTEGRITY_INFO'),
		]);

		$badPerms = intval($this->system_info['files_with_incorrect_permissions'] ?? 0);
		echo $this->model->renderStatusItem([
			'title'      => Text::_('COM_SECURITYCHECKPRO_SECURITY_PERMISSIONS'),
			'ok'         => ($badPerms === 0),
			'problems'   => $badPerms,
			'fixButtonId'=> 'GoToPermissions_button',
			'modalId'    => 'modal_file_permissions',
			'modalText'  => Text::_('COM_SECURITYCHECKPRO_FILE_PERMISSIONS_INFO'),
		]);

		$backendProtection = !empty($this->system_info['backend_protection']);
		echo $this->model->renderStatusItem([
			'title'      => Text::_('COM_SECURITYCHECKPRO_SECURITY_HIDE_BACKEND'),
			'ok'         => $backendProtection,
			'problems'   => 1,
			'fixButtonId'=> 'GoToHtaccessProtection_button',
			'modalId'    => 'modal_hide_backend',
			'modalText'  => Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_INFO'),
		]);

		$forbidNewAdmins = intval($this->system_info['firewall_options']['forbid_new_admins'] ?? 0) === 1;
		echo $this->model->renderStatusItem([
			'title'      => Text::_('COM_SECURITYCHECKPRO_FORBID_NEW_ADMINS_LABEL'),
			'ok'         => $forbidNewAdmins,
			'problems'   => 1,
			'fixButtonId'=> 'li_session_protection_button',
			'modalId'    => 'modal_forbid_new_admins',
			'modalText'  => Text::_('COM_SECURITYCHECKPRO_FORBID_NEW_ADMINS_LABEL_INFO'),
		]);

		$twoFactorEnabled = intval($this->system_info['twofactor_enabled'] ?? 0) > 1;
		echo $this->model->renderStatusItem([
			'title'      => Text::_('COM_SECURITYCHECKPRO_TWO_FACTOR_ENABLED_LABEL'),
			'ok'         => $twoFactorEnabled,
			'problems'   => 1,
			'fixButtonId'=> 'li_joomla_plugins_button',
			'modalId'    => 'modal_two_factor_enabled',
			'modalText'  => Text::_('COM_SECURITYCHECKPRO_TWO_FACTOR_ENABLED_LABEL_INFO'),
		]);

		$headers = $this->system_info['htaccess_protection'] ?? [];
		$headersOk = (
			intval($headers['xframe_options']        ?? 0) > 0 &&
			intval($headers['sts_options']           ?? 0) > 0 &&
			intval($headers['xss_options']           ?? 0) > 0 &&
			intval($headers['csp_policy']            ?? 0) > 0 &&
			intval($headers['referrer_policy']       ?? 0) > 0 &&
			intval($headers['prevent_mime_attacks']  ?? 0) > 0
		);
		echo $this->model->renderStatusItem([
			'title'      => Text::_('COM_SECURITYCHECKPRO_HTTP_HEADERS_LABEL'),
			'ok'         => $headersOk,
			'problems'   => 1,
			'fixButtonId'=> 'li_headers_button',
			'modalId'    => 'modal_http_headers',
			'modalText'  => Text::_('COM_SECURITYCHECKPRO_HTTP_HEADERS_INFO'),
		]);			?>
		</div>
	<?php
	echo HTMLHelper::_('uitab.endTab');

	// ------------- TAB: EXTENSION STATUS -------------
	echo HTMLHelper::_('uitab.addTab', $tabSetId, 'extensions', Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS'));
	?>
		<div class="progress mb-3">
		  <div class="progress-bar <?php echo $wfClass; ?>"
			   role="progressbar"
			   aria-label="<?php echo Text::_('COM_SECURITYCHECKPRO_SECURITY_OVERALL_STATUS'); ?>"
			   style="width: <?php echo $wf; ?>%;"
			   aria-valuenow="<?php echo $wf; ?>"
			   aria-valuemin="0"
			   aria-valuemax="100">
			<?php echo $wf; ?>%
		  </div>
		</div>
	
		<?php
		// Helpers para condiciones recurrentes
		$fwEnabled = !empty($this->system_info['firewall_plugin_enabled']);
		$opts      = $this->system_info['firewall_options'] ?? [];
		$ht        = $this->system_info['htaccess_protection'] ?? [];

		// Grid: 1 col en móvil, 2 en md, 3 en xl
		echo '<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">';

		// -------------------- Grupo: Firewall (header "dark") --------------------
		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-dark',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_FIREWALL_ENABLED'),
		  'ok'             => $fwEnabled,
		  'problems'       => 1,
		  'fixButtonId'    => 'li_twofactor_button',
		  'modalId'        => 'modal_firewall_plugin_enabled',
		  'modalText'      => Text::_('COM_SECURITYCHECKPRO_FIREWALL_ENABLED_INFO'),
		]);

		// Dynamic blacklist
		if (!$fwEnabled) {
		  echo $this->model->renderStatusItem([
			'headerItemClass'=> 'list-group-item-dark',
			'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_DYNAMIC_BLACKLIST'),
			'ok'             => true, // no problema: mostramos aviso
			'valueBadge'     => Text::_('COM_SECURITYCHECKPRO_ENABLE_FIREWALL_TO_APPLY'),
			'valueBadgeClass'=> 'bg-warning',
		  ]);
		} else {
		  echo $this->model->renderStatusItem([
			'headerItemClass'=> 'list-group-item-dark',
			'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_DYNAMIC_BLACKLIST'),
			'ok'             => !empty($opts['dynamic_blacklist']),
			'problems'       => 1,
			'fixButtonId'    => 'li_security_status_button',
			'modalId'        => 'modal_dynamic_blacklist',
			'modalText'      => Text::_('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_INFO'),
		  ]);
		}

		// Logs attacks
		if (!$fwEnabled) {
		  echo $this->model->renderStatusItem([
			'headerItemClass'=> 'list-group-item-dark',
			'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_LOGS'),
			'ok'             => true,
			'valueBadge'     => Text::_('COM_SECURITYCHECKPRO_ENABLE_FIREWALL_TO_APPLY'),
			'valueBadgeClass'=> 'bg-warning',
		  ]);
		} else {
		  echo $this->model->renderStatusItem([
			'headerItemClass'=> 'list-group-item-dark',
			'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_LOGS'),
			'ok'             => !empty($opts['logs_attacks']),
			'problems'       => 1,
			'fixButtonId'    => 'li_security_status_logs_button',
			'modalId'        => 'modal_logs_attacks',
			'modalText'      => Text::_('COM_SECURITYCHECKPRO_LOG_ATTACKS_INFO'),
		  ]);
		}

		// Second level
		if (!$fwEnabled) {
		  echo $this->model->renderStatusItem([
			'headerItemClass'=> 'list-group-item-dark',
			'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_SECOND_LEVEL'),
			'ok'             => true,
			'valueBadge'     => Text::_('COM_SECURITYCHECKPRO_ENABLE_FIREWALL_TO_APPLY'),
			'valueBadgeClass'=> 'bg-warning',
		  ]);
		} else {
		  echo $this->model->renderStatusItem([
			'headerItemClass'=> 'list-group-item-dark',
			'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_SECOND_LEVEL'),
			'ok'             => !empty($opts['second_level']),
			'problems'       => 1,
			'fixButtonId'    => 'li_extension_status_second_button',
			'modalId'        => 'modal_second_level',
			'modalText'      => Text::_('COM_SECURITYCHECKPRO_SECOND_LEVEL_INFO'),
		  ]);
		}

		// Exclude exceptions if vulnerable
		if (!$fwEnabled) {
		  echo $this->model->renderStatusItem([
			'headerItemClass'=> 'list-group-item-dark',
			'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_EXCLUDE_EXCEPTIONS'),
			'ok'             => true,
			'valueBadge'     => Text::_('COM_SECURITYCHECKPRO_ENABLE_FIREWALL_TO_APPLY'),
			'valueBadgeClass'=> 'bg-warning',
		  ]);
		} else {
		  echo $this->model->renderStatusItem([
			'headerItemClass'=> 'list-group-item-dark',
			'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_EXCLUDE_EXCEPTIONS'),
			'ok'             => !empty($opts['exclude_exceptions_if_vulnerable']),
			'problems'       => 1,
			'fixButtonId'    => 'li_extension_status_exclude_button',
			'modalId'        => 'modal_exclude_exceptions_if_vulnerable',
			'modalText'      => Text::_('COM_SECURITYCHECKPRO_EXCLUDE_EXCEPTIONS_IF_VULNERABLE_DESCRIPTION'),
		  ]);
		}

		// XSS filter
		$xssOk = $fwEnabled && (strpos((string)($opts['strip_tags_exceptions'] ?? ''), '*') === false);
		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-dark',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_XSS_FILTER'),
		  'ok'             => $xssOk ?: (!$fwEnabled),
		  'problems'       => $xssOk || !$fwEnabled ? 0 : 1,
		  'fixButtonId'    => !$xssOk ? 'li_extension_status_xss_button' : null,
		  'modalId'        => !$xssOk ? 'modal_strip_tags_exceptions' : null,
		  'modalText'      => !$xssOk ? Text::_('COM_SECURITYCHECKPRO_XSS_FILTER_INFO') : null,
		  'valueBadge'     => !$fwEnabled ? Text::_('COM_SECURITYCHECKPRO_ENABLE_FIREWALL_TO_APPLY') : null,
		  'valueBadgeClass'=> !$fwEnabled ? 'bg-warning' : null,
		]);

		// SQL filter
		$sqlOk = $fwEnabled && (strpos((string)($opts['sql_pattern_exceptions'] ?? ''), '*') === false);
		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-dark',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_SQL_FILTER'),
		  'ok'             => $sqlOk ?: (!$fwEnabled),
		  'problems'       => $sqlOk || !$fwEnabled ? 0 : 1,
		  'fixButtonId'    => !$sqlOk ? 'li_extension_status_sql_button' : null,
		  'modalId'        => !$sqlOk ? 'modal_sql_pattern_exceptions' : null,
		  'modalText'      => !$sqlOk ? Text::_('COM_SECURITYCHECKPRO_SQL_FILTER_INFO') : null,
		  'valueBadge'     => !$fwEnabled ? Text::_('COM_SECURITYCHECKPRO_ENABLE_FIREWALL_TO_APPLY') : null,
		  'valueBadgeClass'=> !$fwEnabled ? 'bg-warning' : null,
		]);

		// LFI filter
		$lfiOk = $fwEnabled && (strpos((string)($opts['lfi_exceptions'] ?? ''), '*') === false);
		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-dark',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_LFI_FILTER'),
		  'ok'             => $lfiOk ?: (!$fwEnabled),
		  'problems'       => $lfiOk || !$fwEnabled ? 0 : 1,
		  'fixButtonId'    => !$lfiOk ? 'li_extension_status_lfi_button' : null,
		  'modalId'        => !$lfiOk ? 'modal_lfi_exceptions' : null,
		  'modalText'      => !$lfiOk ? Text::_('COM_SECURITYCHECKPRO_SQL_FILTER_INFO') : null,
		  'valueBadge'     => !$fwEnabled ? Text::_('COM_SECURITYCHECKPRO_ENABLE_FIREWALL_TO_APPLY') : null,
		  'valueBadgeClass'=> !$fwEnabled ? 'bg-warning' : null,
		]);

		// Session protection
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
		$params = $app->getConfig();
		$shared = (bool) $params->get('shared_session');
		$sessOk = $fwEnabled && !empty($opts['session_protection_active']) && !$shared;
		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-dark',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_SESSION_PROTECTION'),
		  'ok'             => $sessOk ?: (!$fwEnabled),
		  'problems'       => $sessOk || !$fwEnabled ? 0 : 1,
		  'fixButtonId'    => !$sessOk ? 'li_extension_status_session_button' : null,
		  'modalId'        => !$sessOk ? 'modal_session_protection_active' : null,
		  'modalText'      => !$sessOk ? Text::_('COM_SECURITYCHECKPRO_SESSION_PROTECTION_INFO') : null,
		  'valueBadge'     => !$fwEnabled ? Text::_('COM_SECURITYCHECKPRO_ENABLE_FIREWALL_TO_APPLY') : null,
		  'valueBadgeClass'=> !$fwEnabled ? 'bg-warning' : null,
		]);

		// Session hijack
		$hijackOk = $fwEnabled && !empty($opts['session_hijack_protection']) && !$shared;
		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-dark',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_SESSION_HIJACK_PROTECTION'),
		  'ok'             => $hijackOk ?: (!$fwEnabled),
		  'problems'       => $hijackOk || !$fwEnabled ? 0 : 1,
		  'fixButtonId'    => !$hijackOk ? 'li_extension_status_session_hijack_button' : null,
		  'modalId'        => !$hijackOk ? 'modal_session_hijack_protection' : null,
		  'modalText'      => !$hijackOk ? Text::_('COM_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_INFO') : null,
		  'valueBadge'     => !$fwEnabled ? Text::_('COM_SECURITYCHECKPRO_ENABLE_FIREWALL_TO_APPLY') : null,
		  'valueBadgeClass'=> !$fwEnabled ? 'bg-warning' : null,
		]);

		// Upload scanner
		$uploadOk = $fwEnabled && !empty($opts['upload_scanner_enabled']);
		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-dark',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_UPLOAD_SCANNER'),
		  'ok'             => $uploadOk ?: (!$fwEnabled),
		  'problems'       => $uploadOk || !$fwEnabled ? 0 : 1,
		  'fixButtonId'    => !$uploadOk ? 'li_extension_status_upload_button' : null,
		  'modalId'        => !$uploadOk ? 'modal_upload_scanner_enabled' : null,
		  'modalText'      => !$uploadOk ? Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_DESCRIPTION') : null,
		  'valueBadge'     => !$fwEnabled ? Text::_('COM_SECURITYCHECKPRO_ENABLE_FIREWALL_TO_APPLY') : null,
		  'valueBadgeClass'=> !$fwEnabled ? 'bg-warning' : null,
		]);

		// Cron enabled (ajusta la condición real si tienes flag específico)
		$cronOk = $fwEnabled && !empty($this->system_info['cron_enabled']); // <-- ajusta a tu key real
		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-dark',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_CRON_ENABLED'),
		  'ok'             => $cronOk ?: (!$fwEnabled),
		  'problems'       => $cronOk || !$fwEnabled ? 0 : 1,
		  'fixButtonId'    => !$cronOk ? 'li_extension_status_cron_button' : null,
		  'modalId'        => !$cronOk ? 'modal_cron_enabled' : null,
		  'modalText'      => !$cronOk ? Text::_('COM_SECURITYCHECKPRO_CRON_ENABLED_INFO') : null,
		  'valueBadge'     => !$fwEnabled ? Text::_('COM_SECURITYCHECKPRO_ENABLE_FIREWALL_TO_APPLY') : null,
		  'valueBadgeClass'=> !$fwEnabled ? 'bg-warning' : null,
		]);

		// Last filemanager check
		$last = (string) ($this->system_info['last_check'] ?? '');
		$now  = (new \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel())->get_Joomla_timestamp();
		$interval = ($now && $last) ? (int) ((strtotime($now) - strtotime($last)) / 86400) : 100;
		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-dark',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_CRON_LAST_FILEMANAGER_CHECK'),
		  'ok'             => ($interval < 2),
		  'problems'       => 1,
		  'valueBadge'     => $last,
		  'valueBadgeClass'=> ($interval < 2) ? 'bg-success' : 'bg-warning',
		  'fixButtonId'    => ($interval < 2) ? null : 'li_extension_status_filemanager_check_button',
		  'modalId'        => ($interval < 2) ? null : 'modal_last_check',
		  'modalText'      => ($interval < 2) ? null : Text::_('COM_SECURITYCHECKPRO_LAST_CHECK_INFO'),
		]);

		// Last fileintegrity check
		$lastI = (string) ($this->system_info['last_check_integrity'] ?? '');
		$intervalI = ($now && $lastI) ? (int) ((strtotime($now) - strtotime($lastI)) / 86400) : 100;
		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-dark',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_CRON_LAST_FILEINTEGRITY_CHECK'),
		  'ok'             => ($intervalI < 2),
		  'problems'       => 1,
		  'valueBadge'     => $lastI,
		  'valueBadgeClass'=> ($intervalI < 2) ? 'bg-success' : 'bg-warning',
		  'fixButtonId'    => ($intervalI < 2) ? null : 'li_extension_status_fileintegrity_check_button',
		  'modalId'        => ($intervalI < 2) ? null : 'modal_last_check_integrity',
		  'modalText'      => ($intervalI < 2) ? null : Text::_('COM_SECURITYCHECKPRO_LAST_CHECK_INTEGRITY_INFO'),
		]);

		// Spam protection plugin
		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-dark',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_EXTENSION_STATUS_SPAM_PROTECTION_ENABLED'),
		  'ok'             => !empty($this->system_info['spam_protection_plugin_enabled']),
		  'problems'       => 1,
		  'fixButtonId'    => 'li_extension_status_spam_button',
		  'modalId'        => 'modal_spam_protection_enabled',
		  'modalText'      => Text::_('COM_SECURITYCHECKPRO_SPAM_PROTECTION_ENABLED_INFO'),
		]);

		// -------------------- Grupo: .htaccess (header "danger") --------------------
		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-danger',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_CPANEL_HTACCESS_PROTECTION_TEXT'),
		  'ok'             => !empty($ht['prevent_access']),
		  'problems'       => 1,
		  'fixButtonId'    => 'li_extension_status_htaccess_button',
		  'modalId'        => 'modal_prevent_access',
		  'modalText'      => Text::_('COM_SECURITYCHECKPRO_PREVENT_ACCESS_EXPLAIN'),
		]);

		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-danger',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_PREVENT_UNAUTHORIZED_BROWSING_TEXT'),
		  'ok'             => !empty($ht['prevent_unauthorized_browsing']),
		  'problems'       => 1,
		  'fixButtonId'    => 'li_extension_status_browsing_button',
		  'modalId'        => 'modal_prevent_unauthorized_browsing',
		  'modalText'      => Text::_('COM_SECURITYCHECKPRO_PREVENT_UNAUTHORIZED_BROWSING_EXPLAIN'),
		]);

		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-danger',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_FILE_INJECTION_PROTECTION_TEXT'),
		  'ok'             => !empty($ht['file_injection_protection']),
		  'problems'       => 1,
		  'fixButtonId'    => 'li_extension_status_file_injection_button',
		  'modalId'        => 'modal_file_injection_protection',
		  'modalText'      => Text::_('COM_SECURITYCHECKPRO_FILE_INJECTION_PROTECTION_EXPLAIN'),
		]);

		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-danger',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_SELF_ENVIRON_EXPLAIN'),
		  'ok'             => !empty($ht['self_environ']),
		  'problems'       => 1,
		  'fixButtonId'    => 'li_extension_status_self_button',
		  'modalId'        => 'modal_self_environ',
		  'modalText'      => Text::_('COM_SECURITYCHECKPRO_SELF_ENVIRON_EXPLAIN'),
		]);

		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-danger',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_XFRAME_OPTIONS_TEXT'),
		  'ok'             => !empty($ht['xframe_options']),
		  'problems'       => 1,
		  'fixButtonId'    => 'li_extension_status_xframe_button',
		  'modalId'        => 'modal_xframe_options',
		  'modalText'      => Text::_('COM_SECURITYCHECKPRO_XFRAME_OPTIONS_EXPLAIN'),
		]);

		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-danger',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_PREVENT_MIME_ATTACKS_TEXT'),
		  'ok'             => !empty($ht['prevent_mime_attacks']),
		  'problems'       => 1,
		  'fixButtonId'    => 'li_extension_status_mime_button',
		  'modalId'        => 'modal_prevent_mime_attacks',
		  'modalText'      => Text::_('COM_SECURITYCHECKPRO_PREVENT_MIME_ATTACKS_EXPLAIN'),
		]);

		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-danger',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_DEFAULT_BANNED_LIST_TEXT'),
		  'ok'             => !empty($ht['default_banned_list']),
		  'problems'       => 1,
		  'fixButtonId'    => 'li_extension_status_default_banned_button',
		  'modalId'        => 'modal_default_banned_list',
		  'modalText'      => Text::_('COM_SECURITYCHECKPRO_DEFAULT_BANNED_LIST_INFO'),
		]);

		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-danger',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_DISABLE_SERVER_SIGNATURE_TEXT'),
		  'ok'             => !empty($ht['disable_server_signature']),
		  'problems'       => 1,
		  'fixButtonId'    => 'li_extension_status_signature_button',
		  'modalId'        => 'modal_disable_server_signature',
		  'modalText'      => Text::_('COM_SECURITYCHECKPRO_DISABLE_SERVER_SIGNATURE_EXPLAIN'),
		]);

		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-danger',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_DISALLOW_PHP_EGGS_TEXT'),
		  'ok'             => !empty($ht['disallow_php_eggs']),
		  'problems'       => 1,
		  'fixButtonId'    => 'li_extension_status_eggs_button',
		  'modalId'        => 'modal_disallow_php_eggs',
		  'modalText'      => Text::_('COM_SECURITYCHECKPRO_DISALLOW_PHP_EGGS_EXPLAIN'),
		]);

		echo $this->model->renderStatusItem([
		  'headerItemClass'=> 'list-group-item-danger',
		  'title'          => Text::_('COM_SECURITYCHECKPRO_DISALLOW_SENSIBLE_FILES_ACCESS_TEXT'),
		  'ok'             => !empty($ht['disallow_php_eggs']), // ajusta si tienes key específica
		  'problems'       => 1,
		  'fixButtonId'    => 'li_extension_status_sensible_button',
		  'modalId'        => 'modal_disallow_sensible_files_access',
		  'modalText'      => Text::_('COM_SECURITYCHECKPRO_DISALLOW_ACCESS_SENSIBLE_FILES_INFO'),
		]);

		echo '</div>'; // /row ?>		
	<?php
	echo HTMLHelper::_('uitab.endTab');

	// ------------- TAB: GLOBAL CONFIGURATION -------------
	echo HTMLHelper::_('uitab.addTab', $tabSetId, 'global', Text::_('COM_SECURITYCHECKPRO_GLOBAL_CONFIGURATION'));
	?>
		<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
		<?php
		echo $this->model->renderInfoItem([
			'title' => Text::_('COM_SECURITYCHECKPRO_SYSINFO_JOOMLAVERSION'),
			'value' => $this->system_info['version'] ?? '',
		]);

		echo $this->model->renderInfoItem([
			'title' => Text::_('COM_SECURITYCHECKPRO_SYSINFO_JOOMLAPLATFORM'),
			'value' => $this->system_info['platform'] ?? '',
		]);
    ?>
</div>
	<?php
	echo HTMLHelper::_('uitab.endTab');

	// ------------- TAB: MYSQL CONFIGURATION -------------
	echo HTMLHelper::_('uitab.addTab', $tabSetId, 'mysql', Text::_('COM_SECURITYCHECKPRO_MYSQL_CONFIGURATION'));
	?>
		 <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
			<?php
			echo $this->model->renderInfoItem([
			  'headerItemClass' => 'list-group-item-warning',
			  'title'           => Text::_('COM_SECURITYCHECKPRO_SYSINFO_MAX_ALLOWED_PACKET'),
			  'value'           => $this->system_info['max_allowed_packet'] ?? '',
			  'suffix'          => 'M',                  // añade la unidad
			  'valueClass'      => '',     // opcional
			  'colClass'        => 'col-12 col-md-6 col-xl-3', // opcional
			]);
			?>		
		</div>
	<?php
	echo HTMLHelper::_('uitab.endTab');

	// ------------- TAB: PHP CONFIGURATION -------------
	echo HTMLHelper::_('uitab.addTab', $tabSetId, 'php', Text::_('COM_SECURITYCHECKPRO_PHP_CONFIGURATION'));
	?>
		<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
		  <?php
		  echo $this->model->renderInfoItem([
			'headerItemClass' => 'list-group-item-secondary',
			'title'           => Text::_('COM_SECURITYCHECKPRO_SYSINFO_PHPVERSION'),
			'value'           => $this->system_info['phpversion'] ?? '',
			'valueClass'      => '',
		  ]);

		  echo $this->model->renderInfoItem([
			'headerItemClass' => 'list-group-item-secondary',
			'title'           => Text::_('COM_SECURITYCHECKPRO_SYSINFO_MEMORY_LIMIT'),
			'value'           => $this->system_info['memory_limit'] ?? '', // p.ej. "256M"
			'valueClass'      => '',
			'isHtml'      => true,
		  ]);
		  ?>
		</div>

	<?php
	echo HTMLHelper::_('uitab.endTab');

	echo HTMLHelper::_('uitab.endTabSet');
	?>

	<input type="hidden" name="task" value="">
	<input type="hidden" name="active_tab" id="active_tab" value="<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">
	<input type="hidden" name="option" value="com_securitycheckpro" />
	<input type="hidden" name="controller" value="filemanager" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>