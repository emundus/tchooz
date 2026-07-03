<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */

if ($this->plugin_trackactions_installed) {
	$db = Factory::getContainer()->get(DatabaseInterface::class);
	$db->setQuery('SELECT DISTINCT extension FROM #__securitycheckpro_trackactions_extensions');
	/** @var array<int, array{extension:string}> $extensions */
	$extensions = $db->loadAssocList();
	$optionsTrack = [];
	foreach ($extensions as $row) {
		$optionsTrack[] = HTMLHelper::_('select.option', (string) $row['extension'], (string) $row['extension']);
	}
}
?>
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<h5 class="fw-semibold mb-4">
			<i class="fa fa-chart-line text-primary me-2" aria-hidden="true"></i>
			<?php echo Text::_('COM_SECURITYCHECKPRO_TRACK_ACTIONS'); ?>
		</h5>

		<?php if ($this->plugin_trackactions_installed) : ?>
		<div class="row g-4">
			<div class="col-md-4">
				<div class="row g-4">
					<div class="col-12">
						<label class="form-label" for="delete_period">
							<?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_LOG_DELETE_PERIOD'); ?>
						</label>
						<input type="number" class="form-control" id="delete_period" name="delete_period"
							   min="0" max="999"
							   value="<?php echo (int) $this->delete_period; ?>" />
						<div class="form-text">
							<?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_LOG_DELETE_PERIOD_DESC'); ?>
						</div>
					</div>

					<div class="col-12">
						<label class="form-label" for="ip_logging">
							<?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_IP_LOGGING'); ?>
						</label>
						<?php echo $basemodel->renderSelect(
							'ip_logging',
							'boolean',
							['class' => 'form-select', 'id' => 'ip_logging'],
							$this->ip_logging,
							false
						); ?>
						<div class="form-text">
							<?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_IP_LOGGING_DESC'); ?>
						</div>
					</div>
				</div>
			</div>

			<div class="col-md-8">
				<label class="form-label" for="loggable_extensions">
					<?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_LOG_EXTENSIONS'); ?>
				</label>
				<?php echo HTMLHelper::_(
					'select.genericlist',
					$optionsTrack,
					'loggable_extensions[]',
					'class="form-select" multiple="multiple" id="loggable_extensions" style="min-height:200px"',
					'value',
					'text',
					$this->loggable_extensions
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_LOG_EXTENSIONS_DESC'); ?>
				</div>
			</div>
		</div>

		<?php else : ?>
		<div class="alert alert-warning mb-0">
			<?php echo Text::_('COM_SECURITYCHECKPRO_TRACKACTIONS_NOT_INSTALLED'); ?>
		</div>
		<?php endif; ?>
	</div>
</div>
