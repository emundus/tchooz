<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

Session::checkToken('get') or die('Invalid Token');

function booleanlist($name, $attribs = null, $selected = null, $id=false)
{
    $arr = array(
		HTMLHelper::_('select.option',  '0', Text::_('COM_SECURITYCHECKPRO_NO')),
		HTMLHelper::_('select.option',  '1', Text::_('COM_SECURITYCHECKPRO_YES'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', (int) $selected, $id);
}
?>


<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=controlcenter&'. Session::getFormToken() .'=1');?>" class="margin-left-10 margin-right-10" method="post" name="adminForm" id="adminForm">

    <?php 
    // Cargamos la navegación
    require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
    ?>                        
           
            
    <?php if (function_exists('openssl_encrypt')) { ?>
	
		<div id="toast" class="col-12 toast align-items-center margin-bottom-10" role="alert" aria-live="assertive" aria-atomic="true">
			<div class="toast-header">			
				<strong id="toast-auto" class="me-auto"></strong>			
				<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
			<div id="toast-body" class="toast-body">			
			</div>
		</div>
            
            <div class="card mb-6">
                <div class="card-body">
                    <div class="row">
                        <div class="alert alert-info">
        <?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_EXPLAIN'); ?>    
                        </div>
        
                        <div class="col-xl-12 mb-12">
                            <div class="card-header text-white bg-primary">
                                <?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS') ?>
                            </div>
                            <div class="card-body">
								<div class="input-group mb-3">
									<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENABLED_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENABLED_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
									<span class="input-group-text" id="control_center_enabled_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENABLED_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENABLED_TEXT'); ?></span>
									<?php echo booleanlist('control_center_enabled', array(), $this->control_center_enabled) ?>     											
								</div>
								
								<div class="input-group mb-3">
									<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_SECRET_KEY_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_GENERATE_KEY_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
									<span class="input-group-text" id="generate_key_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_SECRET_KEY_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_GENERATE_KEY_TEXT'); ?></span>
									<input class="form-control" type="text" name="secret_key" id="secret_key" value="<?php echo $this->secret_key ?>" readonly>
									<button class="btn btn-outline-secondary" type="button" onclick='document.getElementById("secret_key").value = Password.generate(32)'><?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_GENERATE_KEY_TEXT') ?></button>     											
								</div>
								
								<div class="input-group mb-3">
									<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
									<span class="input-group-text" id="conrol_center_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL'); ?></span>
									<input class="form-control" type="text" name="control_center_url" id="control_center_url" value="<?php echo $this->control_center_url ?>" placeholder="<?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL_PLACEHOLDER') ?>">									     											
								</div>					
								
								<?php	 
									$mainframe = Factory::getApplication();
									$cc_status = $mainframe->getUserState('download_controlcenter_log', null);									
									if ( (!empty($cc_status)) || ($this->error_file_exists == 1) ) { 							
								?>
								<div id="button_show_log" class="card-footer">
									<h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_CONFIG_FILE_MANAGER_LOG_PATH_LABEL'); ?></h4>
									<blockquote><p class="text-info"><small><?php echo Text::_('COM_SECURITYCHECKPRO_LOG_FILE_EXPLAIN') ?></small></p></blockquote>
									<?php
									 if (!empty($cc_status)) {
									?>
									<button class="btn btn-success" type="button" onclick="Joomla.submitbutton('download_controlcenter_log');"><i class="fa fa-download"></i><?php echo Text::_('COM_SECURITYCHECKPRO_DOWNLOAD_LOG'); ?></button>
									<?php
									}
									if ($this->error_file_exists == 1) {
									?>
									<button class="btn btn-danger" type="button" onclick="add_element_to_form('error_log','1'); Joomla.submitbutton('download_controlcenter_log');"><i class="fa fa-download"></i><?php echo Text::_('COM_SECURITYCHECKPRO_DOWNLOAD_ERROR_LOG'); ?></button>
									<?php
									 }
									 ?>
									<button class="btn btn-warning" type="button" onclick="Joomla.submitbutton('delete_controlcenter_log');"><i class="fa fa-trash"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CONFIG_FILE_MANAGER_DELETE_LOG_FILE_LABEL'); ?></button>
								</div>  
								<?php }    ?>        							  
								
                            </div>
                        </div>
                    </div>                    
                </div>
            </div>
    <?php } else { ?>
                <div class="alert alert-error">
        <?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENCRYPT_LIBRARY_NOT_PRESENT'); ?>    
                </div>

    <?php } ?>
        </div>
</div>

<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="view" value="controlcenter" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="task" id="task" value="save" />
<input type="hidden" name="controller" value="controlcenter" />    
</form>