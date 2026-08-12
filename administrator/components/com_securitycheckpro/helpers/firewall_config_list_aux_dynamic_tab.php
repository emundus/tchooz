<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel $firewallconfigmodel */
?>

<!-- Toolbar row -->
<div class="d-flex align-items-center flex-wrap gap-2 mb-3">
	<i class="fa fa-clock text-secondary" aria-hidden="true"></i>
	<span class="text-muted small flex-grow-1">
		<?php echo Text::_('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_DESCRIPTION'); ?>
	</span>

	<?php if (!empty($this->dynamic_blacklist_elements)) : ?>
	<button class="btn btn-sm btn-outline-danger ms-auto" id="deleteip_dynamic_blacklist_button" type="button">
		<i class="fa fa-trash me-1" aria-hidden="true"></i>
		<?php echo Text::_('COM_SECURITYCHECKPRO_DELETE'); ?>
	</button>
	<?php endif; ?>
</div>

<?php if (!empty($this->pagination_dynamic_blacklist)) : ?>
	<input type="hidden" name="start_dynamic_blacklist" id="start_dynamic_blacklist"
		   value="<?php echo (int) $this->pagination_dynamic_blacklist->limitstart; ?>" />
<?php endif; ?>

<!-- Table -->
<div class="table-responsive">
	<table id="dynamic_blacklist_table" class="table table-bordered table-hover align-middle">
		<thead class="table-light">
			<tr>
				<th class="text-center" style="width:2.5rem">
					<input type="checkbox" class="form-check-input" id="toggle_dynamic_blacklist"
						   onclick="Joomla.checkAll(this);" />
				</th>
				<th><?php echo Text::_('Ip'); ?></th>
				<th class="text-center" style="width:3rem"><?php echo Text::_('COM_SECURITYCHECKPRO_ACTIONS'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if (!empty($this->dynamic_blacklist_elements)) :
			$k = 0;
			foreach ($this->dynamic_blacklist_elements as $row_dynamic) :
				/** @var stdClass $row_dynamic */
				$ip = (string) ($row_dynamic->ip ?? '');
		?>
			<tr>
				<td class="text-center">
					<?php echo HTMLHelper::_('grid.id', $k, $ip, '', 'dynamic_blacklist_cid'); ?>
				</td>
				<td><?php echo htmlspecialchars($ip, ENT_QUOTES, 'UTF-8'); ?></td>
				<td class="text-center">
					<button type="button" class="btn btn-sm btn-link text-danger p-0 scp-delete-row"
							data-list="dynamic_blacklist"
							data-ip="<?php echo htmlspecialchars($ip, ENT_QUOTES, 'UTF-8'); ?>"
							data-task="deleteip_dynamic_blacklist"
							title="<?php echo Text::_('COM_SECURITYCHECKPRO_DELETE'); ?>">
						<i class="fa fa-trash" aria-hidden="true"></i>
					</button>
				</td>
			</tr>
		<?php
			$k++;
			endforeach;
		else : ?>
			<tr><td colspan="3" class="text-center text-muted py-3">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</div>

<?php if (isset($this->pagination_dynamic_blacklist)) : ?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
	<div><?php echo $firewallconfigmodel->getLimitBox('dynamic_blacklist', $this->pagination_dynamic_blacklist); ?></div>
	<div><?php echo $this->pagination_dynamic_blacklist->getListFooter(); ?></div>
</div>
<?php endif; ?>
