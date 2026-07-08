<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Filesystem\Path;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Controlcenter\HtmlView $this */

Session::checkToken('get') or die('Invalid Token');

HTMLHelper::_('behavior.core');
HTMLHelper::_('bootstrap.tooltip', '[data-bs-toggle="tooltip"]');

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
$basemodel = $this->basemodel;

$esc = static function ($v): string {
	return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$ccEnabled = (int) $this->control_center_enabled === 1;
?>

<form action="<?= Route::_('index.php?option=com_securitycheckpro&view=controlcenter&' . Session::getFormToken() . '=1'); ?>" method="post" name="adminForm" id="adminForm" class="mx-2">

	<?php
	$navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
	if (is_file($navFile)) {
		require $navFile;
	}
	?>

	<?php if (function_exists('openssl_encrypt')): ?>

		<!-- Toast -->
		<div id="toast" class="col-12 toast align-items-center margin-bottom-10" role="alert" aria-live="assertive" aria-atomic="true">
			<div class="toast-header">
				<strong id="toast-auto" class="me-auto"></strong>
				<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="<?= $esc(Text::_('JCLOSE')); ?>"></button>
			</div>
			<div id="toast-body" class="toast-body"></div>
		</div>

		<!-- Action bar -->
		<div class="scp-actionbar">
			<div>
				<p class="scp-actionbar__title">
					<i class="fa fa-network-wired" aria-hidden="true"></i>
					<?= Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS'); ?>
				</p>
				<p class="scp-actionbar__subtitle d-flex flex-wrap gap-2 align-items-center mt-1">
					<?php if ($ccEnabled): ?>
						<span class="scp-chip scp-chip--ok">
							<i class="fa fa-check-circle" aria-hidden="true"></i>
							<?= Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENABLED_TEXT'); ?>
						</span>
					<?php else: ?>
						<span class="scp-chip scp-chip--ko">
							<i class="fa fa-times-circle" aria-hidden="true"></i>
							<?= Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENABLED_TEXT'); ?>
						</span>
					<?php endif; ?>
				</p>
			</div>
		</div>

		<div class="scp-callout scp-callout--info">
			<i class="fa fa-info-circle me-1" aria-hidden="true"></i>
			<?= Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_EXPLAIN'); ?>
		</div>

		<!-- Fields card -->
		<div class="card mb-3">
			<div class="card-body">

				<!-- Control Center enabled -->
				<div class="scp-field row g-3 align-items-center">
					<div class="col-md-4">
						<div class="fw-semibold" data-bs-toggle="tooltip" data-bs-theme="dark" data-bs-placement="top"
							 title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENABLED_EXPLAIN')); ?>">
							<?= Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENABLED_TEXT'); ?>
						</div>
					</div>
					<div class="col-md-8">
						<?= $basemodel->renderSelect('control_center_enabled', 'boolean', ['class' => 'form-select form-select-sm'], (int) $this->control_center_enabled, false); ?>
					</div>
				</div>

				<!-- Token -->
				<div class="scp-field row g-3 align-items-center">
					<div class="col-md-4">
						<div class="fw-semibold" data-bs-toggle="tooltip" data-bs-theme="dark" data-bs-placement="top"
							 title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_TOKEN_EXPLAIN')); ?>">
							<?= Text::_('COM_SECURITYCHECKPRO_TOKEN_TEXT'); ?>
						</div>
					</div>
					<div class="col-md-8">
						<input class="form-control form-control-sm" type="text" name="token" id="token" value="<?= $esc($this->token); ?>">
					</div>
				</div>

				<!-- Secret key -->
				<div class="scp-field row g-3 align-items-center">
					<div class="col-md-4">
						<div class="fw-semibold" data-bs-toggle="tooltip" data-bs-theme="dark" data-bs-placement="top"
							 title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_SECRET_KEY_EXPLAIN')); ?>">
							<?= Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_GENERATE_KEY_TEXT'); ?>
						</div>
					</div>
					<div class="col-md-8">
						<div class="input-group input-group-sm">
							<input class="form-control" type="text" name="secret_key" id="secret_key" value="<?= $esc($this->secret_key); ?>" readonly>
							<button class="btn btn-outline-secondary" type="button" onclick='document.getElementById("secret_key").value = Password.generate(32)'>
								<?= Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_GENERATE_KEY_TEXT'); ?>
							</button>
						</div>
					</div>
				</div>

				<!-- Control center URL -->
				<div class="scp-field row g-3 align-items-center">
					<div class="col-md-4">
						<div class="fw-semibold" data-bs-toggle="tooltip" data-bs-theme="dark" data-bs-placement="top"
							 title="<?= $esc(Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL_EXPLAIN')); ?>">
							<?= Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL'); ?>
						</div>
					</div>
					<div class="col-md-8">
						<input class="form-control form-control-sm" type="text" name="control_center_url" id="control_center_url"
							   value="<?= (string) ($this->control_center_url); ?>"
							   placeholder="<?= $esc(Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL_PLACEHOLDER')); ?>">
					</div>
				</div>

			</div>
		</div>

		<?php
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
		$mainframe   = Factory::getApplication();
		$cc_status   = $mainframe->getUserState('download_controlcenter_log', null);
		$errorExists = (int) ($this->error_file_exists) === 1;

		if (!empty($cc_status) || $errorExists) :
		?>
		<div class="card mb-3">
			<div class="card-body">
				<p class="fw-semibold mb-1"><?= Text::_('COM_SECURITYCHECKPRO_CONFIG_FILE_MANAGER_LOG_PATH_LABEL'); ?></p>
				<p class="text-muted small mb-3"><?= Text::_('COM_SECURITYCHECKPRO_LOG_FILE_EXPLAIN'); ?></p>
				<div class="d-flex flex-wrap gap-2">
					<?php if (!empty($cc_status)): ?>
						<button class="btn btn-sm btn-success" type="button" onclick="Joomla.submitbutton('download_controlcenter_log');">
							<i class="fa fa-download me-1" aria-hidden="true"></i><?= Text::_('COM_SECURITYCHECKPRO_DOWNLOAD_LOG'); ?>
						</button>
					<?php endif; ?>
					<?php if ($errorExists): ?>
						<button class="btn btn-sm btn-danger" type="button" onclick="add_element_to_form('error_log','1'); Joomla.submitbutton('download_controlcenter_log');">
							<i class="fa fa-download me-1" aria-hidden="true"></i><?= Text::_('COM_SECURITYCHECKPRO_DOWNLOAD_ERROR_LOG'); ?>
						</button>
					<?php endif; ?>
					<button class="btn btn-sm btn-warning" type="button" onclick="Joomla.submitbutton('delete_controlcenter_log');">
						<i class="fa fa-trash me-1" aria-hidden="true"></i><?= Text::_('COM_SECURITYCHECKPRO_CONFIG_FILE_MANAGER_DELETE_LOG_FILE_LABEL'); ?>
					</button>
				</div>
			</div>
		</div>
		<?php endif; ?>

	<?php else: ?>
		<div class="scp-callout scp-callout--danger">
			<i class="fa fa-exclamation-circle me-1" aria-hidden="true"></i>
			<?= Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENCRYPT_LIBRARY_NOT_PRESENT'); ?>
		</div>
	<?php endif; ?>

	<input type="hidden" name="option" value="com_securitycheckpro" />
	<input type="hidden" name="view" value="controlcenter" />
	<input type="hidden" name="boxchecked" value="1" />
	<input type="hidden" name="task" id="task" value="" />
	<input type="hidden" name="controller" value="controlcenter" />
	<?= HTMLHelper::_('form.token'); ?>
</form>
