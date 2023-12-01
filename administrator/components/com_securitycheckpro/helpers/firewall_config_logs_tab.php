<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
?>
<!-- Logs -->
	<div class="card mb-6">
        <div class="card-body">
            <div class="row">
                <div class="col-xl-6 mb-6">
                    <div class="card-header text-white bg-primary">
                        <?php echo Text::_('PLG_SECURITYCHECKPRO_LOGS_LABEL') ?>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_LOG_ATTACKS_DESCRIPTION'); ?></h4>                        
						<div class="controls">
							<?php echo booleanlist('logs_attacks', array(), $this->logs_attacks) ?>
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_LOG_ATTACKS_DESCRIPTION') ?></small></p></blockquote>
                       
                                                
                        <h4 class="card-title"><?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_LOG_DELETE_PERIOD'); ?></h4>                        
                        <div class="controls">
                            <input type="text" size="4" maxlength="4" id="scp_delete_period" name="scp_delete_period" value="<?php echo $this->scp_delete_period ?>" title="" />    
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_LOG_DELETE_PERIOD_DESC') ?></small></p></blockquote>
                                                                                                   
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_LOG_LIMITS_PER_IP_AND_DAY_LABEL'); ?></h4>                    
                        <div class="controls">
							<input type="number" size="4" maxlength="4" id="log_limits_per_ip_and_day" name="log_limits_per_ip_and_day" value="<?php echo $this->log_limits_per_ip_and_day ?>" title="" />
						</div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_COUNTER_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                               
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_ADD_ACCESS_ATTEMPTS_LOGS_LABEL'); ?></h4>                    
                        <div class="controls">
							<?php echo booleanlist('add_access_attempts_logs', array(), $this->add_access_attempts_logs) ?>
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_ADD_ACCESS_ATTEMPTS_LOGS_DESCRIPTION') ?></small></p></blockquote>
                                                                      
                    </div>
                </div>                                        
            </div>
        </div> 
    </div>