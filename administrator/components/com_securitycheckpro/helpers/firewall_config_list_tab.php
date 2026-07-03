<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */

$listConfig = [
	'Whitelist'        => [
		'label'     => Text::_('COM_SECURITYCHECKPRO_WHITELIST'),
		'badge'     => 'bg-success',
		'badgeText' => Text::_('COM_SECURITYCHECKPRO_LIST_ACTION_PERMIT'),
	],
	'DynamicBlacklist' => [
		'label'     => Text::_('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST'),
		'badge'     => 'bg-secondary',
		'badgeText' => Text::_('COM_SECURITYCHECKPRO_LIST_ACTION_AUTO'),
	],
	'Blacklist'        => [
		'label'     => Text::_('COM_SECURITYCHECKPRO_BLACKLIST'),
		'badge'     => 'bg-danger',
		'badgeText' => Text::_('COM_SECURITYCHECKPRO_LIST_ACTION_REJECT'),
	],
];

$priorityOrder = [
	$this->priority1 ?? 'Whitelist',
	$this->priority2 ?? 'DynamicBlacklist',
	$this->priority3 ?? 'Blacklist',
];
?>

<!-- Section 1: How the firewall decides -->
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<h5 class="fw-semibold mb-4">
			<i class="fa fa-sliders-h text-primary me-2" aria-hidden="true"></i>
			<?php echo Text::_('COM_SECURITYCHECKPRO_HOW_FIREWALL_DECIDES'); ?>
		</h5>
		<div class="row g-4">

			<!-- Left: Dynamic engine settings -->
			<div class="col-lg-5">
				<p class="text-muted small fw-semibold text-uppercase mb-3">
					<?php echo Text::_('COM_SECURITYCHECKPRO_DYNAMIC_ENGINE_LABEL'); ?>
				</p>

				<label class="form-label small mb-1">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'dynamic_blacklist',
					'boolean',
					['class' => 'form-select form-select-sm mb-3'],
					$this->dynamic_blacklist,
					false
				); ?>

				<div class="row g-3">
					<div class="col">
						<label class="form-label small mb-1">
							<?php echo Text::_('COM_SECURITYCHECKPRO_BLOCKING_TIME_LABEL'); ?>
						</label>
						<div class="input-group input-group-sm">
							<input type="number" class="form-control"
								   id="dynamic_blacklist_time" name="dynamic_blacklist_time"
								   value="<?php echo (int) $this->dynamic_blacklist_time; ?>"
								   min="1" max="99999" />
							<span class="input-group-text"><?php echo Text::_('COM_SECURITYCHECKPRO_SECONDS_SHORT'); ?></span>
						</div>
						<div class="form-text"><?php echo Text::_('PLG_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_TIME_DESCRIPTION'); ?></div>
					</div>
					<div class="col">
						<label class="form-label small mb-1">
							<?php echo Text::_('COM_SECURITYCHECKPRO_MAX_ATTEMPTS_LABEL'); ?>
						</label>
						<input type="number" class="form-control form-control-sm"
							   id="dynamic_blacklist_counter" name="dynamic_blacklist_counter"
							   value="<?php echo (int) $this->dynamic_blacklist_counter; ?>"
							   min="1" max="99999" />
						<div class="form-text"><?php echo Text::_('PLG_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_COUNTER_DESCRIPTION'); ?></div>
					</div>
				</div>
			</div>

			<!-- Right: Evaluation order (draggable) -->
			<div class="col-lg-7">
				<p class="text-muted small fw-semibold text-uppercase mb-3">
					<?php echo Text::_('COM_SECURITYCHECKPRO_EVALUATION_ORDER'); ?>
				</p>

				<ul id="priority-order-list" class="list-unstyled d-flex flex-column gap-2 mb-2">
					<?php foreach ($priorityOrder as $listKey) :
						$cfg = $listConfig[$listKey] ?? ['label' => $listKey, 'badge' => 'bg-secondary', 'badgeText' => ''];
					?>
					<li class="d-flex align-items-center gap-3 rounded border bg-body px-3 py-2 scp-draggable"
						draggable="true"
						data-list="<?php echo htmlspecialchars($listKey, ENT_QUOTES, 'UTF-8'); ?>">
						<i class="fa fa-grip-vertical text-muted" style="cursor:grab" aria-hidden="true"></i>
						<span class="fw-semibold flex-grow-1">
							<?php echo htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8'); ?>
						</span>
						<span class="badge <?php echo $cfg['badge']; ?>">
							<?php echo htmlspecialchars($cfg['badgeText'], ENT_QUOTES, 'UTF-8'); ?>
						</span>
					</li>
					<?php endforeach; ?>
				</ul>

				<p class="text-muted small mb-0">
					<i class="fa fa-circle-info me-1" aria-hidden="true"></i>
					<?php echo Text::_('COM_SECURITYCHECKPRO_EVALUATION_ORDER_HINT'); ?>
				</p>

				<!-- Hidden inputs updated by drag-and-drop JS -->
				<input type="hidden" id="priority1" name="priority1"
					   value="<?php echo htmlspecialchars($this->priority1 ?? 'Whitelist', ENT_QUOTES, 'UTF-8'); ?>">
				<input type="hidden" id="priority2" name="priority2"
					   value="<?php echo htmlspecialchars($this->priority2 ?? 'DynamicBlacklist', ENT_QUOTES, 'UTF-8'); ?>">
				<input type="hidden" id="priority3" name="priority3"
					   value="<?php echo htmlspecialchars($this->priority3 ?? 'Blacklist', ENT_QUOTES, 'UTF-8'); ?>">
			</div>

		</div>
	</div>
</div>

<?php include JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_list_aux_tab.php'; ?>
