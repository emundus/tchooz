<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Filesystem\Path;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Protection\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
$basemodel = $this->basemodel;

HTMLHelper::_('bootstrap.tooltip');

$esc = static function ($v): string {
	return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$appliedIcon = static function ($applied): string {
	return $applied ? '<i class="fa fa-check text-success ms-2" aria-hidden="true"></i>' : '';
};

$siteUrl = rtrim(Uri::base(), '/');

// Carga fichero user-agents (para el modal)
$uaFile    = JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'user_agent_blacklist.inc';
$defaultUa = (is_file($uaFile) && is_readable($uaFile)) ? (string) file_get_contents($uaFile) : '';
?>
<form action="<?= Route::_('index.php?option=com_securitycheckpro&controller=protection&view=protection'); ?>" method="post" name="adminForm" id="adminForm" class="scp-compact" novalidate>

	<?php
    // Navegación (include robusto)
    $navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
    if (is_file($navFile)) {
        require $navFile;
    }
    ?>

	<?php if ($this->server === 'apache' || $this->server === 'iis'): ?>
		<div class="alert alert-warning" role="alert">
			<?= Text::_('COM_SECURITYCHECKPRO_USER_AGENT_INTRO'); ?>
		</div>
		<div class="alert alert-danger" role="alert">
			<?= Text::_('COM_SECURITYCHECKPRO_USER_AGENT_WARN'); ?>
		</div>
		<div class="alert alert-info" role="alert">
			<?= $this->ExistsHtaccess ? Text::_('COM_SECURITYCHECKPRO_USER_AGENT_HTACCESS') : Text::_('COM_SECURITYCHECKPRO_USER_AGENT_NO_HTACCESS'); ?>
		</div>
	<?php elseif ($this->server === 'nginx'): ?>
		<div class="alert alert-danger" role="alert">
			<?= Text::_('COM_SECURITYCHECKPRO_NGINX_SERVER'); ?>
		</div>
	<?php endif; ?>

	<!-- Toast (si usas toasts en otros flujos; no se usan aquí para help) -->
	<div id="toast" class="col-12 toast align-items-center margin-bottom-10" role="alert" aria-live="assertive" aria-atomic="true">
		<div class="toast-header">
			<strong id="toast-auto" class="me-auto"></strong>
			<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="<?= $esc(Text::_('JCLOSE')); ?>"></button>
		</div>
		<div id="toast-body" class="toast-body"></div>
	</div>

	<!-- Contenido -->
	<div class="card mb-3">
		<div class="card-body">
			<div class="scp-wrap">
				<ul class="nav nav-tabs" role="tablist" id="protectionTab">
					<li class="nav-item" id="li_autoprotection_tab">
						<a class="nav-link active" href="#autoprotection" data-bs-toggle="tab" role="tab"><?= Text::_('COM_SECURITYCHECKPRO_PROTECTION_AUTOPROTECTION_TEXT'); ?></a>
					</li>
					<li class="nav-item" id="li_headers_protection_tab">
						<a class="nav-link" href="#headers_protection" data-bs-toggle="tab" role="tab"><?= Text::_('COM_SECURITYCHECKPRO_HTTP_HEADERS_PROTECTION_TEXT'); ?></a>
					</li>
					<li class="nav-item" id="li_user_agents_protection_tab">
						<a class="nav-link" href="#user_agents_protection" data-bs-toggle="tab" role="tab"><?= Text::_('COM_SECURITYCHECKPRO_PROTECTION_USER_AGENTS_TEXT'); ?></a>
					</li>
					<li class="nav-item" id="li_fingerprinting_tab">
						<a class="nav-link" href="#fingerprinting" data-bs-toggle="tab" role="tab"><?= Text::_('COM_SECURITYCHECKPRO_FINGERPRINTING_PROTECTION_TEXT'); ?></a>
					</li>
					<li class="nav-item" id="li_backend_protection_tab">
						<a class="nav-link" href="#backend_protection" data-bs-toggle="tab" role="tab"><?= Text::_('COM_SECURITYCHECKPRO_BACKEND_PROTECTION_TEXT'); ?></a>
					</li>
					<li class="nav-item" id="li_performance_tab">
						<a class="nav-link" href="#performance_tab" data-bs-toggle="tab" role="tab"><?= Text::_('COM_SECURITYCHECKPRO_CPANEL_PERFORMANCE'); ?></a>
					</li>
				</ul>

				<div class="tab-content margin-top-10 overflow-auto">

					<!-- Self protection -->
					<div class="tab-pane show active" id="autoprotection" role="tabpanel">
						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" data-bs-placement="top"
									 title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_PREVENT_ACCESS_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_PREVENT_ACCESS_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['prevent_access'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('prevent_access','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['prevent_access']??0),false); ?>
							</div>
						</div>

						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" data-bs-placement="top"
									 title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_PREVENT_UNAUTHORIZED_BROWSING_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_PREVENT_UNAUTHORIZED_BROWSING_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['prevent_unauthorized_browsing'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('prevent_unauthorized_browsing','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['prevent_unauthorized_browsing']??0),false); ?>
							</div>
						</div>

						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" data-bs-placement="top"
									 title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_FILE_INJECTION_PROTECTION_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_FILE_INJECTION_PROTECTION_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['file_injection_protection'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('file_injection_protection','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['file_injection_protection']??0),false); ?>
							</div>
						</div>

						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" data-bs-placement="top"
									 title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_SELF_ENVIRON_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_SELF_ENVIRON_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['self_environ'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('self_environ','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['self_environ']??0),false); ?>
							</div>
						</div>
					</div>

					<!-- HTTP Headers Protection -->
					<div class="tab-pane" id="headers_protection" role="tabpanel">
						<div class="alert alert-danger mb-2"><?= Text::_('COM_SECURITYCHECKPRO_HTTP_HEADERS_EXPLAIN'); ?></div>
						<div class="alert alert-info"><?= Text::_('COM_SECURITYCHECKPRO_HTTP_HEADERS_PROTECTION_ALREADY_APPLIED'); ?></div>

						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_XFRAME_OPTIONS_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_XFRAME_OPTIONS_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['xframe_options'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect(
									'xframe_options',
									['NO'=>'COM_SECURITYCHECKPRO_NO','DENY'=>'COM_SECURITYCHECKPRO_XFRAME_OPTIONS_DENY','SAMEORIGIN'=>'COM_SECURITYCHECKPRO_XFRAME_OPTIONS_SAMEORIGIN'],
									['class'=>'form-select form-select-sm'],
									$this->protection_config['xframe_options'] ?? 'NO',
									false,true
								); ?>
							</div>
						</div>

						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_PREVENT_MIME_ATTACKS_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_PREVENT_MIME_ATTACKS_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['prevent_mime_attacks'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('prevent_mime_attacks','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['prevent_mime_attacks']??0),false); ?>
							</div>
						</div>

						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_STS_OPTIONS_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_STS_OPTIONS_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['sts_options'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('sts_options','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['sts_options']??0),false); ?>
							</div>
						</div>

						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_XSS_OPTIONS_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_XSS_OPTIONS_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['xss_options'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('xss_options','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['xss_options']??0),false); ?>
							</div>
						</div>

						<div class="scp-field row g-3">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_CSP_OPTIONS_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_CSP_OPTIONS_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['csp_policy'])); ?>
								</div>
							</div>
							<div class="col-md-8 d-flex gap-2 align-items-center">
								<input type="text" class="form-control form-control-sm" id="csp_policy" name="csp_policy"
									placeholder="<?= $esc(Text::_('COM_SECURITYCHECKPRO_ENTER_POLICY')) ?>"
									value="<?= $esc($this->protection_config['csp_policy'] ?? '') ?>">
							</div>
						</div>

						<div class="scp-field row g-3">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_REFERRER_POLICY_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_REFERRER_POLICY_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['referrer_policy'])); ?>
								</div>
							</div>
							<div class="col-md-8 d-flex gap-2 align-items-center">
								<input type="text" class="form-control form-control-sm" id="referrer_policy" name="referrer_policy"
									placeholder="<?= $esc(Text::_('COM_SECURITYCHECKPRO_ENTER_POLICY')) ?>"
									value="<?= $esc($this->protection_config['referrer_policy'] ?? '') ?>">
							</div>
						</div>

						<div class="scp-field row g-3">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_PERMISSIONS_POLICY_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_PERMISSIONS_POLICY_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['permissions_policy'])); ?>
								</div>
							</div>
							<div class="col-md-8 d-flex gap-2 align-items-center">
								<input type="text" class="form-control form-control-sm" id="permissions_policy" name="permissions_policy"
									placeholder="<?= $esc(Text::_('COM_SECURITYCHECKPRO_ENTER_POLICY')) ?>"
									value="<?= $esc($this->protection_config['permissions_policy'] ?? '') ?>">
							</div>
						</div>
					</div>

					<!-- Protection against malicious user-agents -->
					<div class="tab-pane" id="user_agents_protection" role="tabpanel">
						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_DEFAULT_BANNED_LIST_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_DEFAULT_BANNED_LIST_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['default_banned_list'])); ?>
								</div>
								<button type="button" class="btn btn-warning btn-sm mt-2" id="boton_default_user_agent">
									<?= Text::_('COM_SECURITYCHECKPRO_EDIT_DEFAULT_USER_AGENTS'); ?>
								</button>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('default_banned_list','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['default_banned_list']??0),false); ?>
							</div>
						</div>

						<hr class="my-3">

						<div class="scp-field row g-3">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_OWN_BANNED_LIST_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_OWN_BANNED_LIST_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['own_banned_list'])); ?>
								</div>
							</div>
							<div class="col-md-8 position-relative">
								<textarea class="form-control form-control-sm scp-textarea" name="own_banned_list" id="own_banned_list"><?= $esc($this->protection_config['own_banned_list'] ?? '') ?></textarea>
								<button type="button" class="btn btn-outline-secondary btn-sm scp-expander" data-expand-target="#own_banned_list">Expand</button>
							</div>
						</div>

						<hr class="my-3">

						<div class="scp-field row g-3">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_OWN_CODE_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_OWN_CODE_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['own_code'])); ?>
								</div>
							</div>
							<div class="col-md-8 position-relative">
								<textarea class="form-control form-control-sm scp-textarea" name="own_code" id="own_code"><?= $esc($this->protection_config['own_code'] ?? '') ?></textarea>
								<button type="button" class="btn btn-outline-secondary btn-sm scp-expander" data-expand-target="#own_code">Expand</button>
							</div>
						</div>

						<!-- Modal edición de default user agents -->
						<div class="modal" id="div_default_user_agents" tabindex="-1" aria-labelledby="defaultuseragentsLabel" aria-hidden="true">
							<div class="modal-dialog modal-lg" role="document">
								<div class="modal-content">
									<div class="modal-header alert alert-info">
										<h2 class="modal-title" id="defaultuseragentsLabel"><?= Text::_('COM_SECURITYCHECKPRO_FILE_CONTENT'); ?></h2>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $esc(Text::_('JCLOSE')); ?>"></button>
									</div>
									<div class="modal-body margin-left-10">
										<div class="color_rojo mb-2">
											<?= Text::_('COM_SECURITYCHECKPRO_WARNING_CHANGES_USER_AGENTS'); ?>
										</div>
										<textarea class="form-control" id="file_info" name="file_info" rows="10"><?= $esc($defaultUa) ?></textarea>
										<?php if ($defaultUa === ''): ?>
											<p class="text-muted mt-2 mb-0"><?= Text::_('JGLOBAL_FIELD_INVALID'); ?> (user_agent_blacklist.inc)</p>
										<?php endif; ?>
									</div>
									<div class="modal-footer">
										<input class="btn btn-success" id="save_default_user_agent_button" type="button" value="<?= $esc(Text::_('COM_SECURITYCHECKPRO_SAVE_CLOSE')); ?>" />
										<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Fingerprinting Protection -->
					<div class="tab-pane" id="fingerprinting" role="tabpanel">
						<div class="alert alert-danger mb-3"><?= Text::_('COM_SECURITYCHECKPRO_FINGERPRINTING_EXPLAIN'); ?></div>

						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_DISABLE_SERVER_SIGNATURE_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_DISABLE_SERVER_SIGNATURE_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['disable_server_signature'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('disable_server_signature','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['disable_server_signature']??0),false); ?>
							</div>
						</div>

						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_DISALLOW_PHP_EGGS_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_DISALLOW_PHP_EGGS_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['disallow_php_eggs'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('disallow_php_eggs','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['disallow_php_eggs']??0),false); ?>
							</div>
						</div>

						<?php
						if (empty($this->protection_config['disallow_sensible_files_access'])) {
							$this->protection_config['disallow_sensible_files_access'] =
								"htaccess.txt\nconfiguration.php(-dist)?\njoomla.xml\nREADME.txt\nweb.config.txt\nCONTRIBUTING.md\nphpunit.xml.dist\nplugin_googlemap2_proxy.php";
						}
						?>
						<div class="scp-field row g-3">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_DISALLOW_SENSIBLE_FILES_ACCESS_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_DISALLOW_SENSIBLE_FILES_ACCESS_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['disallow_sensible_files_access'])); ?>
								</div>
							</div>
							<div class="col-md-8 position-relative">
								<textarea class="form-control form-control-sm scp-textarea" name="disallow_sensible_files_access" id="disallow_sensible_files_access"><?= $esc($this->protection_config['disallow_sensible_files_access'] ?? '') ?></textarea>
								<button type="button" class="btn btn-outline-secondary btn-sm scp-expander" data-expand-target="#disallow_sensible_files_access">Expand</button>
							</div>
						</div>
					</div>

					<!-- Backend Protection -->
					<div class="tab-pane" id="backend_protection" role="tabpanel">
						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_FEATURE_APPLIED_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_FEATURE_APPLIED_TEXT'); ?>
									<?= $appliedIcon(!empty($this->protection_config['backend_protection_applied'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" id="backend_protection_applied" name="backend_protection_applied" onchange="hideIt();" <?= !empty($this->protection_config['backend_protection_applied']) ? 'checked' : '' ?>>
									<label class="form-check-label" for="backend_protection_applied"></label>
								</div>
							</div>
						</div>

						<div id="menu_hide_backend_1" class="alert alert-danger my-3"><?= Text::_('COM_SECURITYCHECKPRO_BACKEND_PROTECTION_EXPLAIN'); ?></div>

						<div id="menu_hide_backend_2" class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_URL_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_URL_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['hide_backend_url'])); ?>
								</div>
							</div>
							<div class="col-md-8 d-flex gap-2 align-items-center">
								<span class="badge text-dark" style="background:#FFBF60;"><?= $esc($siteUrl) ?>?</span>
								<input type="text" class="form-control form-control-sm" name="hide_backend_url" id="hide_backend_url"
									value="<?= $esc($this->protection_config['hide_backend_url'] ?? '') ?>"
									placeholder="<?= $esc($this->protection_config['hide_backend_url'] ?? '') ?>">
								<button type="button" id="hide_backend_url_button" class="btn btn-primary btn-sm"><?= Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_GENERATE_KEY_TEXT'); ?></button>
							</div>
						</div>

						<div id="menu_hide_backend_3" class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_URL_REDIRECTION_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_URL_REDIRECTION_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['hide_backend_url_redirection'])); ?>
								</div>
							</div>
							<div class="col-md-8 d-flex gap-2 align-items-center">
								<span class="badge text-dark" style="background:#D0F5A9;">/</span>
								<input type="text" class="form-control form-control-sm" name="hide_backend_url_redirection" id="hide_backend_url_redirection"
									value="<?= $esc($this->protection_config['hide_backend_url_redirection'] ?? '') ?>" placeholder="not_found">
							</div>
						</div>

						<div id="menu_hide_backend_4" class="scp-field row g-3">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_ADD_EXCEPTION_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_EXCEPTIONS'); ?>
									<?= $appliedIcon(!empty($this->config_applied['backend_exceptions'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<div class="position-relative mb-2">
									<textarea readonly class="form-control form-control-sm scp-textarea" name="backend_exceptions" id="backend_exceptions"><?= $esc($this->protection_config['backend_exceptions'] ?? '') ?></textarea>
									<button type="button" class="btn btn-outline-secondary btn-sm scp-expander" data-expand-target="#backend_exceptions">Expand</button>
								</div>
								<div class="d-flex gap-2">
								  <input type="text" class="form-control form-control-sm" name="exception" id="exception" placeholder="<?= $esc(Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_YOUR_EXCEPTION_HERE')); ?>">
								  <div class="btn-group" id="backend_actions_group"> <!-- <<--- AÑADIDO -->
									<button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
									  <?= Text::_('COM_SECURITYCHECKPRO_ACTIONS'); ?>
									</button>
									<ul class="dropdown-menu dropdown-menu-end">
									  <li><a class="dropdown-item" id="add_exception_button" href="#backend_exceptions"><?= Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_ADD_EXCEPTION_TEXT'); ?></a></li>
									  <li><a class="dropdown-item" id="delete_exception_button" href="#backend_exceptions"><?= Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_DELETE_EXCEPTION_TEXT'); ?></a></li>
									  <li><a class="dropdown-item" id="delete_all_button" href="#backend_exceptions"><?= Text::_('COM_SECURITYCHECKPRO_DELETE_ALL'); ?></a></li>
									</ul>
								  </div>
								</div>
							</div>
						</div>
					</div>

					<!-- Performance -->
					<div class="tab-pane" id="performance_tab" role="tabpanel">
						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_OPTIMAL_EXPIRATION_TIME_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_OPTIMAL_EXPIRATION_TIME_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['optimal_expiration_time'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('optimal_expiration_time','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['optimal_expiration_time']??0),false); ?>
							</div>
						</div>

						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_COMPRESS_CONTENT_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_COMPRESS_CONTENT_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['compress_content'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('compress_content','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['compress_content']??0),false); ?>
							</div>
						</div>

						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_REDIRECT_TO_WWW_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_REDIRECT_TO_WWW_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['redirect_to_www'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('redirect_to_www','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['redirect_to_www']??0),false); ?>
							</div>
						</div>

						<div class="scp-field row g-3 align-items-center">
							<div class="col-md-4">
								<div class="fw-semibold" data-bs-toggle="tooltip" title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_REDIRECT_TO_NON_WWW_EXPLAIN')) ?>">
									<?= Text::_('COM_SECURITYCHECKPRO_REDIRECT_TO_NON_WWW_TEXT'); ?>
									<?= $appliedIcon(!empty($this->config_applied['redirect_to_non_www'])); ?>
								</div>
							</div>
							<div class="col-md-8">
								<?= $basemodel->renderSelect('redirect_to_non_www','boolean',['class'=>'form-select form-select-sm'],(int)($this->protection_config['redirect_to_non_www']??0),false); ?>
							</div>
						</div>
					</div>

				</div><!-- /.tab-content -->
			</div>
		</div>
	</div>

	<input type="hidden" name="option" value="com_securitycheckpro" />
	<input type="hidden" name="boxchecked" value="1" />
	<input type="hidden" name="task" id="task" value="save" />
	<?= HTMLHelper::_('form.token'); ?>
</form>