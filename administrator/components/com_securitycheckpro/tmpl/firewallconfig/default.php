<?php 
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

Session::checkToken('get') or die('Invalid Token');

// Load plugin language
$lang2 = Factory::getLanguage();
$lang2->load('plg_system_securitycheckpro');

$site_url = Uri::root();

function booleanlist( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  '0', Text::_('COM_SECURITYCHECKPRO_NO')),
    HTMLHelper::_('select.option',  '1', Text::_('COM_SECURITYCHECKPRO_YES'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', (int) $selected, $id);
}

function prioritylist( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  'Blacklist', Text::_('PLG_SECURITYCHECKPRO_BLACKLIST')),
    HTMLHelper::_('select.option',  'Whitelist', Text::_('PLG_SECURITYCHECKPRO_WHITELIST')),
    HTMLHelper::_('select.option',  'DynamicBlacklist', Text::_('PLG_SECURITYCHECKPRO_DYNAMICBLACKLIST'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', $selected, $id);
}

function methodslist( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  'GET,POST,REQUEST', 'Get,Post,Request'),

    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', $selected, $id);
}

function mode( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  '0', Text::_('PLG_SECURITYCHECKPRO_ALERT_MODE')),
    HTMLHelper::_('select.option',  '1', Text::_('PLG_SECURITYCHECKPRO_STRICT_MODE'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', (int) $selected, $id);
}

function redirectionlist( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  '1', Text::_('PLG_SECURITYCHECKPRO_JOOMLA_PATH_LABEL')),
    HTMLHelper::_('select.option',  '2', Text::_('COM_SECURITYCHECKPRO_REDIRECTION_OWN_PAGE'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select" onchange="Disable()"', 'value', 'text', (int) $selected, $id);
}

function secondredirectlist( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  '1', Text::_('COM_SECURITYCHECKPRO_YES'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name,  'class="form-select"', 'value', 'text', (int) $selected, $id);
}

function booleanlist_js( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  '0', Text::_('COM_SECURITYCHECKPRO_NO')),
    HTMLHelper::_('select.option',  '1', Text::_('COM_SECURITYCHECKPRO_YES'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select" onchange="Disable()"', 'value', 'text', (int) $selected, $id);
}

function email_actions( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  '0', Text::_('COM_SECURITYCHECKPRO_EMAIL_BOTH_INCORRECT')),
    HTMLHelper::_('select.option',  '1', Text::_('COM_SECURITYCHECKPRO_EMAIL_ONLY_FRONTEND')),
    HTMLHelper::_('select.option',  '2', Text::_('COM_SECURITYCHECKPRO_EMAIL_ONLY_BACKEND'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', (int) $selected, $id);
}

function actions_failed_login( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  '0', Text::_('COM_SECURITYCHECKPRO_DO_NOTHING')),
    HTMLHelper::_('select.option',  '1', Text::_('COM_SECURITYCHECKPRO_ADD_IP_TO_DYNAMIC_BLACKLIST'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', (int) $selected, $id);
}

function actions( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  '0', Text::_('COM_SECURITYCHECKPRO_DO_NOTHING')),
    HTMLHelper::_('select.option',  '1', Text::_('COM_SECURITYCHECKPRO_ADD_IP_TO_DYNAMIC_BLACKLIST'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', (int) $selected, $id);
}

function spammer_action( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  '0', Text::_('COM_SECURITYCHECKPRO_DO_NOTHING')),
    HTMLHelper::_('select.option',  '1', Text::_('COM_SECURITYCHECKPRO_ADD_IP_TO_DYNAMIC_BLACKLIST'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', (int) $selected, $id);
}

function action( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  '0', Text::_('COM_SECURITYCHECKPRO_DO_NOTHING')),
    HTMLHelper::_('select.option',  '1', Text::_('COM_SECURITYCHECKPRO_ADD_IP_TO_DYNAMIC_BLACKLIST')),
    HTMLHelper::_('select.option',  '2', Text::_('COM_SECURITYCHECKPRO_ADD_IP_TO_BLACKLIST'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', (int) $selected, $id);
}

function what_to_check( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
    HTMLHelper::_('select.option',  '1', Text::sprintf('PLG_SECURITYCHECKPRO_IP_USER_AGENT', "OR")),
    HTMLHelper::_('select.option',  '2', Text::sprintf('PLG_SECURITYCHECKPRO_IP_USER_AGENT', "AND"))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', (int) $selected, $id);
}   
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=firewallconfig&'. Session::getFormToken() .'=1');?>" class="margin-left-10 margin-right-10" enctype="multipart/form-data" method="post" name="adminForm" id="adminForm">       
        
    <?php 
    // Cargamos la navegación
    require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
    ?>
                        
          
            <div class="card mb-3">
                <div class="card-body">
					<?php echo HTMLHelper::_('bootstrap.startTabSet', 'WafConfigurationTabs'); ?>
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_lists_tab', Text::_('PLG_SECURITYCHECKPRO_LISTS_LABEL')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_list_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_methods_tab', Text::_('PLG_SECURITYCHECKPRO_METHODS_INSPECTED_LABEL')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_methods_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_mode_tab', Text::_('PLG_SECURITYCHECKPRO_MODE_FIELDSET_LABEL')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_mode_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_logs_tab', Text::_('PLG_SECURITYCHECKPRO_LOGS_LABEL')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_logs_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_redirection_tab', Text::_('PLG_SECURITYCHECKPRO_REDIRECTION_LABEL')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_redirection_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_second_tab', Text::_('PLG_SECURITYCHECKPRO_SECOND_LABEL')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_second_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_email_notifications_tab', Text::_('PLG_SECURITYCHECKPRO_EMAIL_NOTIFICATIONS_LABEL')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_notification_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_exceptions_tab', Text::_('PLG_SECURITYCHECKPRO_EXCEPTIONS_LABEL')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_exceptions_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_session_protection_tab', Text::_('PLG_SECURITYCHECKPRO_SESSION_PROTECTION_LABEL')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_session_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_upload_scanner_tab', Text::_('COM_SECURITYCHECKPRO_UPLOADSCANNER_LABEL')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_upload_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_spam_protection_tab', Text::_('COM_SECURITYCHECKPRO_SPAM_PROTECTION')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_spam_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_url_inspector_tab', Text::_('COM_SECURITYCHECKPRO_CPANEL_URL_INSPECTOR_TEXT')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_url_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
						
						<?php echo HTMLHelper::_('bootstrap.addTab', 'WafConfigurationTabs', 'li_track_actions_tab', Text::_('COM_SECURITYCHECKPRO_TRACK_ACTIONS')); ?>
							<?php include JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'firewall_config_track_tab.php'; ?>
						<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
					
					<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?> 
					
				</div>
            </div>
        <!-- End container fluid -->        

<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="controller" value="firewallconfig" />
</form>
