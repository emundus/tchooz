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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

Session::checkToken('get') or die('Invalid Token');

// Load plugin language
$lang2 = Factory::getLanguage();
$lang2->load('plg_system_securitycheckpro');

$type_array = array(HTMLHelper::_('select.option', 'Component', Text::_('COM_SECURITYCHECKPRO_TITLE_COMPONENT')),
            HTMLHelper::_('select.option', 'Plugin', Text::_('COM_SECURITYCHECKPRO_TITLE_PLUGIN')),
            HTMLHelper::_('select.option', 'Module', Text::_('COM_SECURITYCHECKPRO_TITLE_MODULE')));
            
$vulnerable_array = array(HTMLHelper::_('select.option', 'Si', Text::_('COM_SECURITYCHECKPRO_HEADING_VULNERABLE')),
            HTMLHelper::_('select.option', 'No', Text::_('COM_SECURITYCHECKPRO_GREEN_COLOR')));

$data_dismiss = "data-bs-dismiss";
?>

    <!-- Modal vulnerable extension -->
    <div class="modal bd-example-modal-lg" id="modal_vuln_extension" tabindex="-1" role="dialog" aria-labelledby="modal_vuln_extensionLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" class="max-width-1200" role="document">
            <div class="modal-content">
                <div class="modal-header alert alert-info">
                    <h2 class="modal-title"><?php echo Text::_('COM_SECURITYCHECKPRO_VULN_INFO_TEXT'); ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>            
                </div>
                <div class="modal-body" class="overflow-x-auto">
                    <div class="table-responsive" id="response_result">        
                    </div>                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
                </div>              
            </div>
        </div>
    </div>                                    

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=securitycheckpro&view=securitycheckpro&'. Session::getFormToken() .'=1');?>" class="margin-left-10 margin-right-10" method="post" name="adminForm" id="adminForm">

    <?php 
    // Cargamos la navegación
    require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
    ?>
        
                  
            <!-- Contenido principal -->
            <!-- Update database plugin status -->
            <div class="card mb-3">
                    <div class="card-body">
        <?php if (($this->update_database_plugin_exists) && ($this->update_database_plugin_enabled) && ($this->database_message == "PLG_SECURITYCHECKPRO_UPDATE_DATABASE_DATABASE_UPDATED") ) { ?>                        
                        <div class="badge bg-success">
                            <h4><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES'); ?></h4>
                            <p><strong><?php echo Text::_('COM_SECURITYCHECKPRO_DATABASE_VERSION'); ?></strong><?php echo($this->database_version); ?></p>
                            <p><strong><?php echo Text::_('COM_SECURITYCHECKPRO_LAST_CHECK'); ?></strong><?php echo($this->last_check); ?></p>
                        </div>
        <?php } else if (($this->update_database_plugin_exists) && ($this->update_database_plugin_enabled) && (is_null($this->database_message)) ) { ?>
                            <div class="badge bg-success">
                                <h4><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES'); ?></h4>
                                <p><strong><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_NOT_LAUNCHED'); ?></strong></p>                        
                            </div>
        <?php } else if (($this->update_database_plugin_exists) && ($this->update_database_plugin_enabled) && ( !($this->database_message == "PLG_SECURITYCHECKPRO_UPDATE_DATABASE_DATABASE_UPDATED") && !(is_null($this->database_message) )) ) { ?>                            
                            <div class="badge bg-danger">
                                <h4><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_PROBLEM'); ?></h4>
                                <p><strong><?php echo Text::_('COM_SECURITYCHECKPRO_DATABASE_MESSAGE'); ?></strong><?php echo Text::_($this->database_message); ?></p>
            <?php
            if ($this->database_message != "COM_SECURITYCHECKPRO_UPDATE_DATABASE_SUBSCRIPTION_EXPIRED" ) {
                ?>
                                <a href="<?php echo 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $this->plugin_id?>" class="btn btn-dark"><?php echo Text::_('COM_SECURITYCHECKPRO_CHECK_CONFIG'); ?></a>            
            <?php } else { ?>
                                    <a href="https://securitycheck.protegetuordenador.com/subscriptions" target="_blank"  rel="noopener noreferrer" class="btn"><?php echo Text::_('COM_SECURITYCHECKPRO_RENEW'); ?></a>
            <?php } ?>
                                        
                            </div>    
        <?php } else if (($this->update_database_plugin_exists) && (!$this->update_database_plugin_enabled) ) { ?>
                            <div class="badge bg-warning">
                                <h4><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME'); ?></h4>
                                <p><strong><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_DISABLED'); ?></strong></p>                        
                            </div>
        <?php } else if (!($this->update_database_plugin_exists) ) { ?>
                            <div class="badge bg-info">
                                <h4><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_NOT_INSTALLED'); ?></h4>
                                <p><strong><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_NOT_RECEIVE'); ?></strong></p>            
                            </div>                        
        <?php } ?>                                                
                    </div>
                </div>
            
            <!-- Extensions table -->
            <div class="card mb-3 margin-left-10 margin-right-10">
                <div id="editcell">
                    <div class="card-header text-center">
						<?php echo Text::_('COM_SECURITYCHECKPRO_COLOR_CODE'); ?>
                    </div>
                    <table class="table table-borderless">                        
                        <thead>
                            <tr>
                                <td><span class="badge bg-success"> </span>
                                </td>
                                <td class="left">
									<?php echo Text::_('COM_SECURITYCHECKPRO_GREEN_COLOR'); ?>
                                </td>
                                <td><span class="badge bg-warning"> </span>
                                </td>
                                <td class="left">
									<?php echo Text::_('COM_SECURITYCHECKPRO_YELLOW_COLOR'); ?>
                                </td>
                                <td><span class="badge bg-danger"> </span>
                                </td>
                                <td class="left">
									<?php echo Text::_('COM_SECURITYCHECKPRO_RED_COLOR'); ?>
                                </td>
                            </tr>
                        </thead>
                    </table>                    
                </div>
                
                <div class="row margin-left-10 margin-right-10">
					<div class="col">
						<select name="filter_extension_type" class="custom-select" onchange="this.form.submit()">
							<option value=""><?php echo Text::_('COM_SECURITYCHECKPRO_TYPE_DESCRIPTION');?></option>
								<?php echo HTMLHelper::_('select.options', $type_array, 'value', 'text', $this->state->get('filter.extension_type'));?>
						</select>
					</div>
					<div class="col">
						<select name="filter_vulnerable" class="custom-select" onchange="this.form.submit()">
							<option value=""><?php echo Text::_('COM_SECURITYCHECKPRO_VULNERABILITIES');?></option>
								<?php echo HTMLHelper::_('select.options', $vulnerable_array, 'value', 'text', $this->state->get('filter.vulnerable'));?>
						</select>
					</div>
					<div class="col">
					<?php  if (!empty($this->items) ) {  echo $this->pagination->getLimitBox();  }?>
					</div>
					<div class="col">
						<span class="badge bg-info padding-10-10-10-10 float-right"><?php echo Text::_('COM_SECURITYCHECKPRO_UPDATE_DATE') . $this->last_update; ?></span>
					</div>
                </div>
				
    
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="5" class="alert alert-info text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_HEADING_ID'); ?>
                                    </th>
                                    <th class="alert alert-info text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_HEADING_PRODUCT'); ?>
                                    </th>
                                    <th class="alert alert-info text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_HEADING_TYPE'); ?>
                                    </th>
                                    <th class="alert alert-info text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_HEADING_INSTALLED_VERSION'); ?>
                                    </th>
                                    <th class="alert alert-info text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_HEADING_VULNERABLE'); ?>
                                    </th>
                                </tr>
                            </thead>
        <?php
        $k = 0;
        if (!empty($this->items) ) {
            foreach ($this->items as &$row) {
                ?>
                            <tr class="<?php echo "row$k"; ?>">
                                <td class="text-center">
                <?php echo $row->id; ?>
                                </td>
                                <td class="text-center">
                <?php
                $vulnerable = $row->Vulnerable;
                if ($vulnerable <> 'No' ) {							
                    echo '<a href="#" onclick="filter_vulnerable_extension(\'' . $row->Product .'\');">' . $row->Product . '</a>';                        
                } else {
                    echo $row->Product; 
                }
                ?>    
                                </td>
                <?php 
                $type = $row->sc_type;
                ?>
                                    <td class="text-center">
                <?php
                if ($type == 'core' ) {
                    echo "<span class=\"badge background-FFADF5\">";
                } else if ($type == 'component' ) {
                    echo "<span class=\"badge bg-info\">";
                } else if ($type == 'module' ) {
                    echo "<span class=\"badge bg-secondary\">";
                } else {
                    echo "<span class=\"badge bg-dark\">";
                }
                ?>
                <?php echo Text::_('COM_SECURITYCHECKPRO_TYPE_' . $row->sc_type); ?>
                                </span>
                                </td>
                                <td class="text-center">
                <?php echo $row->Installedversion; ?>
                                </td>
                <?php 
                $vulnerable = $row->Vulnerable;
                ?>
                            <td class="text-center">
                <?php
                if ($vulnerable == 'Si' ) {
                    echo "<span class=\"badge bg-danger\">";
                } else if ($vulnerable == 'Indefinido' ) {
                    echo "<span class=\"badge bg-warning\">";
                } else
                {
                    echo "<span class=\"badge bg-success\">";
                }
                ?>
                <?php echo Text::_('COM_SECURITYCHECKPRO_VULNERABLE_' . $row->Vulnerable); ?>
                            </span>
                            </td>
                            </tr>
                <?php
                $k = 1 - $k;
            }
        }
        ?>                            
                        </table>
                    </div>    

        <?php
        if (!empty($this->items) ) {        
            ?>
                        <div>
            <?php echo $this->pagination->getListFooter(); ?>
                        </div>                    
        <?php }    ?>
                </div>
                
            </div>        
                
</div>

<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="controller" value="securitycheckpro" />
</form>
