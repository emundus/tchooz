<?php 
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;

Session::checkToken('get') or die('Invalid Token');

// Load plugin language
$lang = Factory::getLanguage();
$lang->load('plg_system_securitycheckpro_cron');


function taskslist( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
		HTMLHelper::_('select.option',  'permissions', Text::_('PLG_SECURITYCHECKPRO_CRON_ONLY_PERMISSIONS')),
		HTMLHelper::_('select.option',  'integrity', Text::_('PLG_SECURITYCHECKPRO_CRON_ONLY_INTEGRITY')),
		HTMLHelper::_('select.option',  'both', Text::_('PLG_SECURITYCHECKPRO_CRON_BOTH_TASKS')),
		HTMLHelper::_('select.option',  'alternate', Text::_('PLG_SECURITYCHECKPRO_CRON_ALTERNATE_TASKS'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', $selected, $id);    
}

function launchtimelist( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
		HTMLHelper::_('select.option',  '0', Text::_('00:00 - 01:00')),
		HTMLHelper::_('select.option',  '1', Text::_('01:00 - 02:00')),
		HTMLHelper::_('select.option',  '2', Text::_('02:00 - 03:00')),
		HTMLHelper::_('select.option',  '3', Text::_('03:00 - 04:00')),
		HTMLHelper::_('select.option',  '4', Text::_('04:00 - 05:00')),
		HTMLHelper::_('select.option',  '5', Text::_('05:00 - 06:00')),
		HTMLHelper::_('select.option',  '6', Text::_('06:00 - 07:00')),
		HTMLHelper::_('select.option',  '7', Text::_('07:00 - 08:00')),
		HTMLHelper::_('select.option',  '8', Text::_('08:00 - 09:00')),
		HTMLHelper::_('select.option',  '9', Text::_('09:00 - 10:00')),
		HTMLHelper::_('select.option',  '10', Text::_('10:00 - 11:00')),
		HTMLHelper::_('select.option',  '11', Text::_('11:00 - 12:00')),
		HTMLHelper::_('select.option',  '12', Text::_('12:00 - 13:00')),
		HTMLHelper::_('select.option',  '13', Text::_('13:00 - 14:00')),
		HTMLHelper::_('select.option',  '14', Text::_('14:00 - 15:00')),
		HTMLHelper::_('select.option',  '15', Text::_('15:00 - 16:00')),
		HTMLHelper::_('select.option',  '16', Text::_('16:00 - 17:00')),
		HTMLHelper::_('select.option',  '17', Text::_('17:00 - 18:00')),
		HTMLHelper::_('select.option',  '18', Text::_('18:00 - 19:00')),
		HTMLHelper::_('select.option',  '19', Text::_('19:00 - 20:00')),
		HTMLHelper::_('select.option',  '20', Text::_('20:00 - 21:00')),
		HTMLHelper::_('select.option',  '21', Text::_('21:00 - 22:00')),
		HTMLHelper::_('select.option',  '22', Text::_('22:00 - 23:00')),
		HTMLHelper::_('select.option',  '23', Text::_('23:00 - 00:00'))        
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select"', 'value', 'text', (int) $selected, $id);
}

function periodicitylist( $name, $attribs = null, $selected = null, $id=false )
{
    $arr = array(
		HTMLHelper::_('select.option',  '1', Text::sprintf('PLG_SECURITYCHECKPRO_CRON_EVERY_X_HOUR', 1)),
		HTMLHelper::_('select.option',  '2', Text::sprintf('PLG_SECURITYCHECKPRO_CRON_EVERY_X_HOUR', 2)),
		HTMLHelper::_('select.option',  '4', Text::sprintf('PLG_SECURITYCHECKPRO_CRON_EVERY_X_HOUR', 4)),
		HTMLHelper::_('select.option',  '6', Text::sprintf('PLG_SECURITYCHECKPRO_CRON_EVERY_X_HOUR', 6)),
		HTMLHelper::_('select.option',  '8', Text::sprintf('PLG_SECURITYCHECKPRO_CRON_EVERY_X_HOUR', 8)),
		HTMLHelper::_('select.option',  '12', Text::sprintf('PLG_SECURITYCHECKPRO_CRON_EVERY_X_HOUR', 12)),
		HTMLHelper::_('select.option',  '24', Text::_('PLG_SECURITYCHECKPRO_CRON_EVERY_DAY')),
		HTMLHelper::_('select.option',  '168', Text::_('PLG_SECURITYCHECKPRO_CRON_EVERY_WEEK'))
    );
    return HTMLHelper::_('select.genericlist',  $arr, $name, 'class="form-select" onchange="Disable()"', 'value', 'text', (int) $selected, $id);
}

?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=cron&'. Session::getFormToken() .'=1');?>" class="margin-left-10 margin-right-10" method="post" name="adminForm" id="adminForm">

    <?php 
    // Cargamos la navegación
    require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
    ?>
            <div id="toast" class="col-12 toast align-items-center margin-bottom-10" role="alert" aria-live="assertive" aria-atomic="true">
			  <div class="toast-header">			
				<strong id="toast-auto" class="me-auto"></strong>			
				<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
			  </div>
			  <div id="toast-body" class="toast-body">			
			  </div>
			</div>      
			
            <div class="card mb-3">
                <div class="card-body">
                   <div class="overflow-x-auto">                        
                            <div class="card-header text-white bg-primary">
                                <?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS') ?>
                            </div>
                            <div class="card-body">
								<div class="input-group mb-3">
									<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('PLG_SECURITYCHECKPRO_CRON_TASKS_DESCRIPTION') ?>','<?php echo Text::_('PLG_SECURITYCHECKPRO_CRON_TASKS_LABEL') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
									<span class="input-group-text" id="tasks_label"><?php echo Text::_('PLG_SECURITYCHECKPRO_CRON_TASKS_LABEL'); ?></span>
									<?php echo taskslist('tasks', array(), $this->tasks) ?>														
								</div>
								
								<div class="input-group mb-3">
									<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('PLG_SECURITYCHECKPRO_CRON_LAUNCH_TIME_DESCRIPTION') ?>','<?php echo Text::_('PLG_SECURITYCHECKPRO_CRON_TASKS_LABEL') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
									<span class="input-group-text" id="launch_time_label"><?php echo Text::_('PLG_SECURITYCHECKPRO_CRON_LAUNCH_TIME_LABEL'); ?></span>
									<?php echo launchtimelist('launch_time', array(), $this->launch_time) ?>														
								</div>
								
								<div class="input-group mb-3">
									<input class="btn btn-info" type="button" onclick="configure_toast('<?php echo Text::_('PLG_SECURITYCHECKPRO_CRON_LAUNCH_TIME_DESCRIPTION') ?>','<?php echo Text::_('PLG_SECURITYCHECKPRO_CRON_TASKS_LABEL') ?>');" value="<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>" />
									<span class="input-group-text" id="periodicity_label"><?php echo Text::_('PLG_SECURITYCHECKPRO_CRON_PERIODICITY_LABEL'); ?></span>
									<?php echo periodicitylist('periodicity', array(), $this->periodicity) ?>														
								</div>                               
                            </div>                       
                    </div>                    
                </div>
            </div>
        </div>
</div>


<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="controller" value="cron" />
</form>
