 <?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
?>
<!-- Spam protection -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-xl-6 mb-6">
                <div class="card-header text-white bg-primary">
                    <?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS') ?>
                </div>
                <div class="card-body">
                    <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_ARBITRARY_STRING_LABEL'); ?></h4>
                    <div class="controls">
						<?php echo $basemodel->renderSelect('detect_arbitrary_strings','boolean',['class' => 'form-select'],$this->detect_arbitrary_strings,false); ?>
                    </div>
					<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('COM_SECURITYCHECKPRO_ARBITRARY_STRING_DESCRIPTION') ?></small></p></blockquote>                
				</div>
			</div>
		</div>
    </div>
 </div>
										
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
							<?php echo $basemodel->renderSelect('check_if_user_is_spammer','boolean',['class' => 'form-select'],$this->check_if_user_is_spammer,false); ?>
                        </div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_IF_USER_IS_SPAMMER_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                        
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_ACTION_LABEL'); ?></h4>
                        <div class="controls">
							<?php echo $basemodel->renderSelect('spammer_action',[['value'=>'0','text'=>'COM_SECURITYCHECKPRO_DO_NOTHING'],['value'=>'1','text'=>'COM_SECURITYCHECKPRO_ADD_IP_TO_DYNAMIC_BLACKLIST']],['class' => 'form-select'],$this->spammer_action,false,true); ?>
                        </div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_ACTION_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                           
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_WRITE_LOG_LABEL'); ?></h4>
                        <div class="controls">
							<?php echo $basemodel->renderSelect('spammer_write_log','boolean',['class' => 'form-select'],$this->spammer_write_log,false); ?>
                        </div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_WRITE_LOG_DESCRIPTION') ?></small></p></blockquote>
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
                               /** @var array<int,object> $options_spam */
								$options_spam = [];
								$options_spam[] = HTMLHelper::_('select.option', 0, Text::_('PLG_SECURITYCHECKPRO_EMAIL'));
								$options_spam[] = HTMLHelper::_('select.option', 1, Text::_('PLG_SECURITYCHECKPRO_IP'));
								$options_spam[] = HTMLHelper::_('select.option', 2, Text::_('PLG_SECURITYCHECKPRO_USERNAME'));
                                $this->spammer_what_to_check = (array) ($this->spammer_what_to_check ?? ['Email','IP','Username']);                       
                                echo HTMLHelper::_('select.genericlist', $options_spam, 'spammer_what_to_check[]', 'class="form-select" multiple="multiple"', 'text', 'text',  $this->spammer_what_to_check);                                                
                            ?>                    
                        </div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_WHAT_TO_CHECK_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                                                           
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_LIMIT_LABEL'); ?></h4>
                        <div class="controls">
							<input type="number" size="3" maxlength="3" id="spammer_limit" name="spammer_limit" value="<?php echo htmlspecialchars((string) $this->spammer_limit, ENT_QUOTES, 'UTF-8'); ?>" title="" />  
                        </div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAMMER_LIMIT_DESCRIPTION') ?></small></p></blockquote>
                                                    													
						<h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAM_PROTECTION_INCLUDE_URLS_LABEL'); ?></h4>
							<div class="controls">
								<textarea cols="35" rows="3" name="include_urls_spam_protection"><?php echo htmlspecialchars((string) $this->include_urls_spam_protection, ENT_QUOTES, 'UTF-8'); ?></textarea>
							</div>
							<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SPAM_PROTECTION_INCLUDE_URLS_DESCRIPTION') ?></small></p></blockquote>
                       </div>
					</div>
											
					<div class="col-xl-3 mb-3">
                        <div class="card-header text-white bg-primary">
                            <?php echo Text::_('COM_SECURITYCHECKPRO_HONEYPOT_PROTECTION') ?>
                        </div>
						<div class="card-body">                                                                                                    
							<h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_SPAM_PROTECTION_FORMS_LABEL'); ?></h4>
							<div class="controls">
								<textarea name="forms_to_include_honeypot_in"><?php echo htmlspecialchars((string) $this->forms_to_include_honeypot_in, ENT_QUOTES, 'UTF-8'); ?></textarea>
							</div>
							<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('COM_SECURITYCHECKPRO_SPAM_PROTECTION_FORMS_LABEL_DESCRIPTION') ?></small></p></blockquote>
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