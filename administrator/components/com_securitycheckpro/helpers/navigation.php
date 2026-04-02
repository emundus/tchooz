<?php
defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;

HTMLHelper::_('bootstrap.modal');

/** @var \Joomla\CMS\Application\CMSApplication $app */
$app       = Factory::getApplication();
$document  = $app->getDocument();
$input     = $app->getInput();
$view      = $input->getCmd('view', '');
$ctrl      = $input->getCmd('controller', '');
$option    = $input->getCmd('option', '');
$logo_src = Uri::root(true) . '/media/com_securitycheckpro/images/logo_securitycheck_pro_joomla.png';

// Datos dinámicos
$basemodel = new BaseModel();
$logs_pending = (int) $basemodel->LogsPending();
$trackactions_plugin_exists = (bool) $basemodel->PluginStatus(8);
$exists_filemanager = $app->getUserState("exists_filemanager", true);
?>   
        
<!-- Modal purgesessions -->
        <div class="modal fade" id="purgesessions" tabindex="-1" role="dialog" aria-labelledby="purgesessionsLabel" aria-hidden="true">		
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header alert alert-info">
                <h2 class="modal-title" id="purgesessionsLabel"><?php echo Text::_('COM_SECURITYCHECKPRO_PURGE_SESSIONS'); ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">    
                <div id="div_messages" class="margen-loading texto_14">
					<?php echo Text::_('COM_SECURITYCHECKPRO_PURGE_SESSIONS_MESSAGE'); ?>                       
                    <?php echo Text::_('COM_SECURITYCHECKPRO_PURGE_SESSIONS_MESSAGE_EXPLAINED'); ?>
                </div>
                <div id="div_loading" class="margen_inferior" style="text-align:center; display:none;">
                    <span class="tammano-18"><?php echo Text::_('COM_SECURITYCHECKPRO_PURGING'); ?></span><br/>
                    <div class="d-flex justify-content-center">
						<div class="spinner-border" role="status">
							<span class="visually-hidden"><?php echo Text::_('COM_SECURITYCHECKPRO_PURGING'); ?></span>
						</div>
					</div>
                </div>        
              </div>
                <div class="modal-footer" id="div_purge_sessions">					
                    <input class="btn btn-primary" type="button" id="boton_purge_sessions" value="<?php echo Text::_('COM_SECURITYCHECKPRO_YES'); ?>"  />
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_NO'); ?></button>
                </div>              
            </div>
          </div>
        </div>
        
        <!-- Modal initialize_data -->
        <div class="modal fade" id="initialize_data" tabindex="-1" role="dialog" aria-labelledby="initializedataLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header alert alert-info">
                <h2 class="modal-title" id="initializedataLabel"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_INITIALIZE_DATA'); ?></h2>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body text-center">    
                <div id="warning_message" class="margen-loading texto_14">
					<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_CLEAR_DATA_WARNING_START_MESSAGE'); ?>
                </div>
                <div id="completed_message" class="margen-loading texto_14 color_verde">    
                </div>
                <div id="loading-container" class="text-center margen">    
                </div>        
              </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="buttonwrapper" type="button" onclick="hideElement('buttonwrapper'); hideElement('buttonclose'); clear_data_button();"><i class="fa fa-fire"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CLEAR_DATA_CLEAR_BUTTON'); ?></button>
                    <button type="button" id="buttonclose" class="btn btn-default" data-bs-dismiss="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
                </div>              
            </div>
          </div>
        </div>
        
        <!-- Modal clean tmp dir -->
        <div class="modal fade" id="cleantmpdir" tabindex="-1" role="dialog" aria-labelledby="cleantmpdirLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header alert alert-info">
                <h2 class="modal-title" id="cleantmpdirLabel"><?php echo Text::_('COM_SECURITYCHECKPRO_CLEAN_TMP_DIR'); ?></h2>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body text-center">    
                <div id="warning_message_tmpdir" class="margen-loading texto_14">
					<?php echo Text::_('COM_SECURITYCHECKPRO_CLEAN_TMP_DIR_MESSAGE'); ?>
                </div>
                <div id="completed_message_tmpdir" class="margen-loading texto_14">    
                </div>
                <div id="tmpdir-container" class="text-center margen">    
                </div>    
                <div id="container_result" class="text-center margen-loading texto_14" style="display:none;">    
                    <textarea id="container_result_area" rows="10" class="table" readonly>                        
                    </textarea>                
                </div>
              </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="buttonwrapper_tmpdir" type="button" onclick="hideElement('buttonwrapper_tmpdir'); hideElement('buttonclose_tmpdir'); clean_tmp_dir();"><i class="fa fa-fire"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CLEAR_DATA_CLEAR_BUTTON'); ?></button>
                    <button type="button" id="buttonclose_tmpdir" class="btn btn-default" data-bs-dismiss="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
                </div>              
            </div>
          </div>
        </div>
		
	<div id="com_securitycheckpro" class="scp-toolbar mb-3 mt-3">		  
	  <!-- Grupo 1: Navegación básica -->
	  <div class="btn-group">
		<a class="btn btn-sm btn-light border-0" 
			 href="<?php echo Route::_('index.php?option=com_securitycheckpro');?>"
			 title="<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_DASHBOARD'); ?>">
			<img src="<?php echo htmlspecialchars($logo_src, ENT_QUOTES, 'UTF-8'); ?>"
				 alt="SecurityCheck Pro"
				 class="img-fluid"
				 style="max-height:24px;">
		</a>

		<a class="btn btn-sm btn-primary" href="<?php echo Route::_('index.php?option=com_securitycheckpro&view=sysinfo&'. Session::getFormToken() .'=1');?>">
		  <span class="fa fa-info-circle"></span>
		  <span class="d-none d-sm-inline"> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_SYSINFO_TEXT'); ?></span>
		</a>

		<a class="btn btn-sm btn-primary" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=securitycheckpro&view=securitycheckpro&'. Session::getFormToken() .'=1');?>">
		  <span class="fa fa-check-circle"></span>
		  <span class="d-none d-sm-inline"> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_CHECK_VULNERABILITIES_TEXT'); ?></span>
		</a>
	  </div>

	  <!-- Grupo 2: Logs (con badge) -->
	  <div class="btn-group shrink">
		<div class="btn-group">
		  <a class="btn btn-sm btn-primary" href="<?php echo 'index.php?option=com_securitycheckpro&controller=securitycheckpro&view=logs'?>">
			<span class="fa fa-eye"></span>
			<span class="d-none d-sm-inline"> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_VIEW_FIREWALL_LOGS'); ?></span>
			<span class="badge <?php echo $logs_pending ? 'bg-warning':'bg-success'; ?>">
			  <?php echo (int)$logs_pending >= 99 ? '+99' : (int)$logs_pending; ?>
			</span>
		  </a>

		  <?php if ($trackactions_plugin_exists): ?>
		  <a class="btn btn-sm btn-primary" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=trackactions_logs&view=trackactions_logs');?>">
			<span class="fa fa-binoculars"></span>
			<span class="d-none d-sm-inline"> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_VIEW_TRACKACTIONS_LOGS'); ?></span>
		  </a>
		  <?php endif; ?>
		</div>
	  </div>

	  <!-- Grupo 3: Options (dropdown) -->
	  <?php if ($exists_filemanager): ?>
	  <div class="btn-group">
		<div class="btn-group">
		  <button class="btn btn-sm btn-primary dropdown-toggle" type="button"
				  data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
			<span class="fa fa-sliders-h"></span>
			<span class="d-none d-sm-inline"> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_OPTIONS'); ?></span>
		  </button>
		  <ul class="dropdown-menu">
			<li>
			  <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&'. Session::getFormToken() .'=1');?>"><span class="fa fa-folder-open"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_FILE_MANAGER_TEXT'); ?></a>
			</li>
			<li>
			  <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&'. Session::getFormToken() .'=1');?>"><span class="fa fa-file-signature"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_FILE_INTEGRITY_TEXT'); ?></a>
			</li>
			<li>
			  <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=protection&view=protection&'. Session::getFormToken() .'=1');?>"><span class="fa fa-file-alt"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_HTACCESS_PROTECTION_TEXT'); ?></a>
			</li>
			<li>
			  <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=malwarescan&'. Session::getFormToken() .'=1');?>"><span class="fa fa-bug"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_MALWARESCAN'); ?></a>
			</li>
		  </ul>
		</div>
	  </div>
	  <?php endif; ?>

	  <!-- Grupo 4: Configuration (dropdown) -->
	  <div class="btn-group">
		<div class="btn-group">
		  <button class="btn btn-sm btn-primary dropdown-toggle" type="button"
				  data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
			<span class="fa fa-wrench"></span>
			<span class="d-none d-sm-inline"> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_CONFIGURATION'); ?></span>
		  </button>
		  <ul class="dropdown-menu dropdown-menu-end">
			<li>
			  <a class="dropdown-item" href="index.php?option=com_config&view=component&component=com_securitycheckpro&path=&return=<?php echo base64_encode(Uri::getInstance()->toString()) ?>">
				<span class="fa fa-cog"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_GLOBAL_CONFIGURATION'); ?>
			  </a>
			</li>
			<li>
			  <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=firewallconfig&view=firewallconfig&'. Session::getFormToken() .'=1');?>">
				<span class="fa fa-shield-alt"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_WAF_CONFIG'); ?>
			  </a>
			</li>
			<li>
			  <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_scheduler&view=tasks');?>">
				<span class="fa fa-clock"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_CRON_CONFIGURATION'); ?>
			  </a>
			</li>
			<li>
			  <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=rules&view=rules&'. Session::getFormToken() .'=1');?>">
				<span class="fa fa-list"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_RULES_TEXT'); ?>
			  </a>
			</li>
			<li>
			  <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=controlcenter&view=controlcenter&'. Session::getFormToken() .'=1');?>">
				<span class="fa fa-tachometer-alt"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_CONTROLCENTER_TEXT'); ?>
			  </a>
			</li>
		  </ul>
		</div>
	  </div>

	  <!-- Grupo 5: Tasks (dropdown) -->
	  <div class="btn-group shrink">
		<div class="btn-group">
		  <button class="btn btn-sm btn-primary dropdown-toggle" type="button"
				  data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
			<span class="fa fa-tasks"></span>
			<span class="d-none d-sm-inline"> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_TASKS'); ?></span>
		  </button>
		  <ul class="dropdown-menu dropdown-menu-end">
			<li>
			  <a class="dropdown-item" href="#initialize_data" data-bs-toggle="modal" data-bs-target="#initialize_data">
				<span class="fa fa-undo"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_INITIALIZE_DATA'); ?>
			  </a>
			</li>
			<li>
			  <a class="dropdown-item" href="#" onclick="Joomla.submitbutton('Export_config');">
				<span class="fa fa-download"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_EXPORT_CONFIG'); ?>
			  </a>
			</li>
			<li>
			  <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=upload&'. Session::getFormToken() .'=1');?>">
				<span class="fa fa-upload"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_IMPORT_CONFIG'); ?>
			  </a>
			</li>
		  </ul>
		</div>
	  </div>

	  <!-- Grupo 6: Performance + OTP (acciones rápidas) -->
	  <div class="btn-group shrink">
		<div class="btn-group">
		  <button class="btn btn-sm btn-primary dropdown-toggle" type="button"
				  data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
			<span class="fa fa-tachometer-alt"></span>
			<span class="d-none d-sm-inline"> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_PERFORMANCE'); ?></span>
		  </button>
		  <ul class="dropdown-menu dropdown-menu-end">
			<?php
			  $config = $app->getConfig();
			  $dbtype = $config->get('dbtype');
			  if (strstr($dbtype, 'mysql')): ?>
			<li>
			  <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=dbcheck&view=dbcheck&'. Session::getFormToken() .'=1');?>">
				<span class="fa fa-database"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_DB_OPTIMIZATION'); ?>
			  </a>
			</li>
			<?php endif; ?>
			<li>
			  <a class="dropdown-item" href="#purgesessions" data-bs-toggle="modal" data-bs-target="#purgesessions">
				<span class="fa fa-user-times"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_PURGE_SESSIONS'); ?>
			  </a>
			</li>
			<li>
			  <a class="dropdown-item" href="#cleantmpdir" data-bs-toggle="modal" data-bs-target="#cleantmpdir">
				<span class="fa fa-recycle"></span> <?php echo Text::_('COM_SECURITYCHECKPRO_CLEAN_TMP_DIR'); ?>
			  </a>
			</li>
		  </ul>
		</div>

		<a class="btn btn-sm btn-outline-primary" href="#" onclick="get_otp_status();">
		  <span class="fa fa-sign-in-alt"></span>
		  <span class="d-none d-sm-inline"> <?php echo Text::_('OTP'); ?></span>
		</a>
	  </div>

	</div>