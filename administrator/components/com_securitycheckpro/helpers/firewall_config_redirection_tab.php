<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Application\CMSApplication;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
?>
<!-- Redirection -->
	<div class="card mb-6">
        <div class="card-body">
            <div class="row">
                <div class="col-xl-6 mb-6">
                    <div class="card-header text-white bg-primary">
                        <?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECTION_LABEL') ?>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_AFTER_ATTACK_LABEL'); ?></h4>
                        <div class="controls">
							<?php echo $basemodel->renderSelect('redirect_after_attack','boolean',['class' => 'form-select'], $this->redirect_after_attack,false); ?>
                        </div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_AFTER_ATTACK_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                      
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_LABEL'); ?></h4>
                        <div class="controls" id="redirect_options">
							<?php echo $basemodel->renderSelect('redirect_options',['1'=> 'PLG_SECURITYCHECKPRO_JOOMLA_PATH_LABEL','2'=> 'COM_SECURITYCHECKPRO_REDIRECTION_OWN_PAGE'],['class' => 'form-select','onchange' => 'Disable()'],$this->redirect_options,false,true); ?>
                        </div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_DESCRIPTION') ?></small></p></blockquote>
						                                                                                                                                                
                        <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECTION_URL_TEXT'); ?></h4>
                        <div class="input-group">
                            <span class="input-group-text" class="background-8EBBFF"><?php echo $site_url ?></span>
                            <input type="text" class="form-control" id="redirect_url" name="redirect_url" value="<?php echo htmlspecialchars((string) $this->redirect_url, ENT_QUOTES, 'UTF-8');?>" placeholder="<?php echo htmlspecialchars((string) $this->redirect_url, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>                                            
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECTION_URL_EXPLAIN') ?></small></p></blockquote>
                                                                                                                       
                        <div class="control-group">
                            <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_EDITOR_TEXT'); ?></h4>
							<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('COM_SECURITYCHECKPRO_EDITOR_EXPLAIN') ?></small></p></blockquote>                                                                             
								<?php 
								// GET EDITOR SELECTED IN GLOBAL SETTINGS
								/** @var \Joomla\CMS\Application\CMSApplication $app */
								$app = Factory::getApplication();
								$config = $app->getConfig();
								$global_editor = $config->get('editor');

								// GET USER'S DEFAULT EDITOR
								$user_editor = Factory::getApplication()->getIdentity()->getParam("editor");

								if($user_editor && $user_editor !== 'JEditor') {
									$selected_editor = $user_editor;
								} else {
									$selected_editor = $global_editor;
								}

								// INSTANTIATE THE EDITOR
								$editor = Editor::getInstance($selected_editor);
																	
								// SET EDITOR PARAMS
								$params = array( 'smilies'=> '0' ,
								'style'  => '1' ,
								'layer'  => '0' ,
								'table'  => '0' ,
								'clear_entities'=>'0'
								);

								// DISPLAY THE EDITOR (name, html, width, height, columns, rows, bottom buttons, id, asset, author, params)
								echo $editor->display('custom_code', $this->custom_code, '600', '200', '10', '10', true, null, null, null, $params);
								?>                                                    
                        </div>
                    </div>
				</div>                                        
            </div>
        </div> 
    </div>