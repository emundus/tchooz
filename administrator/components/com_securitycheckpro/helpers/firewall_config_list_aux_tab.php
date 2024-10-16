<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
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
							<input type="text" class="form-control" name="filter_lists_search" placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>" id="filter_lists_search" value="<?php echo $this->escape($this->state->get('filter.lists_search')); ?>" title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />
							<span class="filter-search-bar__label visually-hidden">
							<label id="filter_search-lbl" for="filter_search">Filter:</label>
							</span>
							<button type="submit" class="filter-search-bar__button btn btn-primary" aria-label="Search">
								<span class="filter-search-bar__button-icon icon-search" aria-hidden="true"></span>
							</button>
							<button class="btn btn-dark" type="button" id="filter_lists_search_clear" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
						</div>
					</div>			                                
				   <div class="col-auto">
						<?php	            
						if (isset($this->pagination) ) {                                    
						?>
						<div class="btn-group pull-right">
							<label for="limit" class="element-invisible"><?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
							<?php echo $this->pagination->getLimitBox(); ?>
						</div>            
						<?php
						}
						?>
					</div>
				</div>
			</div>				
           			
			<?php echo HTMLHelper::_('bootstrap.startTabSet', 'ListsTabs'); ?>
				<?php echo HTMLHelper::_('bootstrap.addTab', 'ListsTabs', 'li_blacklist_tab', Text::_('COM_SECURITYCHECKPRO_BLACKLIST')); ?>
					<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_list_aux_blacklist_tab.php'; ?>
				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
				
				<?php echo HTMLHelper::_('bootstrap.addTab', 'ListsTabs', 'li_dynamic_blacklist_tab', Text::_('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST')); ?>
					<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_list_aux_dynamic_tab.php'; ?>
				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
				
				<?php echo HTMLHelper::_('bootstrap.addTab', 'ListsTabs', 'li_whitelist_tab', Text::_('COM_SECURITYCHECKPRO_WHITELIST')); ?>
					<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_list_aux_whitelist_tab.php'; ?>
				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>			
			
			<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>			
        </div>
		<?php	            
			if (isset($this->pagination) ) {                                    
					echo $this->pagination->getListFooter();
			}
		?>
    </div>    