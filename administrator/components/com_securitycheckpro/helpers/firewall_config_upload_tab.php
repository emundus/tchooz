 <?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
?>
<!-- Upload scanner -->
<div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-xl-3 mb-3">
                                            <div class="card-header text-white bg-primary">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS') ?>
                                            </div>
                                            <div class="card-body">
                                                <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('upload_scanner_enabled','boolean',['class' => 'form-select'], $this->upload_scanner_enabled,false); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                
                                                <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_CHECK_MULTIPLE_EXTENSIONS_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('check_multiple_extensions','boolean',['class' => 'form-select'], $this->check_multiple_extensions,false); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_CHECK_MULTIPLE_EXTENSIONS_DESCRIPTION') ?></small></p></blockquote>                                      
                                            </div>
                                        </div>
                                        
                                        <div class="col-xl-3 mb-3">
                                            <div class="card-header text-white bg-primary">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS') ?>
                                            </div>
                                            <div class="card-body">											
												<h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_MIMETYPES_BLACKLIST_LABEL'); ?></h4>
                                                <div class="controls">
                                                    <textarea name="mimetypes_blacklist" class="form-control mimetypes-blacklist"><?php echo htmlspecialchars((string) $this->mimetypes_blacklist, ENT_QUOTES, 'UTF-8'); ?></textarea>                                
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_MIMETYPES_BLACKLIST_DESCRIPTION') ?></small></p></blockquote>                                                											
												
                                                <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_EXTENSIONS_BLACKLIST_LABEL'); ?></h4>
                                                <div class="controls">
                                                    <textarea name="extensions_blacklist" class="form-control extensions-blacklist"><?php echo htmlspecialchars((string) $this->extensions_blacklist, ENT_QUOTES, 'UTF-8'); ?></textarea>
												</div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_EXTENSIONS_BLACKLIST_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                
                                                <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_DELETE_FILES_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('delete_files','boolean',['class' => 'form-select'], $this->delete_files,false); ?>
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_DELETE_FILES_DESCRIPTION') ?></small></p></blockquote>
                                                </div>
                                        </div>
                                        
                                        <div class="col-xl-3 mb-3">
                                            <div class="card-header text-white bg-primary">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS') ?>
                                            </div>
                                            <div class="card-body">
                                                <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_ACTIONS_LABEL'); ?></h4>
                                                <div class="controls">
													<?php echo $basemodel->renderSelect('actions_upload_scanner',[['value'=>'0','text'=>'COM_SECURITYCHECKPRO_DO_NOTHING'],['value'=>'1','text'=>'COM_SECURITYCHECKPRO_ADD_IP_TO_DYNAMIC_BLACKLIST']],['class' => 'form-select'],$this->actions_upload_scanner,false,true); ?>	
                                                </div>
												<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_ACTIONS_DESCRIPTION') ?></small></p></blockquote>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>