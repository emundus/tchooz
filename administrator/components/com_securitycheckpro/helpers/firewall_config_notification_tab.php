<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
?>
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<h5 class="fw-semibold mb-4">
			<i class="fa fa-envelope text-primary me-2" aria-hidden="true"></i>
			<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_NOTIFICATIONS_LABEL'); ?>
		</h5>

		<div class="row g-4">
			<!-- Row 1: toggles -->
			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="email_active">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ACTIVE_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'email_active',
					'boolean',
					['class' => 'form-select', 'id' => 'email_active'],
					$this->email_active,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ACTIVE_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="email_add_applied_rule">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ADD_APPLIED_RULE_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'email_add_applied_rule',
					'boolean',
					['class' => 'form-select', 'id' => 'email_add_applied_rule'],
					$this->email_add_applied_rule,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ADD_APPLIED_RULE_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-2">
				<label class="form-label" for="email_max_number">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_MAX_NUMBER_LABEL'); ?>
				</label>
				<input type="number" class="form-control" id="email_max_number"
					   name="email_max_number" min="1" max="999"
					   value="<?php echo (int) $this->email_max_number; ?>" />
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_MAX_NUMBER_DESCRIPTION'); ?>
				</div>
			</div>

			<!-- Row 2: subject + recipient -->
			<div class="col-md-6">
				<label class="form-label" for="email_subject">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_SUBJECT_LABEL'); ?>
				</label>
				<input type="text" class="form-control" id="email_subject" name="email_subject"
					   value="<?php echo htmlspecialchars((string) $this->email_subject, ENT_QUOTES, 'UTF-8'); ?>" />
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_SUBJECT_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6">
				<label class="form-label" for="email_to">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_TO_LABEL'); ?>
				</label>
				<input type="email" class="form-control" id="email_to" name="email_to"
					   value="<?php echo htmlspecialchars((string) $this->email_to, ENT_QUOTES, 'UTF-8'); ?>" />
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_TO_DESCRIPTION'); ?>
				</div>
			</div>

			<!-- Row 3: sender fields + test button -->
			<div class="col-md-4">
				<label class="form-label" for="email_from_domain">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_FROM_DOMAIN_LABEL'); ?>
				</label>
				<input type="text" class="form-control" id="email_from_domain" name="email_from_domain"
					   value="<?php echo htmlspecialchars((string) $this->email_from_domain, ENT_QUOTES, 'UTF-8'); ?>" />
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_FROM_DOMAIN_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-4">
				<label class="form-label" for="email_from_name">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_FROM_NAME_LABEL'); ?>
				</label>
				<input type="text" class="form-control" id="email_from_name" name="email_from_name"
					   value="<?php echo htmlspecialchars((string) $this->email_from_name, ENT_QUOTES, 'UTF-8'); ?>" />
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_FROM_NAME_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-4">
				<label class="form-label">&nbsp;</label>
				<button type="button" id="boton_test_email" class="btn btn-outline-secondary w-100">
					<i class="fa fa-paper-plane me-1" aria-hidden="true"></i>
					<?php echo Text::_('COM_SECURITYCHECKPRO_SEND_EMAIL_TEST'); ?>
				</button>
				<div class="form-text">&nbsp;</div>
			</div>

			<!-- Row 4: body -->
			<div class="col-12">
				<label class="form-label" for="email_body">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_BODY_LABEL'); ?>
				</label>
				<textarea class="form-control" id="email_body" name="email_body"
						  style="height:120px"><?php echo htmlspecialchars((string) $this->email_body, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_BODY_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
	</div>
</div>
