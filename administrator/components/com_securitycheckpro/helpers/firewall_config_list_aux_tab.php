<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Path;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */

$firewallconfigmodel = $this->firewallconfigmodel;
?>
<!-- Aux Lists tab -->
    <div class="card mb-3">    
        <div class="card-header">
			<i class="fa fa-bars"></i>
            <?php echo Text::_('COM_SECURITYCHECKPRO_LISTS_MANAGEMENT'); ?>
        </div>
        <div class="card-body">
			<div id="filter-bar" class="filter-search-bar btn-group margin-bottom-10">
				<div class="row">
					<div class="col-auto">
						<div class="input-group">
							<input type="text" class="form-control" name="filter_lists_search" placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>" id="filter_lists_search" value="<?php echo $this->escape($this->state->get('filter.lists_search', '')); ?>" title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />
							<span class="filter-search-bar__label visually-hidden">
							<label id="filter_search-lbl" for="filter_search">Filter:</label>
							</span>
							<button type="submit" class="filter-search-bar__button btn btn-primary" aria-label="Search">
								<span class="filter-search-bar__button-icon icon-search" aria-hidden="true"></span>
							</button>
							<button class="btn btn-dark" type="button" id="filter_lists_search_clear" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
						</div>
					</div>
				</div>
			</div>				
           			
			<?php 
				echo HTMLHelper::_('uitab.startTabSet', 'ListsTabs', [
					'active'     => $this->activeChild,					
					'breakpoint' => 768
				]);				
							
				// --- Pestaña: Lista negra
				echo HTMLHelper::_('uitab.addTab', 'ListsTabs', 'li_blacklist_tab', Text::_('COM_SECURITYCHECKPRO_BLACKLIST'));
				$tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_list_aux_blacklist_tab.php');
				if (is_file($tabFile)) {
					include $tabFile;
				}
				echo HTMLHelper::_('uitab.endTab');
				
				// --- Pestaña: Lista negra dinámica
				echo HTMLHelper::_('uitab.addTab', 'ListsTabs', 'li_dynamic_blacklist_tab', Text::_('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST'));
				$tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_list_aux_dynamic_tab.php');
				if (is_file($tabFile)) {
					include $tabFile;
				}
				echo HTMLHelper::_('uitab.endTab');
				
				// --- Pestaña: Lista blanca
				echo HTMLHelper::_('uitab.addTab', 'ListsTabs', 'li_whitelist_tab', Text::_('COM_SECURITYCHECKPRO_WHITELIST'));
				$tabFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/firewall_config_list_aux_whitelist_tab.php');
				if (is_file($tabFile)) {
					include $tabFile;
				}
				echo HTMLHelper::_('uitab.endTab');
				
				echo HTMLHelper::_('uitab.endTabSet');
			?>			
        </div>		
    </div>    