<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */

/** @var \Joomla\CMS\Application\CMSApplication $app */
$app           = Factory::getApplication();
$sharedSession = (bool) $app->getConfig()->get('shared_session');

// Load user groups (all except Guest) for the multiselect
$db = Factory::getContainer()->get(DatabaseInterface::class);
$db->setQuery("SELECT id, title FROM #__usergroups WHERE title != 'Guest'");
/** @var array<int, array{id:int|string, title:string}> $groups */
$groups = $db->loadAssocList();
$groupOptions = [];
foreach ($groups as $row) {
	$groupOptions[] = HTMLHelper::_('select.option', (string) $row['id'], (string) $row['title']);
}
?>
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<h5 class="fw-semibold mb-4">
			<i class="fa fa-user-shield text-primary me-2" aria-hidden="true"></i>
			<?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_LABEL'); ?>
		</h5>

		<!-- Section 1: Session hijack protection -->
		<p class="text-muted small fw-semibold text-uppercase mb-3">
			<?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_LABEL'); ?>
		</p>

		<?php if ($sharedSession) : ?>
		<div class="alert alert-warning">
			<?php echo Text::_('PLG_SECURITYCHECKPRO_SHARED_SESSIONS_EANBLED'); ?>
		</div>
		<?php else : ?>
		<div class="row g-4 mb-4">
			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="session_protection_active">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_ACTIVE_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'session_protection_active',
					'boolean',
					['class' => 'form-select', 'id' => 'session_protection_active'],
					$this->session_protection_active,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_ACTIVE_LABEL'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="session_hijack_protection">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'session_hijack_protection',
					'boolean',
					['class' => 'form-select', 'id' => 'session_hijack_protection'],
					$this->session_hijack_protection,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="session_hijack_protection_what_to_check">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_WHAT_TO_CHECK_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'session_hijack_protection_what_to_check',
					[
						['value' => '1', 'text' => Text::sprintf('PLG_SECURITYCHECKPRO_IP_USER_AGENT', 'OR')],
						['value' => '2', 'text' => Text::sprintf('PLG_SECURITYCHECKPRO_IP_USER_AGENT', 'AND')],
					],
					['class' => 'form-select', 'id' => 'session_hijack_protection_what_to_check'],
					$this->session_hijack_protection_what_to_check,
					false,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_WHAT_TO_CHECK_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="session_protection_groups">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_GROUPS_LABEL'); ?>
				</label>
				<?php echo HTMLHelper::_(
					'select.genericlist',
					$groupOptions,
					'session_protection_groups[]',
					'class="form-select" multiple="multiple" id="session_protection_groups" style="min-height:120px"',
					'value',
					'text',
					$this->session_protection_groups
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_GROUPS_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<hr class="my-4">

		<!-- Section 2: Failed logins -->
		<p class="text-muted small fw-semibold text-uppercase mb-3">
			<?php echo Text::_('PLG_SECURITYCHECKPRO_TRACK_FAILED_LOGINS'); ?>
		</p>

		<div class="row g-4 mb-4">
			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="track_failed_logins">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_TRACK_FAILED_LOGINS_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'track_failed_logins',
					'boolean',
					['class' => 'form-select', 'id' => 'track_failed_logins'],
					$this->track_failed_logins,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_TRACK_FAILED_LOGINS_LABEL'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="logins_to_monitorize">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_LOGINS_TO_MONITORIZE_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'logins_to_monitorize',
					[
						['value' => '0', 'text' => 'COM_SECURITYCHECKPRO_EMAIL_BOTH_INCORRECT'],
						['value' => '1', 'text' => 'COM_SECURITYCHECKPRO_EMAIL_ONLY_FRONTEND'],
						['value' => '2', 'text' => 'COM_SECURITYCHECKPRO_EMAIL_ONLY_BACKEND'],
					],
					['class' => 'form-select', 'id' => 'logins_to_monitorize'],
					$this->logins_to_monitorize,
					false,
					true
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_LOGINS_TO_MONITORIZE_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="write_log">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_WRITE_LOG_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'write_log',
					'boolean',
					['class' => 'form-select', 'id' => 'write_log'],
					$this->write_log,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_WRITE_LOG_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="actions_failed_login">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_UPLOADSCANNER_ACTIONS_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'actions_failed_login',
					[
						['value' => '0', 'text' => 'COM_SECURITYCHECKPRO_DO_NOTHING'],
						['value' => '1', 'text' => 'COM_SECURITYCHECKPRO_ADD_IP_TO_DYNAMIC_BLACKLIST'],
					],
					['class' => 'form-select', 'id' => 'actions_failed_login'],
					$this->actions_failed_login,
					false,
					true
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_UPLOADSCANNER_ACTIONS_DESCRIPTION'); ?>
				</div>
			</div>
		</div>

		<hr class="my-4">

		<!-- Section 3: Admin logins -->
		<p class="text-muted small fw-semibold text-uppercase mb-3">
			<?php echo Text::_('PLG_SECURITYCHECKPRO_ADMIN_LOGINS'); ?>
		</p>

		<div class="row g-4">
			<div class="col-sm-6 col-lg-4">
				<label class="form-label" for="email_on_admin_login">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ON_BACKEND_LOGIN_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'email_on_admin_login',
					'boolean',
					['class' => 'form-select', 'id' => 'email_on_admin_login'],
					$this->email_on_admin_login,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ON_BACKEND_LOGIN_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-4">
				<label class="form-label" for="forbid_admin_frontend_login">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_FORBID_ADMIN_FRONTEND_LOGIN_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'forbid_admin_frontend_login',
					'boolean',
					['class' => 'form-select', 'id' => 'forbid_admin_frontend_login'],
					$this->forbid_admin_frontend_login,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_FORBID_ADMIN_FRONTEND_LOGIN_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-4">
				<label class="form-label" for="forbid_new_admins">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_FORBID_NEW_ADMINS_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'forbid_new_admins',
					'boolean',
					['class' => 'form-select', 'id' => 'forbid_new_admins'],
					$this->forbid_new_admins,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_FORBID_NEW_ADMINS_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
	</div>
</div>
