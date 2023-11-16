<?php 
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

Session::checkToken('get') or die('Invalid Token');

function booleanlist( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
		HTMLHelper::_('select.option',  '0', Text::_('COM_SECURITYCHECKPRO_NO')),
		HTMLHelper::_('select.option',  '1', Text::_('COM_SECURITYCHECKPRO_YES'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', (int) $selected, $id);
}

function xframeoptions( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
		HTMLHelper::_('select.option',  'NO', Text::_('COM_SECURITYCHECKPRO_NO')),
		HTMLHelper::_('select.option',  'DENY', Text::_('COM_SECURITYCHECKPRO_XFRAME_OPTIONS_DENY')),
		HTMLHelper::_('select.option',  'SAMEORIGIN', Text::_('COM_SECURITYCHECKPRO_XFRAME_OPTIONS_SAMEORIGIN'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', $selected, $id);
}

// Cargamos los archivos javascript necesarios
$document = Factory::getDocument();

// styles ('data-xxx' for J3 and 'data-bs-xxxx' for J4)
$data_dismiss = "data-dismiss";

if (version_compare(JVERSION, '4.0', 'ge')) {	
	$data_dismiss = "data-bs-dismiss";
}

$document->addScript(Uri::root().'media/com_securitycheckpro/new/js/sweetalert.min.js');

// Add style declaration
$media_url = "media/com_securitycheckpro/stylesheets/cpanelui.css";
HTMLHelper::stylesheet($media_url);

$site_url = Uri::base();

$sweet = "media/com_securitycheckpro/stylesheets/sweetalert.css";
HTMLHelper::stylesheet($sweet);

?>

<?php 
// Cargamos el contenido común...
require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/common.php';

// ... y el contenido específico
require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/protection.php';

echo '<script type="module" src="' . URI::root() . 'media/vendor/bootstrap/js/tab.min.js"></script>';
echo '<script type="module" src="' . URI::root() . 'media/vendor/bootstrap/js/toast.min.js"></script>';
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=protection&view=protection&'. Session::getFormToken() .'=1');?>" method="post" name="adminForm" id="adminForm" class="margin-left-10 margin-right-10">

<?php 
        
        // Cargamos la navegación
        require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
?>
        
    <?php
    if (($this->server == 'apache') || ($this->server == 'iis') ) {
        ?>
            <div class="alert alert-warning">
        <?php echo Text::_('COM_SECURITYCHECKPRO_USER_AGENT_INTRO'); ?>
            </div>
            <div class="alert alert-danger">
        <?php echo Text::_('COM_SECURITYCHECKPRO_USER_AGENT_WARN'); ?>    
            </div>
            <div class="alert alert-info">
        <?php if($this->ExistsHtaccess) { 
            echo Text::_('COM_SECURITYCHECKPRO_USER_AGENT_HTACCESS');
        } else { 
            echo Text::_('COM_SECURITYCHECKPRO_USER_AGENT_NO_HTACCESS');
        } ?>
            </div>
        <?php
    } else if ($this->server == 'nginx') {
        ?>
            <div class="alert alert-danger">
        <?php echo Text::_('COM_SECURITYCHECKPRO_NGINX_SERVER'); ?>    
            </div>
        <?php
    }
    ?>
		<div id="toast" class="col-12 toast align-items-center margin-bottom-10" role="alert" aria-live="assertive" aria-atomic="true">
		  <div class="toast-header">			
			<strong id="toast-auto" class="me-auto"></strong>			
			<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
		  </div>
		  <div id="toast-body" class="toast-body">			
		  </div>
		</div>
	
		<!-- Contenido principal -->
        <div class="card mb-3">
            <div class="card-body">
                        
            <div class="overflow-x-auto">    
                <ul class="nav nav-tabs" role="tablist" id="protectionTab">
                    <li class="nav-item" id="li_autoprotection_tab" >
                        <a class="nav-link active" href="#autoprotection" data-bs-toggle="tab" role="tab"><?php echo Text::_('COM_SECURITYCHECKPRO_PROTECTION_AUTOPROTECTION_TEXT'); ?></a>
                    </li>
                    <li class="nav-item" id="li_headers_protection_tab">
                        <a class="nav-link" href="#headers_protection" data-bs-toggle="tab" role="tab"><?php echo Text::_('COM_SECURITYCHECKPRO_HTTP_HEADERS_PROTECTION_TEXT'); ?></a>
                    </li>
                    <li class="nav-item" id="li_user_agents_protection_tab">
                        <a class="nav-link" href="#user_agents_protection" data-bs-toggle="tab" role="tab"><?php echo Text::_('COM_SECURITYCHECKPRO_PROTECTION_USER_AGENTS_TEXT'); ?></a>
                    </li>
                    <li class="nav-item" id="li_fingerprinting_tab">
                        <a class="nav-link" href="#fingerprinting" data-bs-toggle="tab" role="tab"><?php echo Text::_('COM_SECURITYCHECKPRO_FINGERPRINTING_PROTECTION_TEXT'); ?></a>
                    </li>
                    <li class="nav-item" id="li_backend_protection_tab">
                        <a class="nav-link" href="#backend_protection" data-bs-toggle="tab" role="tab"><?php echo Text::_('COM_SECURITYCHECKPRO_BACKEND_PROTECTION_TEXT'); ?></a>
                    </li>
                    <li class="nav-item" id="li_performance_tab_tab">
                        <a class="nav-link" href="#performance_tab" data-bs-toggle="tab" role="tab"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_PERFORMANCE'); ?></a>
                    </li>                    
                </ul>
                
                <div class="tab-content margin-top-10" class="overflow-auto">
                    <div class="tab-pane show active" id="autoprotection" role="tabpanel">		
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_PREVENT_ACCESS_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_PREVENT_ACCESS_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="prevent_access_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_PREVENT_ACCESS_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_PREVENT_ACCESS_TEXT'); ?></span>
							<?php echo booleanlist('prevent_access', array(), $this->protection_config['prevent_access']) ?>                                
							<?php if ($this->config_applied['prevent_access']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>

						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_PREVENT_UNAUTHORIZED_BROWSING_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_PREVENT_UNAUTHORIZED_BROWSING_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="own_banned_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_PREVENT_UNAUTHORIZED_BROWSING_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_PREVENT_UNAUTHORIZED_BROWSING_TEXT'); ?></span>
							<?php echo booleanlist('prevent_unauthorized_browsing', array(), $this->protection_config['prevent_unauthorized_browsing']) ?>                                
							<?php if ($this->config_applied['prevent_unauthorized_browsing']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>	
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_FILE_INJECTION_PROTECTION_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_FILE_INJECTION_PROTECTION_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="file_injection_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_FILE_INJECTION_PROTECTION_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_FILE_INJECTION_PROTECTION_TEXT'); ?></span>
							<?php echo booleanlist('file_injection_protection', array(), $this->protection_config['file_injection_protection']) ?>                                
							<?php if ($this->config_applied['file_injection_protection']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>	
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_SELF_ENVIRON_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_SELF_ENVIRON_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="self_environ_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_SELF_ENVIRON_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_SELF_ENVIRON_TEXT'); ?></span>
							<?php echo booleanlist('self_environ', array(), $this->protection_config['self_environ']) ?>                                
							<?php if ($this->config_applied['self_environ']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>						
                    <!-- autoprotection tab end -->
                    </div>
                    
                    <div class="tab-pane" id="headers_protection" role="tabpanel">
                        <div class="alert alert-danger">
							<?php echo Text::_('COM_SECURITYCHECKPRO_HTTP_HEADERS_EXPLAIN'); ?>    
                        </div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_XFRAME_OPTIONS_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_XFRAME_OPTIONS_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="xframe_options_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_XFRAME_OPTIONS_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_XFRAME_OPTIONS_TEXT'); ?></span>
							 <?php echo xframeoptions('xframe_options', array(), $this->protection_config['xframe_options']) ?>                         
							<?php if ($this->config_applied['xframe_options']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_PREVENT_MIME_ATTACKS_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_PREVENT_MIME_ATTACKS_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="prevent_mime_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_PREVENT_MIME_ATTACKS_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_PREVENT_MIME_ATTACKS_TEXT'); ?></span>
							<?php echo booleanlist('prevent_mime_attacks', array(), $this->protection_config['prevent_mime_attacks']) ?>           
							<?php if ($this->config_applied['prevent_mime_attacks']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_STS_OPTIONS_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_STS_OPTIONS_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="sts_options_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_STS_OPTIONS_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_STS_OPTIONS_TEXT'); ?></span>
							<?php echo booleanlist('sts_options', array(), $this->protection_config['sts_options']) ?>           
							<?php if ($this->config_applied['sts_options']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_XSS_OPTIONS_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_XSS_OPTIONS_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="xss_options_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_XSS_OPTIONS_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_XSS_OPTIONS_TEXT'); ?></span>
							<?php echo booleanlist('xss_options', array(), $this->protection_config['xss_options']) ?>           
							<?php if ($this->config_applied['xss_options']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_CSP_OPTIONS_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_CSP_OPTIONS_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="csp_policy_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_CSP_OPTIONS_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_CSP_OPTIONS_TEXT'); ?></span>
							<input type="text" class="form-control width_560" id="csp_policy" name="csp_policy" aria-describedby="csp_policy" placeholder="<?php echo Text::_('COM_SECURITYCHECKPRO_ENTER_POLICY') ?>" value="<?php echo htmlentities($this->protection_config['csp_policy']); ?>">         
							<?php if ($this->config_applied['csp_policy']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_REFERRER_POLICY_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_REFERRER_POLICY_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="referrer_policy_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_REFERRER_POLICY_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_REFERRER_POLICY_TEXT'); ?></span>
							<input type="text" class="form-control width_560" id="referrer_policy" name="referrer_policy" aria-describedby="referrer_policy" placeholder="<?php echo Text::_('COM_SECURITYCHECKPRO_ENTER_POLICY') ?>" value="<?php echo htmlentities($this->protection_config['referrer_policy']); ?>"> 
							<?php if ($this->config_applied['referrer_policy']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_PERMISSIONS_POLICY_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_PERMISSIONS_POLICY_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="permissions_policy_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_PERMISSIONS_POLICY_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_PERMISSIONS_POLICY_TEXT'); ?></span>
							 <input type="text" class="form-control width_560" id="permissions_policy" name="permissions_policy" aria-describedby="permissions_policy" placeholder="<?php echo Text::_('COM_SECURITYCHECKPRO_ENTER_POLICY') ?>" value="<?php echo htmlentities($this->protection_config['permissions_policy']); ?>">        
							<?php if ($this->config_applied['permissions_policy']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>					
                    <!-- headers_protection tab end -->
                    </div>
                        
                    <div class="tab-pane" id="user_agents_protection" role="tabpanel">
                    
                        <!-- View default user agent list -->
						<?php $default = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'user_agent_blacklist.inc'); ?>
                        <div class="modal" id="div_default_user_agents" tabindex="-1" role="dialog" aria-labelledby="defaultuseragentsLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header alert alert-info">
                                        <h2 class="modal-title" id="defaultuseragentsLabel"><?php echo Text::_('COM_SECURITYCHECKPRO_FILE_CONTENT'); ?></h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body margin-left-10" class="overflow-y-scroll">
                                        <div class="color_rojo">
											<?php echo Text::_('COM_SECURITYCHECKPRO_WARNING_CHANGES_USER_AGENTS'); ?>
                                        </div>
                                        <br/>
                                        <textarea class="form-control" id="file_info" name="file_info" style="height: 200px"><?php echo $default; ?></textarea>                                
                                    </div>
                                    <div class="modal-footer">                    
                                        <input class="btn btn-success" id="save_default_user_agent_button" type="button" id="boton_guardar" value="<?php echo Text::_('COM_SECURITYCHECKPRO_SAVE_CLOSE'); ?>" />
                                        <button type="button" class="btn btn-default" <?php echo $data_dismiss; ?>="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
                                    </div>              
                                </div>
                            </div>
                        </div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_DEFAULT_BANNED_LIST_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_DEFAULT_BANNED_LIST_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<input class="btn btn-warning" type="button" id="boton_default_user_agent" value="<?php echo JText::_('COM_SECURITYCHECKPRO_EDIT_DEFAULT_USER_AGENTS'); ?>" />
							<span class="input-group-text" id="default_banned_list_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_DEFAULT_BANNED_LIST_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_DEFAULT_BANNED_LIST_TEXT'); ?></span>
							<?php echo booleanlist('default_banned_list', array(), $this->protection_config['default_banned_list']) ?>           
							<?php if ($this->config_applied['default_banned_list']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_OWN_BANNED_LIST_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_OWN_BANNED_LIST_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="own_banned_list_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_OWN_BANNED_LIST_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_OWN_BANNED_LIST_TEXT'); ?></span>
							<textarea class="form-control" name="own_banned_list" id="own_banned_list"><?php echo $this->protection_config['own_banned_list'] ?></textarea>           
							<?php if ($this->config_applied['own_banned_list']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_OWN_CODE_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_OWN_CODE_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="own_code_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_OWN_CODE_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_OWN_CODE_TEXT'); ?></span>
							<textarea class="form-control" name="own_code" id="own_code"><?php echo $this->protection_config['own_code'] ?></textarea>           
							<?php if ($this->config_applied['own_code']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>						
                    <!-- headers_protection tab end -->
                    </div>                        
        
                    <div class="tab-pane" id="fingerprinting" role="tabpanel">
                        <div class="alert alert-danger">
							<?php echo Text::_('COM_SECURITYCHECKPRO_FINGERPRINTING_EXPLAIN'); ?>    
                        </div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_DISABLE_SERVER_SIGNATURE_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_DISABLE_SERVER_SIGNATURE_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="disable_server_signature_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_DISABLE_SERVER_SIGNATURE_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_DISABLE_SERVER_SIGNATURE_TEXT'); ?></span>
							<?php echo booleanlist('disable_server_signature', array(), $this->protection_config['disable_server_signature']) ?>                                
							<?php if ($this->config_applied['disable_server_signature']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_DISALLOW_PHP_EGGS_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_DISALLOW_PHP_EGGS_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="disallow_php_eggs_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_DISALLOW_PHP_EGGS_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_DISALLOW_PHP_EGGS_TEXT'); ?></span>
							<?php echo booleanlist('disallow_php_eggs', array(), $this->protection_config['disallow_php_eggs']) ?>                                
							<?php if ($this->config_applied['disallow_php_eggs']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
						
						<div class="input-group mb-3">
							 <?php if (empty($this->protection_config['disallow_sensible_files_access']) ) {
								$this->protection_config['disallow_sensible_files_access'] = "htaccess.txt" . PHP_EOL . "configuration.php(-dist)?" . PHP_EOL . "joomla.xml" . PHP_EOL . "README.txt" . PHP_EOL . "web.config.txt" . PHP_EOL . "CONTRIBUTING.md" . PHP_EOL . "phpunit.xml.dist" . PHP_EOL . "plugin_googlemap2_proxy.php";
							}?>
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_DISALLOW_SENSIBLE_FILES_ACCESS_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_DISALLOW_SENSIBLE_FILES_ACCESS_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="disallow_sensible_files_access_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_DISALLOW_SENSIBLE_FILES_ACCESS_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_DISALLOW_SENSIBLE_FILES_ACCESS_TEXT'); ?></span>
							 <textarea class="form-control" name="disallow_sensible_files_access" id="disallow_sensible_files_access"><?php echo $this->protection_config['disallow_sensible_files_access'] ?></textarea>
							<?php if ($this->config_applied['disallow_sensible_files_access']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>						
                    <!-- fingerprinting tab end -->
                    </div>
                                        
                    <div class="tab-pane" id="backend_protection" role="tabpanel">
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_FEATURE_APPLIED_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_FEATURE_APPLIED_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="hide_backend_url_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_FEATURE_APPLIED_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_FEATURE_APPLIED_TEXT'); ?></span>
							 <input class="mt-0" id="backend_protection_applied" name="backend_protection_applied" type="checkbox" onchange="hideIt();" <?php if ($this->protection_config['backend_protection_applied']) { ?> checked <?php } ?> />												
						</div>
						
						
						
                        <div id="menu_hide_backend_1" class="alert alert-danger">
							<?php echo Text::_('COM_SECURITYCHECKPRO_BACKEND_PROTECTION_EXPLAIN'); ?>    
                        </div>
                        
                        <div id="menu_hide_backend_2" class="control-group">
							<div class="input-group mb-3">
								<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_URL_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_URL_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
								<span class="input-group-text" id="hide_backend_url_redirection_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_URL_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_URL_TEXT'); ?></span>
								<span class="input-group-text background-FFBF60" id="inputGroup-sizing-lg"><?php echo $site_url ?>?</span>
								<input type="text" class="form-control" aria-label="Large" aria-describedby="inputGroup-sizing-sm" name="hide_backend_url" id="hide_backend_url" value="<?php echo $this->protection_config['hide_backend_url']?>" placeholder="<?php echo $this->protection_config['hide_backend_url'] ?>"> 
								<input type='button' id="hide_backend_url_button" class="btn btn-primary" class="margin-left-10" value='<?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_GENERATE_KEY_TEXT') ?>' />
								<?php if ($this->config_applied['hide_backend_url']) {?>
									<span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
								<?php } ?>
							</div>						
                        </div>
                                               
                        <div id="menu_hide_backend_3" class="control-group">
							<div class="input-group mb-3">
								<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_URL_REDIRECTION_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_URL_REDIRECTION_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
								<span class="input-group-text" id="hide_backend_url_redirection_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_URL_REDIRECTION_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_URL_REDIRECTION_TEXT'); ?></span>
								<span class="input-group-text background-D0F5A9" id="inputGroup-sizing-lg"><?php echo "/" ?></span>
								<input type="text" class="form-control" aria-label="Large" aria-describedby="inputGroup-sizing-sm" name="hide_backend_url_redirection" id="hide_backend_url_redirection" value="<?php echo $this->protection_config['hide_backend_url_redirection']?>" placeholder="not_found">
								<?php if ($this->config_applied['hide_backend_url_redirection']) {?>
									<span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
								<?php } ?>
							</div>
						</div>                        
                        
                        <div id="menu_hide_backend_4" class="control-group">
							<div class="input-group mb-3">
								<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_ADD_EXCEPTION_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_EXCEPTIONS') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
								<span class="input-group-text" id="add_exception_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_ADD_EXCEPTION_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_EXCEPTIONS'); ?></span>
								<textarea readonly class="form-control" name="backend_exceptions" id="backend_exceptions"><?php echo $this->protection_config['backend_exceptions'] ?></textarea>
								<input type="text" class="form-control span8" aria-label="exception" name="exception" id="exception" placeholder="<?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_YOUR_EXCEPTION_HERE') ?>">
								<button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo Text::_('COM_SECURITYCHECKPRO_ACTIONS') ?></button>
								<ul class="dropdown-menu dropdown-menu-end">
									<a class="dropdown-item" id="add_exception_button" href="#backend_exceptions"><?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_ADD_EXCEPTION_TEXT') ?></a>
                                    <a class="dropdown-item" id="delete_exception_button" href="#backend_exceptions"><?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_DELETE_EXCEPTION_TEXT') ?></a>
                                    <a class="dropdown-item" id="delete_all_button" href="#backend_exceptions"><?php echo Text::_('COM_SECURITYCHECKPRO_DELETE_ALL') ?></a>          
								</ul>                                               
								<?php if ($this->config_applied['backend_exceptions']) {?>
									<span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
								<?php } ?>
							</div>							
                        </div>                       
                    <!-- backend_protection tab end -->
                    </div>
                    
                    <div class="tab-pane" id="performance_tab" role="tabpanel">
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_OPTIMAL_EXPIRATION_TIME_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_OPTIMAL_EXPIRATION_TIME_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="optimal_expiration_time_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_OPTIMAL_EXPIRATION_TIME_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_OPTIMAL_EXPIRATION_TIME_TEXT'); ?></span>
							<?php echo booleanlist('optimal_expiration_time', array(), $this->protection_config['optimal_expiration_time']) ?>                                
							<?php if ($this->config_applied['optimal_expiration_time']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
						
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_COMPRESS_CONTENT_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_COMPRESS_CONTENT_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="compress_content_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_COMPRESS_CONTENT_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_COMPRESS_CONTENT_TEXT'); ?></span>
							<?php echo booleanlist('compress_content', array(), $this->protection_config['compress_content']) ?>                                
							<?php if ($this->config_applied['compress_content']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
					
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECT_TO_WWW_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECT_TO_WWW_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="redirect_to_www_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECT_TO_WWW_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECT_TO_WWW_TEXT'); ?></span>
							<?php echo booleanlist('redirect_to_www', array(), $this->protection_config['redirect_to_www']) ?>                                
							<?php if ($this->config_applied['redirect_to_www']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>
					
						<div class="input-group mb-3">
							<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECT_TO_NON_WWW_EXPLAIN') ?>','<?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECT_TO_NON_WWW_TEXT') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
							<span class="input-group-text" id="redirect_to_non_www_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECT_TO_NON_WWW_EXPLAIN') ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECT_TO_NON_WWW_TEXT'); ?></span>
							<?php echo booleanlist('redirect_to_non_www', array(), $this->protection_config['redirect_to_non_www']) ?>                                
							<?php if ($this->config_applied['redirect_to_non_www']) {?>
                                <span class="input-group-text text-white bg-success" title="<?php echo Text::_('COM_SECURITYCHECKPRO_APPLIED') ?>"><i class="fapro fa-check"></i></span>		
                            <?php } ?>							
						</div>					
                    <!-- performance tab end -->
                    </div>
                <!-- tab-content margin-top-10 end -->
                </div>                
            </div>            
			</div>
			</div>
		</div>
</div>        

<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="task" id="task" value="save" />
</form>