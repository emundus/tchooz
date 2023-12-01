<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
?>
	<div class="card mb-6">
        <div class="card-body">
            <div class="row">
                <div class="col-xl-6 mb-6">
                    <div class="card-header text-white bg-primary">
                        <?php echo Text::_('PLG_SECURITYCHECKPRO_MODE_FIELDSET_LABEL') ?>
					</div>
					<div class="card-body">
						<h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_MODE_LABEL'); ?></h4>                                        
						<div class="controls">
							<?php echo mode('mode', array(), $this->mode) ?>
						</div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_MODE_DESCRIPTION') ?></small></p></blockquote>
					</div>
				</div>                                        
			</div>
        </div> 
    </div>