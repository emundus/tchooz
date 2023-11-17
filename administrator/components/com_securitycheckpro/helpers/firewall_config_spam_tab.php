 <?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
?>
<!-- Spam protection -->
<?php if ($this->plugin_installed ) { ?>                            
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-xl-3 mb-3">
                                                <div class="card-header text-white bg-primary">
                                                    <?php echo Text::_('COM_SECURITYCHECKPRO_CHECK_USERS') ?>
                                                </div>
                                                <div class="card-body">
                                                            
                                                    <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_IF_USER_IS_SPAMMER_LABEL'); ?></h4>
                                                    <div class="controls">
                                                        <?php echo booleanlist('check_if_user_is_spammer', array(), $this->check_if_user_is_spammer) ?>
                                                    </div>
													<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_IF_USER_IS_SPAMMER_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                           
                                                    <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_ACTION_LABEL'); ?></h4>
                                                    <div class="controls">
                                                        <?php echo spammer_action('spammer_action', array(), $this->spammer_action) ?>
                                                    </div>
													<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_ACTION_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                           
                                                    <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_WRITE_LOG_LABEL'); ?></h4>
                                                    <div class="controls">
                                                        <?php echo booleanlist('spammer_write_log', array(), $this->spammer_write_log) ?>
                                                    </div>
													<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_WRITE_LOG_DESCRIPTION') ?></small></p></blockquote>
                                                </div>
                                            </div>
                                            
                                            <div class="col-xl-3 mb-3">
                                                <div class="card-header text-white bg-primary">
                                                    <?php echo Text::_('COM_SECURITYCHECKPRO_CHECK_USERS') ?>
                                                </div>
                                                <div class="card-body">
                                                    <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_WHAT_TO_CHECK_LABEL'); ?></h4>
                                                    <div class="controls">
                                                        <?php
                                                        $options_spam[] = HTMLHelper::_('select.option', 0, Text::_('PLG_SECURITYCHECKPRO_EMAIL'));                            
                                                        $options_spam[] = HTMLHelper::_('select.option', 1, Text::_('PLG_SECURITYCHECKPRO_IP'));
                                                        $options_spam[] = HTMLHelper::_('select.option', 2, Text::_('PLG_SECURITYCHECKPRO_USERNAME'));
                                                        if (!is_array($this->spammer_what_to_check) ) {                            
                                                            $this->spammer_what_to_check = array('Email','IP','Username');
                                                        }                        
                                                        echo HTMLHelper::_('select.genericlist', $options_spam, 'spammer_what_to_check[]', 'class="form-select" multiple="multiple"', 'text', 'text',  $this->spammer_what_to_check);                                                
                                                        ?>                    
                                                    </div>
													<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_WHAT_TO_CHECK_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                           
                                                    <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_LIMIT_LABEL'); ?></h4>
                                                    <div class="controls">
                                                        <input type="number" size="3" maxlength="3" id="spammer_limit" name="spammer_limit" value="<?php echo $this->spammer_limit ?>" title="" />  
                                                    </div>
													<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_LIMIT_DESCRIPTION') ?></small></p></blockquote>
                                                    													
													<h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAM_PROTECTION_INCLUDE_URLS_LABEL'); ?></h4>
                                                    <div class="controls">
														<textarea cols="35" rows="3" name="include_urls_spam_protection"><?php echo $this->include_urls_spam_protection ?></textarea>
													</div>
													<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAM_PROTECTION_INCLUDE_URLS_DESCRIPTION') ?></small></p></blockquote>
                                                </div>
                                            </div>
											
											<div class="col-xl-3 mb-3">
                                                <div class="card-header text-white bg-primary">
                                                    <?php echo Text::_('COM_SECURITYCHECKPRO_HONEYPOT_PROTECTION') ?>
                                                </div>
												<div class="card-body">                                                                                                    
													<h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_SPAM_PROTECTION_FORMS_LABEL'); ?></h4>
													<div class="controls">
														<textarea name="form-control forms_to_include_honeypot_in"><?php echo $this->forms_to_include_honeypot_in ?></textarea>
													</div>
													<blockquote><p class="text-info"><small><?php echo Text::_('COM_SECURITYCHECKPRO_SPAM_PROTECTION_FORMS_LABEL_DESCRIPTION') ?></small></p></blockquote>
												</div>                                               
                                            </div>										
                                        </div>
                                    </div>
                                </div>
							<?php } else { ?>
                                <div class="alert alert-warning centrado">
									<?php echo Text::_('COM_SECURITYCHECK_SPAM_PROTECTION_NOT_INSTALLED'); ?>    
                                </div>
                                <div class="alert alert-info centrado">
									<?php echo Text::_('COM_SECURITYCHECK_WHY_IS_NOT_INCLUDED'); ?>    
                                </div>
							<?php }  ?>