 <?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Application\CMSApplication;

/** @var \Joomla\CMS\Application\CMSApplication $app */
$app = Factory::getApplication();

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
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
                                                    $params = $app->getConfig();        
                                                    $shared_session_enabled = $params->get('shared_session');
                                                    
                                                if (!$shared_session_enabled ) {
                                                    ?>
                                                
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_ACTIVE_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('session_protection_active','boolean',['class' => 'form-select'], $this->session_protection_active,false); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_ACTIVE_LABEL') ?></small></p></blockquote>
                                               
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('session_hijack_protection','boolean',['class' => 'form-select'], $this->session_hijack_protection,false); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_DESCRIPTION') ?></small></p></blockquote>
                                                												
												<h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_WHAT_TO_CHECK_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('session_hijack_protection_what_to_check',['1'=> Text::sprintf('PLG_SECURITYCHECKPRO_IP_USER_AGENT', "OR"),'2'=> Text::sprintf('PLG_SECURITYCHECKPRO_IP_USER_AGENT', "AND")],['class' => 'form-select'],$this->session_hijack_protection_what_to_check,false,false); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_HIJACK_PROTECTION_WHAT_TO_CHECK_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                               
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_GROUPS_LABEL'); ?></h4>
                                                <div class="controls">
                                                    <?php
                                                    // Listamos todos los grupos presentes en el sistema excepto el grupo 'Guest'
                                                    $db = Factory::getContainer()->get(DatabaseInterface::class);
                                                    $query = "SELECT id,title from #__usergroups WHERE title != 'Guest'";            
                                                    $db->setQuery($query);
													/** @var array<int, array{id:int|string, title:string}> $groups */
													$groups = $db->loadAssocList();
                                                    /** @var array<int, object> $options */
													$options = [];
													foreach ($groups as $row) {
														$options[] = HTMLHelper::_('select.option', (string)$row['id'], (string)$row['title']);
													}
                                                    echo HTMLHelper::_('select.genericlist', $options, 'session_protection_groups[]', 'class="form-select" multiple="multiple"', 'value', 'text',  $this->session_protection_groups);                                                 
                                                    ?>                    
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_GROUPS_DESCRIPTION') ?></small></p></blockquote> <?php
                                                } else {
                                                    ?> 
													<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SHARED_SESSIONS_EANBLED') ?></small></p></blockquote>
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
													<?php echo $basemodel->renderSelect('track_failed_logins','boolean',['class' => 'form-select'], $this->track_failed_logins,false); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_TRACK_FAILED_LOGINS_LABEL') ?></small></p></blockquote>
                                                                                                                                            
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_LOGINS_TO_MONITORIZE_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('logins_to_monitorize',[['value'=>'0','text'=>'COM_SECURITYCHECKPRO_EMAIL_BOTH_INCORRECT'],['value'=>'1','text'=>'COM_SECURITYCHECKPRO_EMAIL_ONLY_FRONTEND'],['value'=>'2','text'=>'COM_SECURITYCHECKPRO_EMAIL_ONLY_BACKEND']],['class' => 'form-select'],$this->logins_to_monitorize,false,true); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_LOGINS_TO_MONITORIZE_DESCRIPTION') ?></small></p></blockquote>
                                                
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_WRITE_LOG_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('write_log','boolean',['class' => 'form-select'], $this->write_log,false); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_WRITE_LOG_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_UPLOADSCANNER_ACTIONS_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('actions_failed_login',[['value'=>'0','text'=>'COM_SECURITYCHECKPRO_DO_NOTHING'],['value'=>'1','text'=>'COM_SECURITYCHECKPRO_ADD_IP_TO_DYNAMIC_BLACKLIST']],['class' => 'form-select'],$this->actions_failed_login,false,true); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_UPLOADSCANNER_ACTIONS_DESCRIPTION') ?></small></p></blockquote>
												</div>
                                        </div>
                                        
                                        <div class="col-xl-3 mb-3">
                                            <div class="card-header text-white bg-primary">
                                                <?php echo Text::_('PLG_SECURITYCHECKPRO_ADMIN_LOGINS') ?>
                                            </div>
                                            <div class="card-body">
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ON_BACKEND_LOGIN_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('email_on_admin_login','boolean',['class' => 'form-select'], $this->email_on_admin_login,false); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_EMAIL_ON_BACKEND_LOGIN_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_FORBID_ADMIN_FRONTEND_LOGIN_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('forbid_admin_frontend_login','boolean',['class' => 'form-select'], $this->forbid_admin_frontend_login,false); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_FORBID_ADMIN_FRONTEND_LOGIN_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                
                                                <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_FORBID_NEW_ADMINS_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('forbid_new_admins','boolean',['class' => 'form-select'], $this->forbid_new_admins,false); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_FORBID_NEW_ADMINS_DESCRIPTION') ?></small></p></blockquote>        </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>