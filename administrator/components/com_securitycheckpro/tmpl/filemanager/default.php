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

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted access');
Session::checkToken('get') or die('Invalid Token');

$kind_array = array(HTMLHelper::_('select.option', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FILE'), Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FILE')),
            HTMLHelper::_('select.option', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FOLDER'), Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FOLDER')));

$status_array = array(HTMLHelper::_('select.option', '0', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_WRONG')),
            HTMLHelper::_('select.option', '1', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_OK')),
            HTMLHelper::_('select.option', '2', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_EXCEPTIONS')));

// Cargamos los archivos javascript necesarios
$document = Factory::getApplication()->getDocument();

$list_group_style = 'class="margin-right-5" style="width: fit-content;"';
if (version_compare(JVERSION, '4.0', 'ge')) {		
	$list_group_style = 'style="width: fit-content;"';
}

?>

<?php
if (empty($this->last_check) ) {
    $this->last_check = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER');
}
if (empty($this->files_status) ) {
    $this->files_status = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NOT_DEFINED');
}
?>

<?php 
// Obtenemos el tipo de servidor web
$mainframe = Factory::getApplication();
$server = $mainframe->getUserState("server", 'apache');
if (strstr($server, "iis") ) { ?>
<div class="alert alert-info">
    <?php echo Text::_('COM_SECURITYCHECKPRO_IIS_SERVER'); ?>
</div>
<?php } ?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&'. Session::getFormToken() .'=1');?>" method="post" class="margin-left-10 margin-right-10" name="adminForm" id="adminForm">

<?php 
        
        // Cargamos la navegación
        require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
?>           
            <!-- Contenido principal -->            
            <div class="row">
            
                <div class="col-xl-4 col-sm-4 mb-4">
                    <div class="card text-center">    
                        <div class="card-header">
        <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_CHECK_STATUS'); ?>                            
                        </div>
                        <div class="row card-body justify-content-center">                                
                            <div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item active"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_CHECK_STARTTIME'); ?></li>
                                    <li class="list-group-item"><span id="start_time" class="badge bg-dark"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER'); ?></span></li>
                                </ul>
                            </div>
                            <div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item active"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_CHECK_TASK'); ?></li>
                                    <li class="list-group-item">
                                        <span id="task_status" class="badge bg-info"><?php echo $this->files_status; ?></span>
                                        <span id="task_error" class="badge bg-danger display-none">Error</span>
                                    </li>
                                </ul>
                            </div>                        
                        </div>                        
                        <div id="button_start_scan" class="card-footer">
                            <button class="btn btn-primary" type="button"><i class="fa fa-fire"></i><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_START_BUTTON'); ?></button>
                        </div>                        
                    </div>
                </div>
                
                <div class="col-xl-7 col-sm-7 mb-7">
                    <div class="card text-center">    
                        <div class="card-header">
        <?php echo Text::_('COM_SECURITYCHECKPRO_FILE_CHECK_RESUME'); ?>
                        </div>
                        <div class="row card-body justify-content-center">
                            <div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item text-white bg-success"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_CHECK'); ?></li>
                                    <li class="list-group-item"><span class="badge bg-dark"><?php echo $this->last_check; ?></span></li>
                                </ul>
                            </div>
							<div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item text-white bg-success"><?php echo Text::_('COM_SECURITYCHECKPRO_TIME_TAKEN'); ?></li>
                                    <li class="list-group-item"><span class="badge bg-dark"><?php echo $this->time_taken; ?></span></li>
                                </ul>
                            </div>
                            <div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item text-white bg-success"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_FILES_SCANNED'); ?></li>
                                    <li class="list-group-item"><span class="badge bg-dark"><?php echo $this->files_scanned;; ?></span></li>
                                </ul>                                
                            </div>
                            <div <?php echo $list_group_style; ?>>
                                <ul class="list-group text-center">
                                    <li class="list-group-item text-white bg-success font-size-13"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_FILES_FOLDERS_INCORRECT_PERMISSIONS'); ?></li>
                                    <li class="list-group-item">
                                        <span class="badge bg-dark"><?php echo $this->incorrect_permissions; ?></span>
                                    </li>
                                </ul>
                            </div>                        
                        </div>    
                        <div id="button_show_log" class="card-footer">    
        <?php	                            
        if (!empty($this->log_filename) ) { ?>
                                    <button class="btn btn-success" type="button" id="view_modal_log_button"><i class="fa fa-eye"></i><?php echo substr(Text::_('COM_SECURITYCHECKPRO_ACTION_VIEWLOGS'), 0, -1); ?></button>
        <?php }    ?>                            
                        </div>    
                    </div>                    
                </div>
                
                 <div id="scandata" class="col-lg-12 margin-top-10">
                    <div class="card mb-3">                        
                        <div class="card-body margin-left-10">
                            <div id="container_repair">
                                <div id="log-container_remember_text" class="centrado margen texto_14">
                                </div>
                                <div id="div_view_log_button" class="buttonwrapper">    
                                </div>                            
                                <div id="log-container_header" class="centrado margen texto_20">    
                                </div>
                                <div id="log-text" class="overflow-y-scroll height-150">
            <?php
            if (!empty($this->repair_log) ) {
                echo $this->repair_log;
                                            
            }
            ?>
                                    
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
                                if (($memory_limit <= 128) && ($this->last_check == Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER')) ) {
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
									<input type="text" class="form-control" name="filter_filemanager_search" placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>" id="filter_filemanager_search" value="<?php echo $this->escape($this->state->get('filter.filemanager_search')); ?>" title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />
									<span class="filter-search-bar__label visually-hidden">
									<label id="filter_search-lbl" for="filter_search">Filter:</label>
									</span>
									<button type="submit" class="filter-search-bar__button btn btn-primary" aria-label="Search">
										<span class="filter-search-bar__button-icon icon-search" aria-hidden="true"></span>
									</button>
									<button class="btn btn-dark" type="button" id="filter_filemanager_search_clear_button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
								</div>
							                                
                                <div class="input-group pull-left margin-left-10">
                                        <select name="filter_filemanager_kind" class="custom-select margin-left-5" onchange="this.form.submit()">
                                            <option value=""><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_KIND_DESCRIPTION');?></option>
											<?php echo HTMLHelper::_('select.options', $kind_array, 'value', 'text', $this->state->get('filter.filemanager_kind'));?>
                                        </select>
                                        <select name="filter_filemanager_permissions_status" class="custom-select margin-left-5" onchange="this.form.submit()">
                                            <option value=""><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_PERMISSIONS_STATUS_DESCRIPTION');?></option>
											<?php echo HTMLHelper::_('select.options', $status_array, 'value', 'text', $this->state->get('filter.filemanager_permissions_status'));?>
                                        </select>
										<?php
										if (!empty($this->items_permissions) ) {        
										?>
											<?php echo $this->pagination->getLimitBox(); ?>
										
										<?php } ?> 
                                </div>								
                            </div>
                                            
        <?php if (!$this->items_permissions == null) { ?>
            <?php if (($this->files_with_incorrect_permissions > 0 ) && ( empty($this->items_permissions) ) ) { ?>
                            <div class="alert alert-error">
                <?php echo Text::_('COM_SECURITYCHECKPRO_EMPTY_ITEMS'); ?>
                            </div>                            
            <?php } ?>

            <?php if ($this->database_error == "DATABASE_ERROR" ) { ?>
                            <div class="alert alert-error">
                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_DATABASE_ERROR'); ?>
                            </div>                            
            <?php } ?>

            <?php if ($this->files_with_incorrect_permissions >3000 ) { ?>
                            <div class="alert alert-error">
                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ALERT'); ?>
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
                                                <td><span class="badge bg-success"> </span>
                                                </td>
                                                <td class="left">
                                                    <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_GREEN_COLOR'); ?>
                                                </td>
                                                <td><span class="badge bg-warning"> </span>
                                                </td>
                                                <td class="left">
                                                    <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_YELLOW_COLOR'); ?>
                                                </td>
                                                <td><span class="badge bg-danger"> </span>
                                                </td>
                                                <td class="left">
                                                    <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RED_COLOR'); ?>
                                                </td>
                                            </tr>
                                        </thead>
                                    </table>                                
                                </div>                            
                            </div>                        
                            
            <?php
            if ((!empty($this->items_permissions)) && (!$this->state->get('filter.filemanager_permissions_status')) ) {        
                ?>
                                <div id="permissions_buttons">
                                    <div class="pull-right">
                                        <button class="btn btn-success margin-right-5" id="add_exception_button" href="#">
                                            <i class="fa fa-plus"> </i>
                <?php echo Text::_('COM_SECURITYCHECKPRO_ADD_AS_EXCEPTION'); ?>
                                        </button>                                    
                                        <button class="btn btn-primary" id="repair_button" href="#">
                                            <i class="fa fa-wrench"> </i>
                <?php echo Text::_('COM_SECURITYCHECKPRO_FILE_STATUS_REPAIR'); ?>
                                        </button>
                                    </div>
                                </div>
                <?php
            } else if ($this->state->get('filter.filemanager_permissions_status') == 2 ) { ?>
                                    <div id="permissions_buttons" class="btn-toolbar">
                                        <div class="btn-group pull-right">
                                            <button class="btn btn-danger" id="delete_exception_button" href="#">
                                                <i class="icon-trash icon-white"> </i>
                <?php echo Text::_('COM_SECURITYCHECKPRO_DELETE_EXCEPTION'); ?>
                                            </button>
                                        </div>
                                </div>

            <?php } ?>

                                <div>
                                    <span class="badge analyzed-files padding-10-10-10-10"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ANALYZED_FILES');?></span>
                                </div>
                                <div class="table-responsive overflow-x-auto margin-top-30">                                    
                                    <table id="filesstatus_table" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
											<?php 
												if ($this->checkbox_position == 1) {
											?>
											<th class="center width-5">
                                                <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
                                            </th> 
											<?php 
												}
											?>
                                            <th class="center">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NAME'); ?>
                                            </th>
                                            <th class="center">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_EXTENSION'); ?>                
                                            </th>
                                            <th class="center">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_KIND'); ?>                
                                            </th>
                                            <th class="center ruta-style">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RUTA'); ?>
                                            </th>
                                            <th class="center">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TAMANNO'); ?>
                                            </th>
                                            <th class="center">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_PERMISSIONS'); ?>
                                            </th>
                                            <th class="center">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_MODIFIED'); ?>
                                            </th>
                                            <?php 
												if ($this->checkbox_position == 0) {
											?>
											<th class="center width-5">
                                                <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
                                            </th> 
											<?php 
												}
											?>       
                                        </tr>
                                    </thead>
            <?php
            $k = 0;
            if (!empty($this->items_permissions) ) {    
                foreach ($this->items_permissions as &$row) {        
                    ?>
					<?php 
					if ($this->checkbox_position == 1) {
						echo '<td class="centrado">' . HTMLHelper::_('grid.id', $k, $row['path'], '', 'filesstatus_table') . '</td>'; 
					}
					?> 
                    <td class="centrado">
                    <?php 
                    // Obtenemos la extensión del archivo
                    $last_part = explode(DIRECTORY_SEPARATOR, $row['path']);        
                    $last_part_2 = explode('.', end($last_part));
                    $name = reset($last_part_2);    
                    echo htmlspecialchars($name);     
                    ?>
                    </td>
                    <td class="centrado">
                    <?php 
                    $last_part = explode(DIRECTORY_SEPARATOR, $row['path']);
                    $extension = explode(".", end($last_part));                                                                                    
                    echo htmlspecialchars(end($extension));
                    ?>
                    </td>
                    <td class="centrado">
                    <?php echo htmlspecialchars($row['kind']); ?>
                    </td>
                    <td class="centrado malwarescan-table-info">
                    <?php echo htmlspecialchars($row['path']); ?>
                    </td>
                    <td class="centrado">
                    <?php 
                    if (file_exists($row['path']) ) {
                        echo filesize($row['path']);
                    }
                    ?>
                    </td>
                    <?php 
                    $safe = $row['safe'];
                    if ($safe == '0' ) {
                        echo "<td class=\"centrado;\"><span class=\"badge bg-danger\">";
                    } else if ($safe == '1' ) {
                        echo "<td class=\"centrado;\"><span class=\"badge bg-success\">";
                    } else if ($safe == '2' ) {
                        echo "<td class=\"centrado;\"><span class=\"badge bg-warning\">";
                    } ?>
                    <?php echo htmlspecialchars($row['permissions']); ?>
                    </td>
                    <td class="centrado">
                    <?php echo htmlspecialchars($row['last_modified']); ?>
                    </td>
                    <?php 
					if ($this->checkbox_position == 0) {
						echo '<td class="centrado">' . HTMLHelper::_('grid.id', $k, $row['path'], '', 'filesstatus_table') . '</td>'; 
					}
					?> 
                </tr>
                    <?php
                    $k = $k+1;
                }
            }
            ?>
                                    </table>
                                </div>

            <?php
            if (!empty($this->items_permissions) ) {        
                ?>
                <div>                                        
                <?php echo $this->pagination->getListFooter(); ?>
                </div>
            <?php } ?>
            </div>
        <?php } else {
			if ($this->state->get('filter.malwarescan_status') == 2 ) {
				if ($this->file_manager_include_exceptions_in_database == 0 ) { 
					echo '<div class="alert alert-info">' . Text::_('COM_SECURITYCHECKPRO_EXCEPTIONS_NOT_INCLUDED_IN_DATABASE'). '</div>';                            
				} 
			}
		}  ?>
                    </div>                
                </div>
            </div>
            
        </div>
</div>        

<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="controller" value="filemanager" />
<input type="hidden" name="table" value="permissions" />
</form>
