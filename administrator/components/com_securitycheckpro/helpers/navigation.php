<?php
defined('_JEXEC') or die();

use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Router\Route as JRoute;
use Joomla\CMS\Factory as JFactory;

// styles ('data-xxx' for J3 and 'data-bs-xxxx' for J4)
$dropdown_style = "data-toggle";
$data_dismiss = "data-dismiss";
$dropdown_class_style = "dropdown-menu";


if (version_compare(JVERSION, '4.0', 'ge')) {
	$dropdown_style = "data-bs-toggle";
	$data_dismiss = "data-bs-dismiss";
	$dropdown_class_style = "header dropdown-menu";
	$document = JFactory::getDocument();
    $document->addStyleDeclaration('.dropdown-item:last-child {border-bottom-right-radius: 0; border-bottom-left-radius: 0;}.dropdown-item:first-child {border-top-left-radius: 0;border-top-right-radius: 0;}');
	
}
?>

    <!-- Modal view file -->
        <div class="modal" id="view_logfile" tabindex="-1" role="dialog" aria-labelledby="viewlogfileLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
              <div class="modal-header alert alert-info">
                <h2 class="modal-title" id="viewlogfileLabel"><?php echo JText::_('COM_SECURITYCHECKPRO_REPAIR_VIEW_LOG_MESSAGE'); ?></h2>
                <button type="button" class="close" <?php echo $data_dismiss; ?>="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <? ob_start(); ?>              
                <textarea rows="10" class="table">        
                <?php 
                $contenido = "There is no log info";
                if (!empty($this->log_filename)) {
                    if (file_exists(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR.$this->log_filename)) {
                        $contenido = file_get_contents(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR.$this->log_filename);        
                        $contenido = filter_var($contenido, FILTER_SANITIZE_SPECIAL_CHARS);
                    }            
                }        
                echo $contenido;                
                ?></textarea>
                <? echo ob_get_clean(); ?>
              </div>
                <div class="modal-footer">                    
                    <button type="button" class="btn btn-default" <?php echo $data_dismiss; ?>="modal"><?php echo JText::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
                </div>              
            </div>
          </div>
        </div>
        
<!-- Modal purgesessions -->
        <div class="modal fade" id="purgesessions" tabindex="-1" role="dialog" aria-labelledby="purgesessionsLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header alert alert-info">
                <h2 class="modal-title" id="purgesessionsLabel"><?php echo JText::_('COM_SECURITYCHECKPRO_PURGE_SESSIONS'); ?></h2>
                <button type="button" class="close" <?php echo $data_dismiss; ?>="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">    
                <div id="div_messages">
                    <h5><?php echo JText::_('COM_SECURITYCHECKPRO_PURGE_SESSIONS_MESSAGE'); ?></h5>                        
                    <h5><?php echo JText::_('COM_SECURITYCHECKPRO_PURGE_SESSIONS_MESSAGE_EXPLAINED'); ?></h5>
                </div>
                <div id="div_loading" style="text-align:center; display:none;">
                    <span class="tammano-18"><?php echo JText::_('COM_SECURITYCHECKPRO_PURGING'); ?></span><br/>
                    <img src="<?php echo JURI::root(); ?>media/com_securitycheckpro/images/loading.gif" width="30" height="30" />
                </div>        
              </div>
                <div class="modal-footer" id="div_boton_subida">
                    <input class="btn btn-primary" type="button" id="boton_subida" value="<?php echo JText::_('COM_SECURITYCHECKPRO_YES'); ?>" onclick= "muestra_progreso_purge(); Joomla.submitbutton('purge_sessions');"  />
                    <button type="button" class="btn btn-default" <?php echo $data_dismiss; ?>="modal"><?php echo JText::_('COM_SECURITYCHECKPRO_NO'); ?></button>
                </div>              
            </div>
          </div>
        </div>
        
        <!-- Modal initialize_data -->
        <div class="modal fade" id="initialize_data" tabindex="-1" role="dialog" aria-labelledby="initializedataLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header alert alert-info">
                <h2 class="modal-title" id="initializedataLabel"><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_INITIALIZE_DATA'); ?></h2>
                <button type="button" class="close" <?php echo $data_dismiss; ?>="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body text-center">    
                <div id="warning_message" class="margen-loading texto_14">
					<?php echo JText::_('COM_SECURITYCHECKPRO_FILEMANAGER_CLEAR_DATA_WARNING_START_MESSAGE'); ?>
                </div>
                <div id="completed_message" class="margen-loading texto_14 color_verde">    
                </div>
                <div id="loading-container" class="text-center margen">    
                </div>        
              </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="buttonwrapper" type="button" onclick="hideElement('buttonwrapper'); hideElement('buttonclose'); clear_data_button();"><i class="fapro fa-fw fa-fire"></i><?php echo JText::_('COM_SECURITYCHECKPRO_CLEAR_DATA_CLEAR_BUTTON'); ?></button>
                    <button type="button" id="buttonclose" class="btn btn-default" <?php echo $data_dismiss; ?>="modal"><?php echo JText::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
                </div>              
            </div>
          </div>
        </div>
        
        <!-- Modal clean tmp dir -->
        <div class="modal fade" id="cleantmpdir" tabindex="-1" role="dialog" aria-labelledby="cleantmpdirLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header alert alert-info">
                <h2 class="modal-title" id="cleantmpdirLabel"><?php echo JText::_('COM_SECURITYCHECKPRO_CLEAN_TMP_DIR'); ?></h2>
                <button type="button" class="close" <?php echo $data_dismiss; ?>="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body text-center">    
                <div id="warning_message_tmpdir" class="margen-loading texto_14">
        <?php echo JText::_('COM_SECURITYCHECKPRO_CLEAN_TMP_DIR_MESSAGE'); ?>
                </div>
                <div id="completed_message_tmpdir" class="margen-loading texto_14">    
                </div>
                <div id="tmpdir-container" class="text-center margen">    
                </div>    
                <div id="container_result" class="text-center margen hide">    
                    <textarea id="container_result_area" rows="10" class="table" readonly>                        
                    </textarea>                
                </div>
              </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="buttonwrapper_tmpdir" type="button" onclick="hideElement('buttonwrapper_tmpdir'); hideElement('buttonclose_tmpdir'); clean_tmp_dir();"><i class="fapro fa-fw fa-fire"></i><?php echo JText::_('COM_SECURITYCHECKPRO_CLEAR_DATA_CLEAR_BUTTON'); ?></button>
                    <button type="button" id="buttonclose_tmpdir" class="btn btn-default" <?php echo $data_dismiss; ?>="modal"><?php echo JText::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
                </div>              
            </div>
          </div>
        </div>

	<div class="d-grid gap-2 d-xxl-block" style="margin-bottom: 1rem; margin-top: 1rem; text-align: center;">
		<a class="btn btn-primary" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro');?>">
			<span class="fapro fa-fw fa-home"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_DASHBOARD'); ?>			
		</a>
		<a class="btn btn-primary" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=filemanager&view=sysinfo&'. JSession::getFormToken() .'=1');?>">
			<span class="fapro fa-fw fa-info-square"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_SYSINFO_TEXT'); ?>			
		</a>
		
		<a class="btn btn-primary" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=securitycheckpro&'. JSession::getFormToken() .'=1');?>">
            <span class="fapro fa-fw fa-check-circle"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_CHECK_VULNERABILITIES_TEXT'); ?>            
        </a>        
        
        <a class="btn btn-primary" href="<?php echo 'index.php?option=com_securitycheckpro&controller=securitycheckpro&view=logs'?>">
            <span class="fapro fa-fw fa-eye"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_VIEW_FIREWALL_LOGS'); ?>           
			<?php	
			if ($this->logs_pending >= 99) {
				$this->logs_pending = "+99";
			}
			if ($this->logs_pending == 0) { ?>
						<span class="badge badge-success">
			<?php     } else
							{ ?>
						<span class="badge badge-warning">
			<?php	}
						echo $this->logs_pending;
			?>
			</span>
        </a>          
		
		<?php 
		if ($this->trackactions_plugin_exists) {                 
			?>
				<a class="btn btn-primary" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=securitycheckpro&view=trackactions_logs');?>">
					<span class="fapro fa-fw fa-binoculars"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_VIEW_TRACKACTIONS_LOGS'); ?>				
				</a>
			<?php
		}
		?>
        
		<?php 
            // Chequeamos si existe el fichero filemanager, necesario para lanzar las tareas de integridad y permisos
            $mainframe =JFactory::getApplication();
            $exists_filemanager = $mainframe->getUserState("exists_filemanager", true);
                    
			if ($exists_filemanager) {                        
		?> 
		
		<div class="btn-group">
			<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuOptions" <?php echo $dropdown_style; ?>="dropdown" aria-expanded="false">    
			<?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_OPTIONS'); ?><span class="caret"></span>
			</button>
			<ul class="<?php echo $dropdown_class_style; ?>" aria-labelledby="dropdownMenuoptions">
				<li>
					<a class="dropdown-item" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&'. JSession::getFormToken() .'=1');
					?>"><span class="fapro fa-fw fa-circle"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_FILE_MANAGER_TEXT'); ?></a>
				</li>
				<li>
					<a class="dropdown-item" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&'. JSession::getFormToken() .'=1');?>"><span class="fapro fa-fw fa-file-check"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_FILE_INTEGRITY_TEXT'); ?></a>
				</li>
				<li>
					<a class="dropdown-item" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=protection&view=protection&'. JSession::getFormToken() .'=1');?>"><span class="fapro fa-fw fa-file-alt"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_HTACCESS_PROTECTION_TEXT'); ?></a>
				</li>
				<li>
					<a class="dropdown-item" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=filemanager&view=malwarescan&'. JSession::getFormToken() .'=1');?>"><span class="fapro fa-fw fa-bug"></span><?php echo JText::_('COM_SECURITYCHECKPRO_MALWARESCAN'); ?></a>
				</li>				  
			</ul>
		</div>
			               
    <?php } ?>
		
		<div class="btn-group">
			<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuConfiguration" <?php echo $dropdown_style; ?>="dropdown" aria-expanded="false">    
			<?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_CONFIGURATION'); ?><span class="caret"></span>
			</button>
			<ul class="<?php echo $dropdown_class_style; ?>" aria-labelledby="dropdownMenuConfiguration">
				<li>
					<a class="dropdown-item" href="index.php?option=com_config&view=component&component=com_securitycheckpro&path=&return=<?php echo base64_encode(JURI::getInstance()->toString()) ?>"><span class="fapro fa-fw fa-wrench"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_GLOBAL_CONFIGURATION'); ?></a>
				</li>
				<li>
					<a class="dropdown-item" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=firewallconfig&view=firewallconfig&'. JSession::getFormToken() .'=1');?>"><span class="fapro fa-fw fa-wrench"></span><?php echo JText::_('COM_SECURITYCHECKPRO_WAF_CONFIG'); ?></a>
				</li>
				<li>
					<a class="dropdown-item" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=cron&view=cron&'. JSession::getFormToken() .'=1');?>"><span class="fapro fa-fw fa-wrench"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_CRON_CONFIGURATION'); ?></a>
				</li>
				<li>
					<a class="dropdown-item" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=rules&view=rules&'. JSession::getFormToken() .'=1');
					?>"><span class="fapro fa-fw fa-wrench"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_RULES_TEXT'); ?></a>
				</li>
				<li>
					<a class="dropdown-item" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=controlcenter&view=controlcenter&'. JSession::getFormToken() .'=1');
					?>"><span class="fapro fa-fw fa-wrench"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_CONTROLCENTER_TEXT'); ?></a>
				</li>			  
			</ul>
		</div>
			
		<div class="btn-group">	
			<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuTasks" <?php echo $dropdown_style; ?>="dropdown" aria-expanded="false">    
			<?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_TASKS'); ?><span class="caret"></span> 
			</button>
			<ul class="<?php echo $dropdown_class_style; ?>" aria-labelledby="dropdownMenuTasks">
				<li>
					<a class="dropdown-item" href="#initialize_data" data-toggle="modal" data-target="#initialize_data"><span class="fapro fa-fw fa-undo"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_INITIALIZE_DATA'); ?></a>
				</li>
				<li>
					<a class="dropdown-item" href="#" onclick="Joomla.submitbutton('Export_config');"><span class="fapro fa-fw fa-download"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_EXPORT_CONFIG'); ?></a>
				</li>
				<li>
					<a class="dropdown-item" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=filemanager&view=upload&'. JSession::getFormToken() .'=1');?>"><span class="fapro fa-fw fa-upload"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_IMPORT_CONFIG'); ?></a>
				</li>		  
			</ul>
		</div>
		
		
		<div class="btn-group">		
			<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuPerformance" <?php echo $dropdown_style; ?>="dropdown" aria-expanded="false">    
			<?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_PERFORMANCE'); ?> <span class="caret"></span>
			</button>
			<ul class="<?php echo $dropdown_class_style; ?>" aria-labelledby="dropdownMenuPerformance">
				<li>
					<?php
						$config = JFactory::getConfig();
						$dbtype = $config->get('dbtype');
						if (strstr($dbtype,"mysql")) {
					?>
					<a class="dropdown-item" href="<?php echo JRoute::_('index.php?option=com_securitycheckpro&controller=dbcheck&view=dbcheck&'. JSession::getFormToken() .'=1');?>"><span class="fapro fa-fw fa-database"></span><?php echo JText::_('COM_SECURITYCHECKPRO_DB_OPTIMIZATION'); ?></a>
					<?php
					}
					?>
				</li>
				<li>
					<a class="dropdown-item" href="#purge_sessions" data-toggle="modal" data-target="#purgesessions"><span class="fapro fa-fw fa-user-times"></span><?php echo JText::_('COM_SECURITYCHECKPRO_PURGE_SESSIONS'); ?></a>
				</li> 
				<li>
					<a class="dropdown-item" href="#clean_tmp_dir" data-toggle="modal" data-target="#cleantmpdir"><span class="fapro fa-fw fa-recycle"></span><?php echo JText::_('COM_SECURITYCHECKPRO_CLEAN_TMP_DIR'); ?></a>
				</li> 
			</ul>
		</div>
				
		<a class="btn btn-primary" href="#" onclick="get_otp_status();">
			<span class="fapro fa-fw fa-sign-in"><?php echo JText::_('OTP'); ?></span>
		</a>
     
	</div>
