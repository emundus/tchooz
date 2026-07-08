<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Path;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */

$firewallconfigmodel = $this->firewallconfigmodel;

// Counters for tab labels
$blacklistTotal = isset($this->pagination_blacklist)
	? (int) $this->pagination_blacklist->total
	: count($this->blacklist_elements ?? []);
$dynTotal = isset($this->pagination_dynamic_blacklist)
	? (int) $this->pagination_dynamic_blacklist->total
	: count($this->dynamic_blacklist_elements ?? []);
$whitelistTotal = isset($this->pagination_whitelist)
	? (int) $this->pagination_whitelist->total
	: count($this->whitelist_elements ?? []);

// Restore active sub-tab
$validTabs = ['li_blacklist_tab', 'li_dynamic_blacklist_tab', 'li_whitelist_tab'];
$activeTab = in_array($this->activeChild, $validTabs, true) ? $this->activeChild : 'li_blacklist_tab';
?>

<!-- Section 2: Lists management -->
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
			<h5 class="fw-semibold mb-0">
				<i class="fa fa-list text-primary me-2" aria-hidden="true"></i>
				<?php echo Text::_('COM_SECURITYCHECKPRO_LISTS_MANAGEMENT'); ?>
			</h5>

			<!-- Search filter -->
			<div class="input-group input-group-sm" style="max-width:240px">
				<button class="btn btn-outline-secondary" type="button" id="filter_lists_search_submit"
						title="<?php echo Text::_('JSEARCH_FILTER'); ?>"><i class="fa fa-search" aria-hidden="true"></i></button>
				<input type="text" class="form-control"
					   name="filter_lists_search"
					   id="filter_lists_search"
					   placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>"
					   value="<?php echo $this->escape($this->state->get('filter.lists_search', '')); ?>" />
				<button class="btn btn-outline-secondary" type="button" id="filter_lists_search_clear"
						title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
					<i class="fa fa-times" aria-hidden="true"></i>
				</button>
			</div>
		</div>

		<!-- Nav tabs with counters (Bootstrap native, not joomla-tab) -->
		<ul class="nav nav-tabs mb-3" id="ListsTabsNav" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link <?php echo $activeTab === 'li_blacklist_tab' ? 'active' : ''; ?>"
						id="lists-btn-blacklist"
						data-bs-toggle="tab" data-bs-target="#li_blacklist_tab"
						type="button" role="tab"
						aria-controls="li_blacklist_tab"
						aria-selected="<?php echo $activeTab === 'li_blacklist_tab' ? 'true' : 'false'; ?>">
					<?php echo Text::_('COM_SECURITYCHECKPRO_BLACKLIST'); ?>
					<span class="badge bg-secondary ms-1"><?php echo $blacklistTotal; ?></span>
				</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link <?php echo $activeTab === 'li_dynamic_blacklist_tab' ? 'active' : ''; ?>"
						id="lists-btn-dynamic"
						data-bs-toggle="tab" data-bs-target="#li_dynamic_blacklist_tab"
						type="button" role="tab"
						aria-controls="li_dynamic_blacklist_tab"
						aria-selected="<?php echo $activeTab === 'li_dynamic_blacklist_tab' ? 'true' : 'false'; ?>">
					<?php echo Text::_('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST'); ?>
					<span class="badge bg-secondary ms-1"><?php echo $dynTotal; ?></span>
				</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link <?php echo $activeTab === 'li_whitelist_tab' ? 'active' : ''; ?>"
						id="lists-btn-whitelist"
						data-bs-toggle="tab" data-bs-target="#li_whitelist_tab"
						type="button" role="tab"
						aria-controls="li_whitelist_tab"
						aria-selected="<?php echo $activeTab === 'li_whitelist_tab' ? 'true' : 'false'; ?>">
					<?php echo Text::_('COM_SECURITYCHECKPRO_WHITELIST'); ?>
					<span class="badge bg-secondary ms-1"><?php echo $whitelistTotal; ?></span>
				</button>
			</li>
		</ul>

		<!-- Tab content -->
		<div class="tab-content" id="ListsTabsContent">
			<div class="tab-pane fade <?php echo $activeTab === 'li_blacklist_tab' ? 'show active' : ''; ?>"
				 id="li_blacklist_tab" role="tabpanel" aria-labelledby="lists-btn-blacklist">
				<?php
				$tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_list_aux_blacklist_tab.php');
				if (is_file($tabFile)) {
					include $tabFile;
				}
				?>
			</div>
			<div class="tab-pane fade <?php echo $activeTab === 'li_dynamic_blacklist_tab' ? 'show active' : ''; ?>"
				 id="li_dynamic_blacklist_tab" role="tabpanel" aria-labelledby="lists-btn-dynamic">
				<?php
				$tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_list_aux_dynamic_tab.php');
				if (is_file($tabFile)) {
					include $tabFile;
				}
				?>
			</div>
			<div class="tab-pane fade <?php echo $activeTab === 'li_whitelist_tab' ? 'show active' : ''; ?>"
				 id="li_whitelist_tab" role="tabpanel" aria-labelledby="lists-btn-whitelist">
				<?php
				$tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_list_aux_whitelist_tab.php');
				if (is_file($tabFile)) {
					include $tabFile;
				}
				?>
			</div>
		</div>

	</div>
</div>
