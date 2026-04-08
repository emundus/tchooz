<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */

?>
<!-- Lists -->
    <div class="card mb-3">                                            
        <div class="card-body">
            <div class="row">
                <div class="col-xl-3 mb-3">
                    <div class="card-header text-white bg-primary">
						<?php echo Text::_('PLG_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_LABEL') ?>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_LABEL'); ?></h4>
                        <div class="controls">					
							<?php echo $basemodel->renderSelect('dynamic_blacklist','boolean',['class' => 'form-select'], $this->dynamic_blacklist,false); ?>							
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_DESCRIPTION') ?></small></p></blockquote>
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_TIME_LABEL'); ?></h4>
                        <div class="controls">
							<input type="number" class="form-control" id="dynamic_blacklist_time" name="dynamic_blacklist_time" value="<?php echo (int) $this->dynamic_blacklist_time; ?>" min="1" max="99999" step="1" />       
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_TIME_DESCRIPTION') ?></small></p></blockquote>
						<h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_COUNTER_LABEL'); ?></h4>
                        <div class="controls">
							<input type="number" class="form-control" id="dynamic_blacklist_counter" name="dynamic_blacklist_counter" value="<?php echo (int) $this->dynamic_blacklist_counter; ?>" min="1" max="99999" step="1" />       
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_COUNTER_DESCRIPTION') ?></small></p></blockquote>
					</div> 
				<!-- End col -->
                </div>
                <div class="col-xl-3 mb-3">
                    <div class="card-header text-white bg-primary">
						<?php echo Text::_('PLG_SECURITYCHECKPRO_BLACKLIST_LABEL') ?>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_BLACKLIST_EMAIL_LABEL'); ?></h4>
                        <div class="controls">
							<?php echo $basemodel->renderSelect('blacklist_email','boolean',['class' => 'form-select'], $this->blacklist_email,false); ?>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_BLACKLIST_EMAIL_LABEL') ?></small></p></blockquote>
					</div>
				<!-- End col -->
                </div>                                            
                <div class="col-xl-3 mb-3">
					<div class="card-header text-white bg-primary">
						<?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS') ?>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_PRIORITY_LABEL'); ?></h4>
                        <label for="priority" class="control-label" title="<?php echo Text::_('First'); ?>"><?php echo Text::_('First'); ?></label>
                        <div class="controls">
							<?php echo $basemodel->renderSelect('priority1',['Blacklist'=> 'PLG_SECURITYCHECKPRO_BLACKLIST','Whitelist'=> 'PLG_SECURITYCHECKPRO_WHITELIST','DynamicBlacklist'=> 'PLG_SECURITYCHECKPRO_DYNAMICBLACKLIST',],['class' => 'form-select'],$this->priority1,false,true); ?>							
                        </div>
                        <label for="priority" class="control-label" title="<?php echo Text::_('Second'); ?>"><?php echo Text::_('Second'); ?></label>
                        <div class="controls">
							<?php echo $basemodel->renderSelect('priority2',['Blacklist'=> 'PLG_SECURITYCHECKPRO_BLACKLIST','Whitelist'=> 'PLG_SECURITYCHECKPRO_WHITELIST','DynamicBlacklist'=> 'PLG_SECURITYCHECKPRO_DYNAMICBLACKLIST',],['class' => 'form-select'],$this->priority2,false,true); ?>							
                        </div>
                        <label for="priority" class="control-label" title="<?php echo Text::_('Third'); ?>"><?php echo Text::_('Third'); ?></label>
                        <div class="controls">
							<?php echo $basemodel->renderSelect('priority3',['Blacklist'=> 'PLG_SECURITYCHECKPRO_BLACKLIST','Whitelist'=> 'PLG_SECURITYCHECKPRO_WHITELIST','DynamicBlacklist'=> 'PLG_SECURITYCHECKPRO_DYNAMICBLACKLIST',],['class' => 'form-select'],$this->priority3,false,true); ?>
                        </div>                                                        
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_PRIORITY_LABEL') ?></small></p></blockquote>                                                    
                    </div>
				<!-- End col -->
                </div>
			<!-- End row -->
            </div>
			<?php include JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/firewall_config_list_aux_tab.php'; ?>
		<!-- End card-body -->
        </div> 		
	<!-- End card -->
    </div>       