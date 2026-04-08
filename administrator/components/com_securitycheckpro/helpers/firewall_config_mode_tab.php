<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
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
							<?php echo $basemodel->renderSelect('mode',[['value'=>'0','text'=>'PLG_SECURITYCHECKPRO_ALERT_MODE'],['value'=>'1','text'=>'PLG_SECURITYCHECKPRO_STRICT_MODE']],['class' => 'form-select'],$this->mode,false,true); ?>
						</div>
						<blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_MODE_DESCRIPTION') ?></small></p></blockquote>
					</div>
				</div>                                        
			</div>
        </div> 
    </div>