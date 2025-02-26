 <?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
?>
<!-- Url inspector -->
<?php if ($this->url_inspector_enabled == 0) { ?>
                            <div class="alert alert-warning centrado">
                                <h4><?php echo Text::_('COM_SECURITYCHECKPRO_URL_INPECTOR_DISABLED'); ?></h4>
                                <button id="enable_url_inspector_button" class="btn btn-success" href="#">
                                    <i class="icon-ok icon-white"> </i>
									<?php echo Text::_('COM_SECURITYCHECKPRO_ENABLE'); ?>
                                </button>            
                            </div>
        <?php } ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-xl-3 mb-3">
                                            <div class="card-header text-white bg-primary">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS') ?>
                                            </div>
                                            <div class="card-body">                                                                                                    
                                                <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_WRITE_LOG_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo booleanlist('write_log_inspector', array(), $this->write_log_inspector) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_WRITE_LOG_DESCRIPTION') ?></small></p></blockquote>
                                                                                            
                                                <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_ACTIONS_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo action('action_inspector', array(), $this->action_inspector) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_ACTION_DESCRIPTION') ?></small></p></blockquote>
                                                                                              
                                                <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_SEND_EMAIL_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo booleanlist('send_email_inspector', array(), $this->send_email_inspector) ?>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_SEND_EMAIL_DESCRIPTION') ?></small></p></blockquote>
                                            </div>
                                        </div>
                                        
                                        <div class="col-xl-8 mb-8">
                                            <div class="card-header text-white bg-primary">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS') ?>
                                            </div>
                                            <div class="card-body">                                                                                                    
                                                <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_FORBIDDEN_WORDS_LABEL'); ?></h4>
                                                <div class="controls">
                                                    <textarea name="inspector_forbidden_words" class="form-control width_560_height_340"><?php echo $this->inspector_forbidden_words ?></textarea>
                                                </div>
												<blockquote><p class="text-info"><small><?php echo Text::_('COM_SECURITYCHECKPRO_URL_INSPECTOR_FORBIDDEN_WORDS_DESCRIPTION') ?></small></p></blockquote>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>   