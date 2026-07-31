<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
?>
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<h5 class="fw-semibold mb-4">
			<i class="fa fa-magnifying-glass text-primary me-2" aria-hidden="true"></i>
			<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_URL_INSPECTOR_TEXT'); ?>
		</h5>

		<?php if (!$this->url_inspector_enabled) : ?>
		<div class="alert alert-warning d-flex align-items-center justify-content-between gap-3 mb-4">
			<span><?php echo Text::_('COM_SECURITYCHECKPRO_URL_INPECTOR_DISABLED'); ?></span>
			<button type="button" id="enable_url_inspector_button" class="btn btn-sm btn-success text-nowrap">
				<i class="fa fa-check me-1" aria-hidden="true"></i>
				<?php echo Text::_('COM_SECURITYCHECKPRO_ENABLE'); ?>
			</button>
		</div>
		<?php endif; ?>

		<div class="row g-4">
			<div class="col-sm-6 col-lg-4">
				<label class="form-label" for="write_log_inspector">
					<?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_WRITE_LOG_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'write_log_inspector',
					'boolean',
					['class' => 'form-select', 'id' => 'write_log_inspector'],
					$this->write_log_inspector,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_WRITE_LOG_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-4">
				<label class="form-label" for="action_inspector">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_ACTIONS_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'action_inspector',
					[
						['value' => '0', 'text' => 'COM_SECURITYCHECKPRO_DO_NOTHING'],
						['value' => '1', 'text' => 'COM_SECURITYCHECKPRO_ADD_IP_TO_DYNAMIC_BLACKLIST'],
						['value' => '2', 'text' => 'COM_SECURITYCHECKPRO_ADD_IP_TO_BLACKLIST'],
					],
					['class' => 'form-select', 'id' => 'action_inspector'],
					$this->action_inspector,
					false,
					true
				); ?>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_ACTION_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-4">
				<label class="form-label" for="send_email_inspector">
					<?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_SEND_EMAIL_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'send_email_inspector',
					'boolean',
					['class' => 'form-select', 'id' => 'send_email_inspector'],
					$this->send_email_inspector,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_SEND_EMAIL_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-12">
				<label class="form-label" for="inspector_forbidden_words">
					<?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_FORBIDDEN_WORDS_LABEL'); ?>
				</label>
				<textarea id="inspector_forbidden_words" name="inspector_forbidden_words"
						  class="form-control font-monospace"
						  style="height:160px"><?php echo htmlspecialchars((string) $this->inspector_forbidden_words, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_FORBIDDEN_WORDS_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
	</div>
</div>
