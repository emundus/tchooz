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
use Joomla\Filesystem\Path;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Filesintegrity\HtmlView $this */

Session::checkToken('get') or die('Invalid Token');

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

$list_group_style = 'style="width: fit-content;"';

if (empty($this->last_check_integrity) ) {
    $this->last_check_integrity = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER');
}
$files_status = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NOT_DEFINED');

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
$basemodel = $this->basemodel;
?>

<!-- Modal view file -->
<div class="modal fade" id="view_logfile" tabindex="-1" aria-labelledby="viewlogfileLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="viewlogfileLabel"><?php echo Text::_('COM_SECURITYCHECKPRO_REPAIR_VIEW_LOG_MESSAGE'); ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
      </div>
      <div class="modal-body">
        <div id="logSpinner" class="small text-muted d-none"><?php echo Text::_('JLOADING'); ?>…</div>
        <pre id="logContent"
             class="form-control bg-body-tertiary p-3"
             style="max-height:60vh; overflow:auto; white-space:pre; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;"
             aria-readonly="true"></pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
      </div>
    </div>
  </div>
</div>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&'. Session::getFormToken() .'=1');?>" method="post" class="margin-left-10 margin-right-10" name="adminForm" id="adminForm">

<?php
    // Navegación (include robusto)
    $navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
    if (is_file($navFile)) {
        require $navFile;
    }
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
            <li class="list-group-item"><span id="start_time" class="badge bg-dark"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER'); ?></span></li>
          </ul>
        </div>
        <div <?php echo $list_group_style; ?>>
          <ul class="list-group text-center">
            <li class="list-group-item active font-size-13"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_CHECK_TASK'); ?></li>
            <li class="list-group-item">
              <span id="task_status" class="badge bg-info"><?php echo $files_status; ?></span>
              <span id="task_error" class="badge bg-danger display-none">Error</span>
            </li>
          </ul>
        </div>
        <div <?php echo $list_group_style; ?>>
          <ul class="list-group text-center">
            <li class="list-group-item active font-size-13"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_HASH_ALG'); ?></li>
            <li class="list-group-item"><span id="end_time" class="badge bg-dark"><?php echo $this->hash_alg; ?></span></li>
          </ul>
        </div>
      </div>
      <div id="button_start_scan_wrap" class="card-footer">
        <button class="btn btn-primary" type="button" id="button_start_scan"><i class="fa fa-fire"></i><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_START_BUTTON'); ?></button>
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
            <span class="badge bg-info"><b><?php echo Text::_('COM_SECURITYCHECKPRO_MAX_TIME_TO_SCAN_FILE') . '</b>' . htmlspecialchars($this->last_scan_info['max_time'], ENT_QUOTES, 'UTF-8') . " " . Text::_('COM_SECURITYCHECKPRO_SECONDS'); ?></span>
          </div>
          <div class="margin-left-10">
            <span class="badge bg-info margin-bottom-20"><b><?php echo Text::_('COM_SECURITYCHECKPRO_MAX_TIME_FILE') . '</b>' . htmlspecialchars($this->last_scan_info['max_time_filename'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>				
									
          <div class="wrapper">
            <div class="pie-charts">
              <div class="pieID--micro-skills pie-chart--wrapper">
                <h2><?php echo Text::_('COM_SECURITYCHECKPRO_FILES_BY_TYPE'); ?></h2>
                <div class="pie-chart">
                  <div class="pie-chart__pie">
                    <div class="slice s0-0" style="transform: rotate(-1deg) translate3d(0px, 0px, 0px);"><span style="transform: rotate(0deg) translate3d(0px, 0px, 0px); background-color: tomato;"></span></div>
                    <div class="slice s0-1" style="transform: rotate(178deg) translate3d(0px, 0px, 0px);"><span style="transform: rotate(-126.88deg) translate3d(0px, 0px, 0px); background-color: tomato;"></span></div>
                    <div class="slice s1-0" style="transform: rotate(230.12deg) translate3d(0px, 0px, 0px);"><span style="transform: rotate(-50.12deg) translate3d(0px, 0px, 0px); background-color: forestgreen;"></span></div>
                  </div>
                  <ul class="pie-chart__legend">
                    <li style="border-color: tomato;"><em><?php echo Text::_('COM_SECURITYCHECKPRO_EXECUTABLE_FILES'); ?></em><span>
                      <?php 
                        if (!empty($this->last_scan_info)) {
                          echo htmlspecialchars($this->last_scan_info['executable_files'], ENT_QUOTES, 'UTF-8');
                        } else {
                          echo 1;
                        }
                      ?>
                    </span></li>
                    <li style="border-color: forestgreen;"><em><?php echo Text::_('COM_SECURITYCHECKPRO_NON_EXECUTABLE_FILES'); ?></em><span>
                      <?php 
                        if (!empty($this->last_scan_info)) {
                          echo htmlspecialchars($this->last_scan_info['non_executable_files'], ENT_QUOTES, 'UTF-8');
                        } else {
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
          <button type="button" class="btn" data-bs-dismiss="modal" aria-hidden="true"><?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button> 
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
            <li class="list-group-item"><span class="badge bg-dark"><?php echo $this->last_check_integrity; ?></span></li>
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
            <li class="list-group-item"><span class="badge bg-dark"><?php echo $this->files_scanned_integrity; ?></span></li>
            <?php if ((!empty($this->time_taken)) && (is_array($this->last_scan_info)) ) : ?>
              <a href="#last_scan_info_modal" role="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?></a>
            <?php endif; ?>
          </ul>
        </div>
        <div <?php echo $list_group_style; ?>>
          <ul class="list-group text-center">
            <li class="list-group-item text-white bg-success font-size-13"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_FILES_MODIFIED'); ?></li>
            <li class="list-group-item">
              <span class="badge bg-dark"><?php echo $this->files_with_bad_integrity; ?></span>
            </li>
          </ul>
        </div>
      </div>
      <div id="button_show_log" class="card-footer">    
        <?php if (!empty($this->log_filename)) : ?>
          <button type="button"
                  class="btn btn-success js-view-log"
                  data-logfilename="<?php echo htmlspecialchars($this->log_filename, ENT_QUOTES, 'UTF-8'); ?>"
                  data-url="index.php?option=com_securitycheckpro&task=filemanager.fetchLog&format=json&<?php echo Session::getFormToken(); ?>=1"
                  data-bs-toggle="modal"
                  data-bs-target="#view_logfile"><i class="fa fa-eye"></i>
            <?php echo Text::_('COM_SECURITYCHECKPRO_VIEW_LOG'); ?>
          </button>
        <?php endif; ?>
      </div>
    </div>                    
  </div>
                
  <div id="scandata" class="col-lg-12 margin-top-30">
    <div class="card mb-3">                        
      <div class="card-body margin-left-10">
        <div id="container_repair">
          <div id="log-container_remember_text" class="centrado margen texto_14"></div>
          <div id="div_view_log_button" class="buttonwrapper"></div>
          <div id="log-container_header" class="centrado margen texto_20"></div>
        </div>
                            
        <div id="error_message_container" class="securitycheck-bootstrap centrado margen-container">
          <div id="error_message"></div>    
        </div>

        <div id="error_button" class="securitycheck-bootstrap centrado margen-container"></div>
                            
        <div id="memory_limit_message" class="centrado margen-loading">
          <?php 
            // Extrae memory_limit (solo número)
            $memory_limit = ini_get('memory_limit');
            $memory_limit = (int) substr($memory_limit, 0, -1);
            if (($memory_limit <= 128) && ($this->last_check_integrity == Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER')) ) {
              echo '<div class="alert alert-warning">' . Text::_('COM_SECURITYCHECKPRO_MEMORY_LIMIT_LOW') . '</div>';
            }
          ?>
        </div>
							
        <div id="scan_only_executable_message" class="centrado margen-loading">
          <?php 
            if ($this->scan_executables_only ) {
              echo '<div class="alert alert-warning">' . Text::_('COM_SECURITYCHECKPRO_SCAN_ONLY_EXECUTABLES_WARNING') . '</div>';
            }
          ?>
        </div>

        <div id="completed_message2" class="centrado margen-loading color_verde"></div>
        <div id="warning_message2" class="centrado margen-loading"></div>
                                                                            
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
              <?php
                echo $basemodel->renderSelect(
                  'filter_fileintegrity_status',
                  [
                    ['value'=>'0','text'=>'COM_SECURITYCHECKPRO_FILEINTEGRITY_TITLE_COMPROMISED'],
                    ['value'=>'1','text'=>'COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_OK'],
                    ['value'=>'2','text'=>'COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_EXCEPTIONS']
                  ],
                  ['class' => 'form-select','onchange' => 'this.form.submit()'],
                  $this->state->get('filter.fileintegrity_status'),
                  false,
                  true
                );

                if (!empty($this->items)) {
                  echo $this->pagination->getLimitBox();
                } 
              ?> 
            </div>
          </div>						
                                            
          <?php if (!$this->items == null) : ?>
            <?php if (($this->files_with_bad_integrity > 0 ) && ( empty($this->items) ) ) : ?>
              <div class="alert alert-danger">
                <?php echo Text::_('COM_SECURITYCHECKPRO_EMPTY_ITEMS'); ?>
              </div>                            
            <?php endif; ?>

            <?php if ($this->database_error == "DATABASE_ERROR" ) : ?>
              <div class="alert alert-danger">
                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_DATABASE_ERROR'); ?>
              </div>                            
            <?php endif; ?>

            <?php if ($this->files_with_bad_integrity > 3000 ) : ?>
              <div class="alert alert-danger">
                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ALERT') . "."; ?>
                <br/>
                <?php echo Text::_('COM_SECURITYCHECKPRO_EMAIL_ALERT_BODY_ALERT'); ?>                                
              </div>                            
            <?php endif; ?>

            <?php if ($this->show_all == 1 ) : ?>
              <div class="alert alert-info">
                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_INFO'); ?>
              </div>                            
            <?php endif; ?>
                            
            <div class="card margin-top-30 margin-bottom-20">
              <div class="card-header text-center">
                <?php echo Text::_('COM_SECURITYCHECKPRO_COLOR_CODE'); ?>
              </div>
              <div class="card-block">                                    
                <table class="table table-borderless margin-top-30">
                  <thead>
                    <tr>
                      <td><span class="badge bg-success"> </span></td>
                      <td class="left">
                        <?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_GREEN_COLOR'); ?>
                      </td>
                      <td><span class="badge bg-warning"> </span></td>
                      <td class="left">
                        <?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_YELLOW_COLOR'); ?>
                      </td>
                      <td><span class="badge bg-danger"> </span></td>
                      <td class="left">
                        <?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_RED_COLOR'); ?>
                      </td>
                    </tr>
                  </thead>
                </table>                                
              </div>                            
            </div>                        
                            
            <?php if ((!empty($this->items)) && (!$this->state->get('filter.fileintegrity_status')) ) : ?>
              <div id="permissions_buttons">
                <div class="pull-right">
                  <button class="btn btn-success margin-right-5" id="add_exception_button" href="#">
                    <i class="fa fa-plus"> </i>
                    <?php echo Text::_('COM_SECURITYCHECKPRO_ADD_AS_EXCEPTION'); ?>
                  </button>                                        
                </div>
              </div>
            <?php elseif ($this->state->get('filter.fileintegrity_status') == 2 ) : ?>
              <div id="permissions_buttons">
                <div class="btn-group pull-right">
                  <button class="btn btn-danger" id="delete_exception_button" href="#">
                    <i class="fa fa-trash"> </i>
                    <?php echo Text::_('COM_SECURITYCHECKPRO_DELETE_EXCEPTION'); ?>
                  </button>
                </div>
              </div>
            <?php endif; ?>

            <div>
              <span class="badge integrity-files padding-10-10-10-10"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_CHECKED_FILES');?></span>
              <?php
              if (!empty($this->items) ) {
                $extensions_updated_tooltip = Text::_('COM_SECURITYCHECKPRO_EXTENSIONS_UPDATED_INSTALLED_DESC') . PHP_EOL;
                if (is_array($this->installs)) {
                  $extensions_updated = count($this->installs);
                  foreach ($this->installs as $extension) {
                    if (is_array($extension)) {
                      $name = isset($extension['name']) ? (string)$extension['name'] : '';
                      $type = isset($extension['type']) ? (string)$extension['type'] : '';
                      if ($name !== '' && $type !== '') {
                        $extensions_updated_tooltip .= PHP_EOL . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . " (" . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . ")" . PHP_EOL;
                      }
                    }
                  }
                } else {
                  $extensions_updated = 0;
                }
              ?>
              <span id="extensions_updated_tooltip"
                    class="badge extensions-updated padding-10-10-10-10"
                    data-html="true"
                    data-bs-toggle="tooltip"
                    title="<?php echo htmlspecialchars($extensions_updated_tooltip, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo Text::_('COM_SECURITYCHECKPRO_EXTENSIONS_UPDATED_INSTALLED') . (int) $extensions_updated; ?>
              </span>
              <?php } ?>
            </div>
                                
            <div class="table-responsive overflow-x-auto margin-top-30">
              <table id="filesintegritystatus_table" class="table table-bordered table-hover">
                <thead>
                  <tr>
                    <?php if ($this->checkbox_position == 1): ?>
                      <th class="center width-5">
                        <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
                      </th> 
                    <?php endif; ?>
                    <th class="center">
                      <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NAME'); ?>
                    </th>
                    <th class="center">
                      <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RUTA'); ?>                
                    </th>
                    <th class="center">
                      <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TAMANNO'); ?>                
                    </th>
                    <th class="center">
                      <?php echo Text::_('Info'); ?>            
                    </th>
                    <th class="center">
                      <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_MODIFIED'); ?>
                    </th>
                    <?php if ($this->checkbox_position == 0): ?>
                      <th class="center width-5">
                        <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
                      </th> 
                    <?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                <?php
                $k = 0;
                if (!empty($this->items)) :
                  foreach ($this->items as &$row) :
                    $safe_integrity = isset($row['safe_integrity']) ? (string)$row['safe_integrity'] : '';
                    $path = isset($row['path']) ? (string)$row['path'] : '';
                    $notes = isset($row['notes']) ? (string)$row['notes'] : '';
                    $isDir = is_dir($path);
                ?>
                  <tr>
                    <?php if ($this->checkbox_position == 1): ?>
                      <td class="centrado">
                        <?php echo HTMLHelper::_('grid.id', $k, $path, '', 'filesintegritystatus_table'); ?>
                      </td>
                    <?php endif; ?>

                    <td class="centrado">
                      <?php
                        if (!$isDir) {
                          echo htmlspecialchars(basename($path), ENT_QUOTES, 'UTF-8');
                        } else {
                          echo '';
                        }
                      ?>
                    </td>

                    <td class="centrado malwarescan-table-info">
                      <?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>
                    </td>

                    <td class="centrado">
                      <?php
                        if (!$isDir && file_exists($path)) {
                          $size = @filesize($path);
                          if ($size === false) {
                            echo 'N/A';
                          } else {
                            // tamaño legible
                            $units = ['B','KB','MB','GB','TB'];
                            $i = 0;
                            while ($size >= 1024 && $i < count($units) - 1) {
                              $size /= 1024;
                              $i++;
                            }
                            echo htmlspecialchars(number_format($size, ($i === 0 ? 0 : 2)) . ' ' . $units[$i], ENT_QUOTES, 'UTF-8');
                          }
                        } else {
                          echo 'Not calculated';
                        }
                      ?>
                    </td>

                    <td class="centrado">
                      <?php
                        $badgeClass = 'bg-warning';
                        if ($safe_integrity === '0') $badgeClass = 'bg-danger';
                        elseif ($safe_integrity === '1') $badgeClass = 'bg-success';
                      ?>
                      <span class="badge <?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars($notes, ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                    </td>

                    <td class="centrado">
                      <?php
                        $wrapOpen = ($safe_integrity === '0') ? '<span class="badge bg-danger">' : '';
                        $wrapClose = ($safe_integrity === '0') ? '</span>' : '';
                        if (file_exists($path)) {
                          $mtime = @filemtime($path);
                          echo $wrapOpen
                             . htmlspecialchars($mtime ? date('Y-m-d H:i:s', $mtime) : 'N/A', ENT_QUOTES, 'UTF-8')
                             . $wrapClose;
                        } else {
                          echo $wrapOpen . 'N/A' . $wrapClose;
                        }
                      ?>
                    </td>

                    <?php if ($this->checkbox_position == 0): ?>
                      <td class="centrado">
                        <?php echo HTMLHelper::_('grid.id', $k, $path, '', 'filesintegritystatus_table'); ?>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php
                    $k++;
                  endforeach;
                endif;
                ?>
                </tbody>
              </table>
            </div>

            <?php if (!empty($this->items)) : ?>
              <div class="margen">
                <div>
                  <?php echo $this->pagination->getListFooter(); ?>
                </div>
              </div>
            <?php endif; ?>

          <?php else : ?>
            <?php
              if ($this->state->get('filter.malwarescan_status') == 2) {
                if ($this->file_manager_include_exceptions_in_database == 0) { 
                  echo '<div class="alert alert-info">' . Text::_('COM_SECURITYCHECKPRO_EXCEPTIONS_NOT_INCLUDED_IN_DATABASE'). '</div>';                            
                } 
              }
            ?>
          <?php endif; ?>
        </div>        
      </div>
    </div>
  </div>
</div>
            
<input type="hidden" name="controller" value="filemanager" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="table" value="integrity" />
<?php echo HTMLHelper::_('form.token'); ?>
</form>
