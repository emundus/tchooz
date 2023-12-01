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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

Session::checkToken('get') or die('Invalid Token');

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

$kind_array = array(HTMLHelper::_('select.option', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FILE'), Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FILE')),
            HTMLHelper::_('select.option', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FOLDER'), Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FOLDER')));

$status_array = array(HTMLHelper::_('select.option', '0', Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_TITLE_COMPROMISED')),
            HTMLHelper::_('select.option', '1', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_OK')),
            HTMLHelper::_('select.option', '2', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_EXCEPTIONS')));

// Cargamos los archivos javascript necesarios
$document = Factory::getDocument();

$document->addScript(Uri::root().'media/com_securitycheckpro/new/js/sweetalert.min.js');

$list_group_style = 'class="margin-right-5" style="width: fit-content;"';
if (version_compare(JVERSION, '4.0', 'ge')) {		
	$list_group_style = 'style="width: fit-content;"';
}

// Add style declaration
$media_url = "media/com_securitycheckpro/stylesheets/cpanelui.css";
HTMLHelper::stylesheet($media_url);

$sweet = "media/com_securitycheckpro/stylesheets/sweetalert.css";
HTMLHelper::stylesheet($sweet);
?>

<?php 
// Cargamos el contenido común...
require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/common.php';

// ... y el contenido específico
require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/fileintegrity.php';
?>

<?php
if (empty($this->last_check_integrity) ) {
    $this->last_check_integrity = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER');
}
if (empty($this->files_status) ) {
    $this->files_status = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NOT_DEFINED');
}
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&'. Session::getFormToken() .'=1');?>" method="post" class="margin-left-10 margin-right-10" name="adminForm" id="adminForm">

<?php 
        
        // Cargamos la navegación
        require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
?>
        
         
            <!-- Contenido principal -->            
            <div class="row">
            
                <div class="col-xl-4 col-sm-4 mb-4 margin-bottom-3rem">
                    <div class="card text-center">    
                        <div class="card-header">
        <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_CHECK_STATUS'); ?>                            
                        </div>
                        <div class="row card-body justify-content-center">                                
                            <div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item active font-size-13"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_CHECK_STARTTIME'); ?></li>
                                    <li class="list-group-item"><span id="start_time" class="badge badge-dark"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER'); ?></span></li>
                                </ul>
                            </div>                            
                            <div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item active font-size-13"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_CHECK_TASK'); ?></li>
                                    <li class="list-group-item">
                                        <span id="task_status" class="badge badge-info"><?php echo $this->files_status; ?></span>
                                        <span id="task_error" class="badge badge-danger display-none">Error</span>
                                    </li>
                                </ul>
                            </div>
                            <div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item active font-size-13"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_HASH_ALG'); ?></li>
                                    <li class="list-group-item"><span id="end_time" class="badge badge-dark"><?php echo $this->hash_alg; ?></span></li>
                                </ul>                                
                            </div>                            
                        </div>                        
                        <div id="button_start_scan" class="card-footer">
                            <button class="btn btn-primary" type="button" id="button_start_scan"><i class="fapro fa-fw fa-fire"></i><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_START_BUTTON'); ?></button>
                        </div>                        
                    </div>
                </div>
				
				 <!-- Last scan info modal -->
                <div class="modal hide bd-example-modal-lg" id="last_scan_info_modal" tabindex="-1" role="dialog" aria-labelledby="modal_last_scan_info_modalLabel" aria-hidden="true">
					<div class="modal-dialog modal-lg" role="document">
						<div class="modal-content">
                            <div class="modal-header alert alert-info">
                                <h2 class="modal-title"><?php echo Text::_('COM_SECURITYCHECKPRO_LAST_SCAN_INFO'); ?></h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>           
                            </div>
                            <div class="modal-body"> 
								<?php if (!empty($this->last_scan_info)) { ?>
									<div class="margin-left-10">
										<span class="badge badge-info"><b><?php echo Text::_('COM_SECURITYCHECKPRO_MAX_TIME_TO_SCAN_FILE') . '</b>' . htmlspecialchars($this->last_scan_info['max_time']) . " " . Text::_('COM_SECURITYCHECKPRO_SECONDS'); ?></span>
									</div>
									<div class="margin-left-10">
										<span class="badge badge-info margin-bottom-20"><b><?php echo Text::_('COM_SECURITYCHECKPRO_MAX_TIME_FILE') . '</b>' . htmlspecialchars($this->last_scan_info['max_time_filename']); ?></span>
									</div>				
									
									<div class="wrapper">
										<div class="pie-charts">
											<div class="pieID--micro-skills pie-chart--wrapper">
												<h2><?php echo Text::_('COM_SECURITYCHECKPRO_FILES_BY_TYPE'); ?></h2>
												<div class="pie-chart">
													<div class="pie-chart__pie"><div class="slice s0-0" style="transform: rotate(-1deg) translate3d(0px, 0px, 0px);"><span style="transform: rotate(0deg) translate3d(0px, 0px, 0px); background-color: tomato;"></span></div><div class="slice s0-1" style="transform: rotate(178deg) translate3d(0px, 0px, 0px);"><span style="transform: rotate(-126.88deg) translate3d(0px, 0px, 0px); background-color: tomato;"></span></div><div class="slice s1-0" style="transform: rotate(230.12deg) translate3d(0px, 0px, 0px);"><span style="transform: rotate(-50.12deg) translate3d(0px, 0px, 0px); background-color: forestgreen;"></span></div></div>
													<ul class="pie-chart__legend">
														<li style="border-color: tomato;"><em><?php echo Text::_('COM_SECURITYCHECKPRO_EXECUTABLE_FILES'); ?></em><span>
														<?php 
															if ( !empty($this->last_scan_info) ) {
																echo htmlspecialchars($this->last_scan_info['executable_files']);
															} else {
																// set a defaut value
																echo 1;
															}
														?></span></li>
														<li style="border-color: forestgreen;"><em><?php echo Text::_('COM_SECURITYCHECKPRO_NON_EXECUTABLE_FILES'); ?></em><span>
														<?php 
															if ( !empty($this->last_scan_info) ) {
																echo htmlspecialchars($this->last_scan_info['non_executable_files']);
															} else {
																// set a defaut value
																echo 1;
															}
														?>
														</span></li>
													</ul>
												</div>
											</div>
											
										</div>
									</div>
								<?php } ?>
                            </div>
                            <div class="modal-footer">
								<button class="btn" data-bs-dismiss="modal" aria-hidden="true"><?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
                            </div>              
                        </div>
                    </div>
                </div>                                                
                
                <div class="col-xl-7 col-sm-7 mb-7">
                    <div class="card text-center">    
                        <div class="card-header">
							<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_CHECK_INTEGRITY_RESULT_HEADER'); ?>
                        </div>
                        <div class="row card-body justify-content-center">
                            <div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item text-white bg-success"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_CHECK'); ?></li>
                                    <li class="list-group-item"><span class="badge badge-dark"><?php echo $this->last_check_integrity; ?></span></li>
                                </ul>
                            </div>
							<div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item text-white bg-success"><?php echo Text::_('COM_SECURITYCHECKPRO_TIME_TAKEN'); ?></li>
                                    <li class="list-group-item"><span class="badge badge-dark"><?php echo $this->time_taken; ?></span></li>
                                </ul>
                            </div>
                            <div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item text-white bg-success"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_FILES_SCANNED'); ?></li>
                                    <li class="list-group-item"><span class="badge badge-dark"><?php echo $this->files_scanned_integrity; ?></span></li>
									<?php
										if ( (!empty($this->time_taken)) && (is_array($this->last_scan_info)) ){
									?>
										<a href="#last_scan_info_modal" role="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?></a>
									<?php
										}
									?>
                                </ul>                                
                            </div>
                            <div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item text-white bg-success font-size-13"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_FILES_MODIFIED'); ?></li>
                                    <li class="list-group-item">
                                        <span class="badge badge-dark"><?php echo $this->files_with_bad_integrity; ?></span>
                                    </li>
                                </ul>
                            </div>   	
						</div>    
                        <div id="button_show_log" class="card-footer">    
        <?php	                            
        if (!empty($this->log_filename) ) { ?>
            <button class="btn btn-success" type="button" id="view_modal_log_button"><i class="fapro fa-fw fa-eye"></i><?php echo substr(Text::_('COM_SECURITYCHECKPRO_ACTION_VIEWLOGS'), 0, -1); ?></button>
        <?php }    ?>                            
                        </div>    
                    </div>                    
                </div>
                
                 <div id="scandata" class="col-lg-12 margin-top-30">
                    <div class="card mb-3">                        
                        <div class="card-body margin-left-10">
                            <div id="container_repair">
                                <div id="log-container_remember_text" class="centrado margen texto_14">
                                </div>
                                <div id="div_view_log_button" class="buttonwrapper">    
                                </div>                            
                                <div id="log-container_header" class="centrado margen texto_20">    
                                </div>
                            </div>
                            
                            <div id="error_message_container" class="securitycheck-bootstrap centrado margen-container">
                                <div id="error_message">
                                </div>    
                            </div>

                            <div id="error_button" class="securitycheck-bootstrap centrado margen-container">    
                            </div>
                            
                            <div id="memory_limit_message" class="centrado margen-loading">
                                <?php 
                                    // Extract 'memory_limit' value cutting the last character
                                    $memory_limit = ini_get('memory_limit');
                                    $memory_limit = (int) substr($memory_limit, 0, -1);
                                            
                                    // If $memory_limit value is less or equal than 128, shows a warning if no previous scans have finished
                                if (($memory_limit <= 128) && ($this->last_check_integrity == Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER')) ) {
                                    $span = "<div class=\"alert alert-warning\">";
                                    echo $span . Text::_('COM_SECURITYCHECKPRO_MEMORY_LIMIT_LOW') . "</div>";
                                }
                                ?>
                            </div>
							
                            <div id="scan_only_executable_message" class="centrado margen-loading">
                                <?php 
                                if ($this->scan_executables_only ) {
                                    $span = "<div class=\"alert alert-warning\">";
                                    echo $span . Text::_('COM_SECURITYCHECKPRO_SCAN_ONLY_EXECUTABLES_WARNING') . "</div>";
                                }
                                ?>
                            </div>

                            <div id="completed_message2" class="centrado margen-loading color_verde">    
                            </div>
                                                        
                            <div id="warning_message2" class="centrado margen-loading">                                
                            </div>
                                                                            
                            <div id="backup-progress" class="progress">
                                <div id="bar" class="progress-bar bg-success width-0" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>    
                        <div id="container_resultado">
							<div id="filter-bar" class="filter-search-bar btn-group">
								<div class="input-group">
									<input type="text" class="form-control" name="filter_fileintegrity_search" placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>" id="filter_fileintegrity_search" value="<?php echo $this->escape($this->state->get('filter.fileintegrity_search')); ?>" title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />									
									<span class="filter-search-bar__label visually-hidden">
									<label id="filter_search-lbl" for="filter_search">Filter:</label>
									</span>
									<button type="submit" class="filter-search-bar__button btn btn-primary" aria-label="Search">
										<span class="filter-search-bar__button-icon icon-search" aria-hidden="true"></span>
									</button>
									<button class="btn btn-dark" type="button" id="filter_fileintegrity_search_clear" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
								</div>
							                                
                                <div class="input-group pull-left margin-left-10">
                                        <select name="filter_fileintegrity_status" class="custom-select" onchange="this.form.submit()">
											<option value=""><?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_STATUS_DESCRIPTION');?></option>
											<?php echo HTMLHelper::_('select.options', $status_array, 'value', 'text', $this->state->get('filter.fileintegrity_status'));?>
										</select>
										<?php
										if (!empty($this->items) ) {        
										?>
											<?php echo $this->pagination->getLimitBox(); ?>
										
										<?php } ?> 
                                </div>								
                            </div>						
						
                                            
        <?php if (!$this->items == null) { ?>
            <?php if (($this->files_with_bad_integrity > 0 ) && ( empty($this->items) ) ) { ?>
                            <div class="alert alert-danger">
                <?php echo Text::_('COM_SECURITYCHECKPRO_EMPTY_ITEMS'); ?>
                            </div>                            
            <?php } ?>

            <?php if ($this->database_error == "DATABASE_ERROR" ) { ?>
                            <div class="alert alert-danger">
                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_DATABASE_ERROR'); ?>
                            </div>                            
            <?php } ?>

            <?php if ($this->files_with_bad_integrity >3000 ) { ?>
                            <div class="alert alert-danger">
                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ALERT') . "."; ?>
                                <br/>
                <?php echo Text::_('COM_SECURITYCHECKPRO_EMAIL_ALERT_BODY_ALERT'); ?>                                
                            </div>                            
            <?php } ?>

            <?php if ($this->show_all == 1 ) { ?>
                            <div class="alert alert-info">
                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_INFO'); ?>
                            </div>                            
            <?php } ?>
                            
                            <div class="card margin-top-30 margin-bottom-20">
                                <div class="card-header text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_COLOR_CODE'); ?>
                                </div>
                                <div class="card-block">                                    
                                    <table class="table table-borderless margin-top-30">
                                        <thead>
                                            <tr>
                                                <td><span class="badge badge-success"> </span>
                                                </td>
                                                <td class="left">
                                                    <?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_GREEN_COLOR'); ?>
                                                </td>
                                                <td><span class="badge badge-warning"> </span>
                                                </td>
                                                <td class="left">
                                                    <?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_YELLOW_COLOR'); ?>
                                                </td>
                                                <td><span class="badge badge-danger"> </span>
                                                </td>
                                                <td class="left">
                                                    <?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_RED_COLOR'); ?>
                                                </td>
                                            </tr>
                                        </thead>
                                    </table>                                
                                </div>                            
                            </div>                        
                            
            <?php
            if ((!empty($this->items)) && (!$this->state->get('filter.fileintegrity_status')) ) {            
                ?>
                                <div id="permissions_buttons">
                                    <div class="pull-right">
                                        <button class="btn btn-success margin-right-5" id="add_exception_button" href="#">
                                            <i class="fapro fa-fw fa-plus"> </i>
                <?php echo Text::_('COM_SECURITYCHECKPRO_ADD_AS_EXCEPTION'); ?>
                                        </button>                                        
                                    </div>
                                </div>
                <?php
            } else if ($this->state->get('filter.fileintegrity_status') == 2 ) { ?>
                                    <div id="permissions_buttons">
                                        <div class="btn-group pull-right">
                                            <button class="btn btn-danger" id="delete_exception_button" href="#">
                                                <i class="icon-trash icon-white"> </i>
                <?php echo Text::_('COM_SECURITYCHECKPRO_DELETE_EXCEPTION'); ?>
                                            </button>
                                        </div>
                                </div>

            <?php } ?>

                                <div>
                                    <span class="badge integrity-files padding-10-10-10-10"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_CHECKED_FILES');?></span>
            <?php
            if (!empty($this->items) ) {    
                $extensions_updated_tooltip = Text::_('COM_SECURITYCHECKPRO_EXTENSIONS_UPDATED_INSTALLED_DESC') . PHP_EOL;
                if (is_array($this->installs)) {
                    $extensions_updated = count($this->installs);     					
                    foreach($this->installs as $extension)
                    {
						if ( (is_string($extension['name'])) && (is_string($extension['type'])) ) {
							$extensions_updated_tooltip .= PHP_EOL . htmlentities($extension['name'], ENT_QUOTES) . " (" . htmlentities($extension['type'], ENT_QUOTES) . ")" . PHP_EOL;
						}
                    }
                } else
                {
                    $extensions_updated = 0;
                }
                ?>
                    <span id="extensions_updated_tooltip" class="badge extensions-updated padding-10-10-10-10" data-html="true" data-bs-toggle="tooltip" title="<?php echo $extensions_updated_tooltip; ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_EXTENSIONS_UPDATED_INSTALLED') . $extensions_updated; ?></span>
                <?php
            }        
            ?>
                                </div>
                                
                                <div class="table-responsive overflow-x-auto margin-top-30">
                                    <table id="filesintegritystatus_table" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
											<?php 
												if ($this->checkbox_position == 1) {
											?>
											<th class="filesintegrity-table width-5">
                                                <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
                                            </th> 
											<?php 
												}
											?>
                                            <th class="filesintegrity-table">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NAME'); ?>
                                            </th>
                                            <th class="filesintegrity-table ruta-style">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RUTA'); ?>                
                                            </th>
                                            <th class="filesintegrity-table">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TAMANNO'); ?>                
                                            </th>
                                            <th class="filesintegrity-table">
                                                <?php echo Text::_('Info'); ?>            
                                            </th>
                                            <th class="filesintegrity-table">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_MODIFIED'); ?>
                                            </th>
                                            <?php 
												if ($this->checkbox_position == 0) {
											?>
											<th class="filesintegrity-table width-5">
                                                <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
                                            </th> 
											<?php 
												}
											?>
                                        </tr>
                                    </thead>
            <?php
            $k = 0;
            if (!empty($this->items) ) {    
                foreach ($this->items as &$row) {
                    $safe_integrity = $row['safe_integrity'];
                    ?>
					<?php 
					if ($this->checkbox_position == 1) {
						echo '<td class="centrado">' . HTMLHelper::_('grid.id', $k, $row['path'], '', 'filesintegritystatus_table') . '</td>'; 
					}
					?>
                    <td class="centrado">
                    <?php
						if ( !is_dir($row['path']) ) {
							$last_part = explode(DIRECTORY_SEPARATOR, $row['path']);
							$end = end($last_part);
							echo htmlspecialchars($end);
						} else {
							echo "";							
						} 
                    ?>
                    </td>
                    <td class="centrado malwarescan-table-info">
                    <?php echo htmlspecialchars($row['path']); ?>
                    </td>
                    <td class="centrado">
                    <?php 
                    if ( !is_dir($row['path']) ) {
						if ( file_exists($row['path']) ) {
							$size = filesize($row['path']);
							echo htmlspecialchars($size);
						}
					} else {
						$size = "Not calculated";
						echo htmlspecialchars($size);
                    } 
                    ?>
                    </td>
					<?php 
                    if ($safe_integrity == '0' ) {
                        echo "<td class=\"centrado;\"><span class=\"badge badge-danger\">";
                    } else if ($safe_integrity == '1' ) {
                        echo "<td class=\"centrado;\"><span class=\"badge badge-success\">";
                    } else if ($safe_integrity == '2' ) {
                        echo "<td class=\"centrado;\"><span class=\"badge badge-warning\">";
                    } ?>
                    <?php echo htmlspecialchars($row['notes']); ?>
                    </span>
                    </td>
                    <?php 
                    if ($safe_integrity == '0' ) {
                        echo "<td class=\"centrado;\"><span class=\"badge badge-danger\">";
                    } else {
                        echo "<td class=\"centrado;\">";
                    } 
                    if (file_exists($row['path']) ) {
                        echo date('Y-m-d H:i:s', filemtime($row['path']));
                    }
                    ?>
                    </span>
                    </td>                    
                    <?php 
					if ($this->checkbox_position == 0) {
						echo '<td class="centrado">' . HTMLHelper::_('grid.id', $k, $row['path'], '', 'filesintegritystatus_table') . '</td>'; 
					}
					?>
                    </tr>
                    <?php
                    $k = $k+1;
                }
            }  ?>
                                    </table>
                                    </div>

            <?php
            if (!empty($this->items) ) {
					
                ?>
                                    <div class="margen">
                                        <div>
                <?php echo $this->pagination->getListFooter(); ?>
                                        </div>
                                    </div>
            <?php } ?>    
                                </div>
        <?php } else {
			if ($this->state->get('filter.malwarescan_status') == 2 ) {
				if ($this->file_manager_include_exceptions_in_database == 0 ) { 
					echo '<div class="alert alert-info">' . Text::_('COM_SECURITYCHECKPRO_EXCEPTIONS_NOT_INCLUDED_IN_DATABASE'). '</div>';                            
				} 
			}
		} ?>
                    </div>        
                </div>
            </div>
            
        </div>
</div>        

<input type="hidden" name="controller" value="filemanager" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="table" value="integrity" />
</form>
