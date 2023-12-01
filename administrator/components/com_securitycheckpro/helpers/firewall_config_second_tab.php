<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
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
							<?php echo booleanlist('second_level', array(), $this->second_level) ?>
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                        
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_IF_PATTERN_LABEL'); ?></h4>
                        <div class="controls">
							<?php echo secondredirectlist('second_level_redirect', array(), $this->second_level_redirect) ?>
                        </div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_IF_PATTERN_DESCRIPTION') ?></small></p></blockquote>
                                                                                                                        
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_LIMIT_WORDS_LABEL'); ?></h4>
                        <div class="controls">
                            <input type="number" size="2" maxlength="2" id="second_level_limit_words" name="second_level_limit_words" value="<?php echo $this->second_level_limit_words ?>" title="" />
						</div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_LIMIT_WORDS_DESCRIPTION') ?></small></p></blockquote>                        
                    </div>
                </div>                                        
                <div class="col-xl-6 mb-6">
					<div class="card-header text-white bg-primary">
						<?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_WORDS_LABEL') ?>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_WORDS_LABEL'); ?></h4>
						<div class="controls">
							<textarea id="second_level_words" name="second_level_words" class="form-control width_560_height_340"><?php echo $this->second_level_words ?></textarea>
						</div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_WORDS_DESCRIPTION') ?></small></p></blockquote>						
					</div>
				</div>
			</div>
        </div> 
    </div>