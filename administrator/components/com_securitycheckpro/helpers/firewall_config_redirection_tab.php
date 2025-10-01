<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Editor\Editor;
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
							<?php echo booleanlist('redirect_after_attack', array(), $this->redirect_after_attack) ?>
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_AFTER_ATTACK_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                      
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_LABEL'); ?></h4>
                        <div class="controls" id="redirect_options">
							<?php echo redirectionlist('redirect_options', array(), $this->redirect_options) ?>
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_DESCRIPTION') ?></small></p></blockquote>
						                                                                                                                                                
                        <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECTION_URL_TEXT'); ?></h4>
                        <div class="input-group">
                            <span class="input-group-text" class="background-8EBBFF"><?php echo $site_url ?></span>
                            <input type="text" class="form-control" id="redirect_url" name="redirect_url" value="<?php echo $this->redirect_url?>" placeholder="<?php echo $this->redirect_url ?>">
                        </div>                                            
                        <blockquote><p class="text-info"><small><?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECTION_URL_EXPLAIN') ?></small></p></blockquote>
                                                                                                                       
                        <div class="control-group">
                            <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_EDITOR_TEXT'); ?></h4>
							<blockquote><p class="text-info"><small><?php echo Text::_('COM_SECURITYCHECKPRO_EDITOR_EXPLAIN') ?></small></p></blockquote>                                                                             
								<?php 
								// GET EDITOR SELECTED IN GLOBAL SETTINGS
								$config = Factory::getConfig();
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