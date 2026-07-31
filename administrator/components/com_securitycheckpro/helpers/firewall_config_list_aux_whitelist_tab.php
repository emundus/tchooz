<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel $firewallconfigmodel */
?>

<!-- Whitelist import modal -->
<div class="modal fade" id="select_whitelist_file_to_upload" tabindex="-1"
	 aria-labelledby="whitelistfileuploadLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header alert alert-info mb-0">
				<h2 class="modal-title h5" id="whitelistfileuploadLabel">
					<?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_SETTINGS'); ?>
				</h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal"
						aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
			</div>
			<div class="modal-body">
				<h5><?php echo Text::_('COM_SECURITYCHECKPRO_SELECT_EXPORTED_FILE'); ?></h5>
				<input class="form-control" id="file_to_import_whitelist"
					   name="file_to_import_whitelist" type="file" />
			</div>
			<div class="modal-footer" id="div_boton_subida_whitelist">
				<input class="btn btn-primary" id="import_whitelist_button" type="button"
					   value="<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOAD_AND_IMPORT'); ?>" />
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
					<?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Toolbar row -->
<div class="d-flex align-items-center flex-wrap gap-2 mb-3">
	<i class="fa fa-check-circle text-success" aria-hidden="true"></i>
	<span class="text-muted small flex-grow-1">
		<?php echo Text::_('COM_SECURITYCHECKPRO_WHITELIST_DESCRIPTION'); ?>
	</span>

	<!-- Accepted formats collapsible -->
	<button type="button" class="btn btn-sm btn-outline-secondary"
			data-bs-toggle="collapse" data-bs-target="#whitelist-formats"
			aria-expanded="false" aria-controls="whitelist-formats">
		<i class="fa fa-circle-info me-1" aria-hidden="true"></i>
		<?php echo Text::_('COM_SECURITYCHECKPRO_ALLOWED_FORMATS'); ?>
	</button>
</div>

<!-- Formats help (collapsible) -->
<div class="collapse mb-3" id="whitelist-formats">
	<div class="card card-body small text-body-secondary">
		<strong class="d-block mb-1"><?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_HEADER'); ?></strong>
		<strong><?php echo Text::_('COM_SECURITYCHECKPRO_IPV4'); ?></strong>
		<ul class="mb-2 ps-3">
			<li><strong><?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_SINGLE'); ?></strong>
				— <var><?php echo htmlspecialchars((string) $this->current_ip, ENT_QUOTES, 'UTF-8'); ?></var></li>
			<li><strong><?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_RANGE'); ?></strong>
				— <var><?php echo htmlspecialchars((string) $this->range_example, ENT_QUOTES, 'UTF-8'); ?></var></li>
			<li><strong><?php echo Text::_('COM_SECURITYCHECKPRO_CIDR'); ?></strong>
				— <var><?php echo htmlspecialchars((string) $this->cidr_v4_example, ENT_QUOTES, 'UTF-8'); ?></var></li>
		</ul>
		<strong><?php echo Text::_('COM_SECURITYCHECKPRO_IPV6'); ?></strong>
		<ul class="mb-0 ps-3">
			<li><strong><?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_SINGLE'); ?></strong> — <var>2001:13d0::1</var></li>
			<li><strong><?php echo Text::_('COM_SECURITYCHECKPRO_CIDR'); ?></strong> — <var>2001:13d0::/29</var></li>
		</ul>
	</div>
</div>

<!-- Add IP + actions row -->
<div class="d-flex flex-wrap gap-2 align-items-center mb-2" id="whitelist_buttons">
	<input type="text" class="form-control form-control-sm"
		   style="max-width:220px"
		   name="whitelist_add_ip" id="whitelist_add_ip"
		   placeholder="<?php echo Text::_('COM_SECURITYCHECKPRO_NEW_IP_OR_RANGE'); ?>"
		   title="<?php echo Text::_('COM_SECURITYCHECKPRO_NEW_IP_LABEL'); ?>" />

	<button class="btn btn-sm btn-success" id="addip_whitelist_button" type="button">
		<i class="fa fa-plus me-1" aria-hidden="true"></i>
		<?php echo Text::_('COM_SECURITYCHECKPRO_ADD'); ?>
	</button>

	<a href="#select_whitelist_file_to_upload" role="button"
	   class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
	   title="<?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_IPS'); ?>">
		<i class="fa fa-upload" aria-hidden="true"></i>
	</a>

	<button class="btn btn-sm btn-outline-secondary" id="export_whitelist_button" type="button"
			title="<?php echo Text::_('COM_SECURITYCHECKPRO_EXPORT_IPS'); ?>">
		<i class="fa fa-download" aria-hidden="true"></i>
	</button>

	<?php if (!empty($this->whitelist_elements)) : ?>
	<button class="btn btn-sm btn-outline-danger ms-auto" id="deleteip_whitelist_button" type="button">
		<i class="fa fa-trash me-1" aria-hidden="true"></i>
		<?php echo Text::_('COM_SECURITYCHECKPRO_DELETE'); ?>
	</button>
	<?php endif; ?>
</div>

<!-- Current IP hint -->
<p class="small text-muted mb-3">
	<?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_CURRENT'); ?>
	<code><?php echo htmlspecialchars((string) $this->current_ip, ENT_QUOTES, 'UTF-8'); ?></code>
	·
	<button type="button" id="add_ip_whitelist_button" class="btn btn-link btn-sm p-0 align-baseline">
		<?php echo Text::_('COM_SECURITYCHECKPRO_ADD_TO_WHITELIST'); ?>
	</button>
</p>

<?php if (!empty($this->pagination_whitelist)) : ?>
	<input type="hidden" name="start_whitelist" id="start_whitelist"
		   value="<?php echo (int) $this->pagination_whitelist->limitstart; ?>" />
<?php endif; ?>

<!-- Table -->
<div class="table-responsive">
	<table class="table table-bordered table-hover align-middle">
		<thead class="table-light">
			<tr>
				<th class="text-center" style="width:2.5rem">
					<input type="checkbox" class="form-check-input" name="toggle"
						   onclick="Joomla.checkAll(this);" />
				</th>
				<th><?php echo Text::_('Ip'); ?></th>
				<th class="text-center" style="width:3rem"><?php echo Text::_('COM_SECURITYCHECKPRO_ACTIONS'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if (!empty($this->whitelist_elements)) :
			$k = 0;
			foreach ($this->whitelist_elements as $row) :
				/** @var stdClass $row */
				$ip = (string) ($row->ip ?? '');
		?>
			<tr>
				<td class="text-center">
					<?php echo HTMLHelper::_('grid.id', $k, $ip, '', 'whitelist_cid'); ?>
				</td>
				<td><?php echo htmlspecialchars($ip, ENT_QUOTES, 'UTF-8'); ?></td>
				<td class="text-center">
					<button type="button" class="btn btn-sm btn-link text-danger p-0 scp-delete-row"
							data-list="whitelist"
							data-ip="<?php echo htmlspecialchars($ip, ENT_QUOTES, 'UTF-8'); ?>"
							data-task="deleteip_whitelist"
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

<?php if (isset($this->pagination_whitelist)) : ?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
	<div><?php echo $firewallconfigmodel->getLimitBox('whitelist', $this->pagination_whitelist); ?></div>
	<div><?php echo $this->pagination_whitelist->getListFooter(); ?></div>
</div>
<?php endif; ?>
