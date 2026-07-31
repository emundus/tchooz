<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
?>
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<h5 class="fw-semibold mb-4">
			<i class="fa fa-file-lines text-primary me-2" aria-hidden="true"></i>
			<?php echo Text::_('PLG_SECURITYCHECKPRO_LOGS_LABEL'); ?>
		</h5>

		<div class="row g-4">
			<div class="col-md-6 col-lg-4">
				<label class="form-label" for="logs_attacks">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_LOG_ATTACKS_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'logs_attacks',
					'boolean',
					['class' => 'form-select', 'id' => 'logs_attacks'],
					$this->logs_attacks,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_LOG_ATTACKS_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6 col-lg-4">
				<label class="form-label" for="add_access_attempts_logs">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_ADD_ACCESS_ATTEMPTS_LOGS_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'add_access_attempts_logs',
					'boolean',
					['class' => 'form-select', 'id' => 'add_access_attempts_logs'],
					$this->add_access_attempts_logs,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_ADD_ACCESS_ATTEMPTS_LOGS_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6 col-lg-4">
				<label class="form-label" for="log_limits_per_ip_and_day">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_LOG_LIMITS_PER_IP_AND_DAY_LABEL'); ?>
				</label>
				<input type="number" class="form-control" id="log_limits_per_ip_and_day"
					   name="log_limits_per_ip_and_day" min="0" max="9999"
					   value="<?php echo (int) $this->log_limits_per_ip_and_day; ?>" />
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_LOG_LIMITS_PER_IP_AND_DAY_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6 col-lg-4">
				<label class="form-label" for="scp_delete_period">
					<?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_LOG_DELETE_PERIOD'); ?>
				</label>
				<input type="number" class="form-control" id="scp_delete_period"
					   name="scp_delete_period" min="0" max="9999"
					   value="<?php echo (int) $this->scp_delete_period; ?>" />
				<div class="form-text">
					<?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_LOG_DELETE_PERIOD_DESC'); ?>
				</div>
			</div>
		</div>
	</div>
</div>
