<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
?>
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<h5 class="fw-semibold mb-4">
			<i class="fa fa-upload text-primary me-2" aria-hidden="true"></i>
			<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_LABEL'); ?>
		</h5>

		<div class="row g-4">
			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="upload_scanner_enabled">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'upload_scanner_enabled',
					'boolean',
					['class' => 'form-select', 'id' => 'upload_scanner_enabled'],
					$this->upload_scanner_enabled,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="check_multiple_extensions">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_CHECK_MULTIPLE_EXTENSIONS_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'check_multiple_extensions',
					'boolean',
					['class' => 'form-select', 'id' => 'check_multiple_extensions'],
					$this->check_multiple_extensions,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_CHECK_MULTIPLE_EXTENSIONS_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="delete_files">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_DELETE_FILES_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'delete_files',
					'boolean',
					['class' => 'form-select', 'id' => 'delete_files'],
					$this->delete_files,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_DELETE_FILES_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<label class="form-label" for="actions_upload_scanner">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_ACTIONS_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'actions_upload_scanner',
					[
						['value' => '0', 'text' => 'COM_SECURITYCHECKPRO_DO_NOTHING'],
						['value' => '1', 'text' => 'COM_SECURITYCHECKPRO_ADD_IP_TO_DYNAMIC_BLACKLIST'],
					],
					['class' => 'form-select', 'id' => 'actions_upload_scanner'],
					$this->actions_upload_scanner,
					false,
					true
				); ?>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_ACTIONS_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6">
				<label class="form-label" for="mimetypes_blacklist">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_MIMETYPES_BLACKLIST_LABEL'); ?>
				</label>
				<textarea id="mimetypes_blacklist" name="mimetypes_blacklist"
						  class="form-control font-monospace"
						  style="height:100px"><?php echo htmlspecialchars((string) $this->mimetypes_blacklist, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_MIMETYPES_BLACKLIST_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6">
				<label class="form-label" for="extensions_blacklist">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_EXTENSIONS_BLACKLIST_LABEL'); ?>
				</label>
				<textarea id="extensions_blacklist" name="extensions_blacklist"
						  class="form-control font-monospace"
						  style="height:100px"><?php echo htmlspecialchars((string) $this->extensions_blacklist, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_EXTENSIONS_BLACKLIST_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
	</div>
</div>
