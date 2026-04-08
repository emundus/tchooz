<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
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
							<?php echo $basemodel->renderSelect('methods',['GET,POST,REQUEST'=> 'Get,Post,Request'],['class' => 'form-select'],$this->methods,false,true); ?>
						</div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_METHODS_INSPECTED_DESCRIPTION') ?></small></p></blockquote>
					</div>
				</div>                                        
			</div>
        </div> 
    </div>