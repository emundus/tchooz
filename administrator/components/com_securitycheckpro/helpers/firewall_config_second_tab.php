<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
?>
<!-- Second -->
	<div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-xl-4 mb-4">
                    <div class="card-header text-white bg-primary">
						<?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECTION_LABEL') ?>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_LABEL'); ?></h4>
                        <div class="controls">
							<?php echo $basemodel->renderSelect('second_level','boolean',['class' => 'form-select'], $this->second_level,false); ?>
                        </div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                        
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_IF_PATTERN_LABEL'); ?></h4>
                        <div class="controls">
							<?php echo $basemodel->renderSelect('second_level_redirect',['1'=> 'COM_SECURITYCHECKPRO_YES'],['class' => 'form-select'],$this->second_level_redirect,false,true); ?>
                        </div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_IF_PATTERN_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                        
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_LIMIT_WORDS_LABEL'); ?></h4>
                        <div class="controls">
                            <input type="number" size="2" maxlength="2" id="second_level_limit_words" name="second_level_limit_words" value="<?php echo htmlspecialchars((string) $this->second_level_limit_words, ENT_QUOTES, 'UTF-8'); ?>" title="" />
						</div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_LIMIT_WORDS_DESCRIPTION') ?></small></p></blockquote>                        
                    </div>
                </div>                                        
                <div class="col-xl-6 mb-6">
					<div class="card-header text-white bg-primary">
						<?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_WORDS_LABEL') ?>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_WORDS_LABEL'); ?></h4>
						<div class="controls">
							<textarea id="second_level_words" name="second_level_words" class="form-control width_560_height_340"><?php echo htmlspecialchars((string) $this->second_level_words, ENT_QUOTES, 'UTF-8'); ?></textarea>
						</div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_WORDS_DESCRIPTION') ?></small></p></blockquote>						
					</div>
				</div>
			</div>
        </div> 
    </div>