 <?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
?>
<!-- User session protection -->
 <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-xl-3 mb-3">
                                            <div class="card-header text-white bg-primary">
                                                <?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_LABEL') ?>
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                    $params = Factory::getConfig();        
                                                    $shared_session_enabled = $params->get('shared_session');
                                                    
                                                if (!$shared_session_enabled ) {
                                                    ?>
                                                
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_ACTIVE_LABEL'); ?></h4>
                                                <div class="controls">
                                                    <?php echo booleanlist('session_protection_active', array(), $this->session_protection_active) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_ACTIVE_LABEL') ?></small></p></blockquote>
                                               
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_LABEL'); ?></h4>
                                                <div class="controls">
                                                    <?php echo booleanlist('session_hijack_protection', array(), $this->session_hijack_protection) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_DESCRIPTION') ?></small></p></blockquote>
                                                												
												<h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_WHAT_TO_CHECK_LABEL'); ?></h4>
                                                <div class="controls">
                                                    <?php echo what_to_check('session_hijack_protection_what_to_check', array(), $this->session_hijack_protection_what_to_check) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_WHAT_TO_CHECK_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                               
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_GROUPS_LABEL'); ?></h4>
                                                <div class="controls">
                                                    <?php
                                                    // Listamos todos los grupos presentes en el sistema excepto el grupo 'Guest'
                                                    $db = Factory::getDBO();
                                                    $query = "SELECT id,title from #__usergroups WHERE title != 'Guest'";            
                                                    $db->setQuery($query);
                                                    $groups = $db->loadRowList();                        
                                                    foreach ($groups as $key=>$value) {                            
                                                        $options[] = HTMLHelper::_('select.option', $value[0], $value[1]);                            
                                                    }
                                                    echo HTMLHelper::_('select.genericlist', $options, 'session_protection_groups[]', 'class="form-select" multiple="multiple"', 'value', 'text',  $this->session_protection_groups);                                                 
                                                    ?>                    
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_GROUPS_DESCRIPTION') ?></small></p></blockquote> <?php
                                                } else {
                                                    ?> 
													<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SHARED_SESSIONS_EANBLED') ?></small></p></blockquote>
                                                <?php	    
                                                }
                                                ?>
                                            </div>
                                        </div>    
                                        
                                        <div class="col-xl-3 mb-3">
                                            <div class="card-header text-white bg-primary">
                                                <?php echo Text::_('PLG_SECURITYCHECKPRO_TRACK_FAILED_LOGINS') ?>
                                            </div>
                                            <div class="card-body">
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_TRACK_FAILED_LOGINS_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo booleanlist('track_failed_logins', array(), $this->track_failed_logins) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_TRACK_FAILED_LOGINS_LABEL') ?></small></p></blockquote>
                                                                                                                                            
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_LOGINS_TO_MONITORIZE_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo email_actions('logins_to_monitorize', array(), $this->logins_to_monitorize) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_LOGINS_TO_MONITORIZE_DESCRIPTION') ?></small></p></blockquote>
                                                
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_WRITE_LOG_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo booleanlist('write_log', array(), $this->write_log) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_WRITE_LOG_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_UPLOADSCANNER_ACTIONS_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo actions_failed_login('actions_failed_login', array(), $this->actions_failed_login) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_UPLOADSCANNER_ACTIONS_DESCRIPTION') ?></small></p></blockquote>
												</div>
                                        </div>
                                        
                                        <div class="col-xl-3 mb-3">
                                            <div class="card-header text-white bg-primary">
                                                <?php echo Text::_('PLG_SECURITYCHECKPRO_ADMIN_LOGINS') ?>
                                            </div>
                                            <div class="card-body">
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ON_BACKEND_LOGIN_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo booleanlist('email_on_admin_login', array(), $this->email_on_admin_login) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ON_BACKEND_LOGIN_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_FORBID_ADMIN_FRONTEND_LOGIN_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo booleanlist('forbid_admin_frontend_login', array(), $this->forbid_admin_frontend_login) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_FORBID_ADMIN_FRONTEND_LOGIN_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_FORBID_NEW_ADMINS_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo booleanlist('forbid_new_admins', array(), $this->forbid_new_admins) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_FORBID_NEW_ADMINS_DESCRIPTION') ?></small></p></blockquote>        </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>