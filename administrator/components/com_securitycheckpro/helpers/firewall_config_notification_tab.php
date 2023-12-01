<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
?>
<!-- Notification -->
	<div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-xl-3 mb-3">
                    <div class="card-header text-white bg-primary">
                        <?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS') ?>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ACTIVE_LABEL'); ?></h4>
                        <div class="controls">
							<?php echo booleanlist('email_active', array(), $this->email_active) ?>
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ACTIVE_DESCRIPTION') ?></small></p></blockquote>
						                                                                                                
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_SUBJECT_LABEL'); ?></h4>
                        <div class="controls">
							<input type="text" class="form-control" size="30" name="email_subject" value="<?php echo $this->email_subject ?>" title="" />        
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_SUBJECT_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                   
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_BODY_LABEL'); ?></h4>
                        <div class="controls">
                            <textarea class="form-control" style="height: 100px" name="email_body" ><?php echo $this->email_body ?></textarea>
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_BODY_DESCRIPTION') ?></small></p></blockquote>                       
                    </div>
                </div>    
                                            
                <div class="col-xl-3 mb-3">
                    <div class="card-header text-white bg-primary">
                        <?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS') ?>
                    </div>
					<div class="card-body">
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_TO_LABEL'); ?></h4>
                        <div class="controls">
                            <input type="text" class="form-control" size="30" id="email_to" name="email_to" value="<?php echo $this->email_to ?>" title="" />        
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_TO_DESCRIPTION') ?></small></p></blockquote>    
                                                                 
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_FROM_DOMAIN_LABEL'); ?></h4>
                        <div class="controls">
                            <input type="text" class="form-control" size="30" id="email_from_domain" name="email_from_domain" value="<?php echo $this->email_from_domain ?>" title="" />        
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_FROM_DOMAIN_DESCRIPTION') ?></small></p></blockquote> 
                                                                
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_FROM_NAME_LABEL'); ?></h4>
                        <div class="controls">
                            <input type="text" size="30" name="email_from_name" value="<?php echo $this->email_from_name ?>" title="" />        
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_FROM_NAME_DESCRIPTION') ?></small></p></blockquote> 
                        <div class="controls">
                            <input class="btn btn-primary" type="button" id="boton_test_email" value="<?php echo Text::_('COM_SECURITYCHECKPRO_SEND_EMAIL_TEST'); ?>" />        
                        </div>                                                
                    </div>
                </div>
                                        
                <div class="col-xl-3 mb-3">
                    <div class="card-header text-white bg-primary">
                        <?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS') ?>
                    </div>
                    <div class="card-body">
						<h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ADD_APPLIED_RULE_LABEL'); ?></h4>
                        <div class="controls">
							<?php echo booleanlist('email_add_applied_rule', array(), $this->email_add_applied_rule) ?>
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ADD_APPLIED_RULE_DESCRIPTION') ?></small></p></blockquote> 
                                                        
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_MAX_NUMBER_LABEL'); ?></h4>
                        <div class="controls">
							<input type="number" size="3" maxlength="3" id="email_max_number" name="email_max_number" value="<?php echo $this->email_max_number ?>" title="" />        
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_MAX_NUMBER_DESCRIPTION') ?></small></p></blockquote>                       
                    </div>
                </div>
            </div>
        </div> 
    </div>