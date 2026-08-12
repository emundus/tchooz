<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel $firewallconfigmodel */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
?>

<!-- Blacklist import modal -->
<div class="modal fade" id="select_blacklist_file_to_upload" tabindex="-1"
	 aria-labelledby="blacklistfileuploadLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header alert alert-info mb-0">
				<h2 class="modal-title h5" id="blacklistfileuploadLabel">
					<?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_SETTINGS'); ?>
				</h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal"
						aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
			</div>
			<div class="modal-body">
				<h5><?php echo Text::_('COM_SECURITYCHECKPRO_SELECT_EXPORTED_FILE'); ?></h5>
				<input class="form-control" id="file_to_import_blacklist"
					   name="file_to_import_blacklist" type="file" />
			</div>
			<div class="modal-footer" id="div_boton_subida_blacklist">
				<input class="btn btn-primary" id="upload_import_button" type="button"
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
	<i class="fa fa-ban text-danger" aria-hidden="true"></i>
	<span class="text-muted small flex-grow-1">
		<?php echo Text::_('COM_SECURITYCHECKPRO_BLACKLIST_DESCRIPTION'); ?>
	</span>

	<div class="d-flex align-items-center gap-2">
		<label class="form-label small mb-0 text-nowrap">
			<?php echo Text::_('COM_SECURITYCHECKPRO_NOTIFY_BY_EMAIL'); ?>
		</label>
		<?php echo $basemodel->renderSelect(
			'blacklist_email',
			'boolean',
			['class' => 'form-select form-select-sm', 'style' => 'width:auto'],
			$this->blacklist_email,
			false
		); ?>
	</div>

	<!-- Accepted formats collapsible -->
	<button type="button" class="btn btn-sm btn-outline-secondary"
			data-bs-toggle="collapse" data-bs-target="#blacklist-formats"
			aria-expanded="false" aria-controls="blacklist-formats">
		<i class="fa fa-circle-info me-1" aria-hidden="true"></i>
		<?php echo Text::_('COM_SECURITYCHECKPRO_ALLOWED_FORMATS'); ?>
	</button>
</div>

<!-- Formats help (collapsible) -->
<div class="collapse mb-3" id="blacklist-formats">
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
<div class="d-flex flex-wrap gap-2 align-items-center mb-2" id="blacklist_buttons">
	<input type="text" class="form-control form-control-sm"
		   style="max-width:220px"
		   name="blacklist_add_ip" id="blacklist_add_ip"
		   placeholder="<?php echo Text::_('COM_SECURITYCHECKPRO_NEW_IP_OR_RANGE'); ?>"
		   title="<?php echo Text::_('COM_SECURITYCHECKPRO_NEW_IP_LABEL'); ?>" />

	<button class="btn btn-sm btn-success" id="add_ip_blacklist_button" type="button">
		<i class="fa fa-plus me-1" aria-hidden="true"></i>
		<?php echo Text::_('COM_SECURITYCHECKPRO_ADD'); ?>
	</button>

	<a href="#select_blacklist_file_to_upload" role="button"
	   class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
	   title="<?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_IPS'); ?>">
		<i class="fa fa-upload" aria-hidden="true"></i>
	</a>

	<button class="btn btn-sm btn-outline-secondary" id="export_blacklist_button" type="button"
			title="<?php echo Text::_('COM_SECURITYCHECKPRO_EXPORT_IPS'); ?>">
		<i class="fa fa-download" aria-hidden="true"></i>
	</button>

	<?php if (!empty($this->blacklist_elements)) : ?>
	<button class="btn btn-sm btn-outline-danger ms-auto" id="delete_ip_blacklist_button" type="button">
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
	<button type="button" id="add_ip_whitelist_button2" class="btn btn-link btn-sm p-0 align-baseline">
		<?php echo Text::_('COM_SECURITYCHECKPRO_ADD_TO_WHITELIST'); ?>
	</button>
</p>

<input type="hidden" name="active_tab" id="active_tab" value="blacklist" />
<?php if (!empty($this->pagination_blacklist)) : ?>
	<input type="hidden" name="start_blacklist" id="start_blacklist"
		   value="<?php echo (int) $this->pagination_blacklist->limitstart; ?>" />
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
		<?php if (!empty($this->blacklist_elements)) :
			$k = 0;
			foreach ($this->blacklist_elements as $row) :
				/** @var stdClass $row */
				$ip = (string) ($row->ip ?? '');
		?>
			<tr>
				<td class="text-center">
					<?php echo HTMLHelper::_('grid.id', $k, $ip); ?>
				</td>
				<td><?php echo htmlspecialchars($ip, ENT_QUOTES, 'UTF-8'); ?></td>
				<td class="text-center">
					<button type="button" class="btn btn-sm btn-link text-danger p-0 scp-delete-row"
							data-list="blacklist"
							data-ip="<?php echo htmlspecialchars($ip, ENT_QUOTES, 'UTF-8'); ?>"
							data-task="deleteip_blacklist"
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

<?php if (isset($this->pagination_blacklist)) : ?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
	<div><?php echo $firewallconfigmodel->getLimitBox('blacklist', $this->pagination_blacklist); ?></div>
	<div><?php echo $this->pagination_blacklist->getListFooter(); ?></div>
</div>
<?php endif; ?>
