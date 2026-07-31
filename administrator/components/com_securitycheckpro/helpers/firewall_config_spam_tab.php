<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */

$this->spammer_what_to_check = (array) ($this->spammer_what_to_check ?? ['Email', 'IP', 'Username']);

$optionsSpam = [
	HTMLHelper::_('select.option', 0, Text::_('PLG_SECURITYCHECKPRO_EMAIL')),
	HTMLHelper::_('select.option', 1, Text::_('PLG_SECURITYCHECKPRO_IP')),
	HTMLHelper::_('select.option', 2, Text::_('PLG_SECURITYCHECKPRO_USERNAME')),
];
?>
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<h5 class="fw-semibold mb-4">
			<i class="fa fa-shield-virus text-primary me-2" aria-hidden="true"></i>
			<?php echo Text::_('COM_SECURITYCHECKPRO_SPAM_PROTECTION'); ?>
		</h5>

		<!-- Arbitrary strings (always shown) -->
		<div class="row g-4 mb-4">
			<div class="col-sm-6 col-lg-4">
				<label class="form-label" for="detect_arbitrary_strings">
					<?php echo Text::_('COM_SECURITYCHECKPRO_ARBITRARY_STRING_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'detect_arbitrary_strings',
					'boolean',
					['class' => 'form-select', 'id' => 'detect_arbitrary_strings'],
					$this->detect_arbitrary_strings,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_ARBITRARY_STRING_DESCRIPTION'); ?>
				</div>
			</div>
		</div>

		<?php if ($this->plugin_installed) : ?>

		<hr class="my-4">

		<!-- Section: Check users -->
		<p class="text-muted small fw-semibold text-uppercase mb-3">
			<?php echo Text::_('COM_SECURITYCHECKPRO_CHECK_USERS'); ?>
		</p>

		<div class="row g-4 mb-4">
			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="check_if_user_is_spammer">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_IF_USER_IS_SPAMMER_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'check_if_user_is_spammer',
					'boolean',
					['class' => 'form-select', 'id' => 'check_if_user_is_spammer'],
					$this->check_if_user_is_spammer,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_IF_USER_IS_SPAMMER_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="spammer_action">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_ACTION_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'spammer_action',
					[
						['value' => '0', 'text' => 'COM_SECURITYCHECKPRO_DO_NOTHING'],
						['value' => '1', 'text' => 'COM_SECURITYCHECKPRO_ADD_IP_TO_DYNAMIC_BLACKLIST'],
					],
					['class' => 'form-select', 'id' => 'spammer_action'],
					$this->spammer_action,
					false,
					true
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_ACTION_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="spammer_write_log">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_WRITE_LOG_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'spammer_write_log',
					'boolean',
					['class' => 'form-select', 'id' => 'spammer_write_log'],
					$this->spammer_write_log,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_WRITE_LOG_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="spammer_limit">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_LIMIT_LABEL'); ?>
				</label>
				<input type="number" class="form-control" id="spammer_limit" name="spammer_limit"
					   min="1" max="999"
					   value="<?php echo (int) $this->spammer_limit; ?>" />
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_LIMIT_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-4">
				<label class="form-label" for="spammer_what_to_check">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_WHAT_TO_CHECK_LABEL'); ?>
				</label>
				<?php echo HTMLHelper::_(
					'select.genericlist',
					$optionsSpam,
					'spammer_what_to_check[]',
					'class="form-select" multiple="multiple" id="spammer_what_to_check" style="min-height:100px"',
					'text',
					'text',
					$this->spammer_what_to_check
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_WHAT_TO_CHECK_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-8">
				<label class="form-label" for="include_urls_spam_protection">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SPAM_PROTECTION_INCLUDE_URLS_LABEL'); ?>
				</label>
				<textarea id="include_urls_spam_protection" name="include_urls_spam_protection"
						  class="form-control font-monospace"
						  style="height:100px"><?php echo htmlspecialchars((string) $this->include_urls_spam_protection, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SPAM_PROTECTION_INCLUDE_URLS_DESCRIPTION'); ?>
				</div>
			</div>
		</div>

		<hr class="my-4">

		<!-- Section: Honeypot -->
		<p class="text-muted small fw-semibold text-uppercase mb-3">
			<?php echo Text::_('COM_SECURITYCHECKPRO_HONEYPOT_PROTECTION'); ?>
		</p>

		<div class="row g-4">
			<div class="col-md-8">
				<label class="form-label" for="forms_to_include_honeypot_in">
					<?php echo Text::_('COM_SECURITYCHECKPRO_SPAM_PROTECTION_FORMS_LABEL'); ?>
				</label>
				<textarea id="forms_to_include_honeypot_in" name="forms_to_include_honeypot_in"
						  class="form-control font-monospace"
						  style="height:100px"><?php echo htmlspecialchars((string) $this->forms_to_include_honeypot_in, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_SPAM_PROTECTION_FORMS_LABEL_DESCRIPTION'); ?>
				</div>
			</div>
		</div>

		<?php else : ?>

		<hr class="my-3">
		<div class="alert alert-warning mb-2">
			<?php echo Text::_('COM_SECURITYCHECK_SPAM_PROTECTION_NOT_INSTALLED'); ?>
		</div>
		<div class="alert alert-info mb-0">
			<?php echo Text::_('COM_SECURITYCHECK_WHY_IS_NOT_INCLUDED'); ?>
		</div>

		<?php endif; ?>
	</div>
</div>
