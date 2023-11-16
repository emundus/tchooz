<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
?>
<!-- Methods -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="row">
                <div class="col-xl-6 mb-6">
                    <div class="card-header text-white bg-primary">
						<?php echo Text::_('PLG_SECURITYCHECKPRO_METHODS_INSPECTED_LABEL') ?>
					</div>
					<div class="card-body">
						<h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_METHODS_LABEL'); ?></h4>
						<div class="controls">
							<?php echo methodslist('methods', array(), $this->methods); ?>
						</div>
						<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_METHODS_INSPECTED_DESCRIPTION') ?></small></p></blockquote>
					</div>
				</div>                                        
			</div>
        </div> 
    </div>