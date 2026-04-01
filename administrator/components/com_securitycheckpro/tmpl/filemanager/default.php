<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Filesystem\Path;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use Joomla\CMS\Application\CMSApplication;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Filemanager\HtmlView $this */

Session::checkToken('get') or die('Invalid Token');

HTMLHelper::_('behavior.core'); // Carga assets core (necesario para checkAll, etc.)

$list_group_style = 'style="width: fit-content;"';

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
$basemodel = $this->basemodel;

if (empty($this->last_check)) {
    $this->last_check = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER');
}
$files_status = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NOT_DEFINED');

// Obtenemos el tipo de servidor web
/** @var \Joomla\CMS\Application\CMSApplication $app */
$app    = Factory::getApplication();
$server = $app->getUserState('server', 'apache');

if (stripos((string) $server, 'iis') !== false) : ?>
	<div class="alert alert-info">
		<?php echo Text::_('COM_SECURITYCHECKPRO_IIS_SERVER'); ?>
	</div>
<?php endif; ?>

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

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&' . Session::getFormToken() . '=1'); ?>"
	  method="post"
	  class="margin-left-10 margin-right-10"
	  name="adminForm"
	  id="adminForm">

	<?php
    // Navegación (include robusto)
    $navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
    if (is_file($navFile)) {
        require $navFile;
    }
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
							<li class="list-group-item active">
								<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_CHECK_STARTTIME'); ?>
							</li>
							<li class="list-group-item">
								<span id="start_time" class="badge bg-dark">
									<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER'); ?>
								</span>
							</li>
						</ul>
					</div>
					<div <?php echo $list_group_style; ?>>
						<ul class="list-group text-center">
							<li class="list-group-item active">
								<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_CHECK_TASK'); ?>
							</li>
							<li class="list-group-item">
								<span id="task_status" class="badge bg-info"><?php echo $files_status; ?></span>
								<span id="task_error" class="badge bg-danger d-none">Error</span>
							</li>
						</ul>
					</div>
				</div>
				<div id="button_start_scan" class="card-footer">
					<button class="btn btn-primary" type="button">
						<i class="fa fa-fire" aria-hidden="true"></i>
						<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_START_BUTTON'); ?>
					</button>
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
							<li class="list-group-item text-white bg-success">
								<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_CHECK'); ?>
							</li>
							<li class="list-group-item">
								<span class="badge bg-dark"><?php echo $this->escape($this->last_check); ?></span>
							</li>
						</ul>
					</div>
					<div <?php echo $list_group_style; ?>>
						<ul class="list-group text-center">
							<li class="list-group-item text-white bg-success">
								<?php echo Text::_('COM_SECURITYCHECKPRO_TIME_TAKEN'); ?>
							</li>
							<li class="list-group-item">
								<span class="badge bg-dark"><?php echo $this->escape($this->time_taken); ?></span>
							</li>
						</ul>
					</div>
					<div <?php echo $list_group_style; ?>>
						<ul class="list-group text-center">
							<li class="list-group-item text-white bg-success">
								<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_FILES_SCANNED'); ?>
							</li>
							<li class="list-group-item">
								<span class="badge bg-dark"><?php echo (int) $this->files_scanned; ?></span>
							</li>
						</ul>
					</div>
					<div <?php echo $list_group_style; ?>>
						<ul class="list-group text-center">
							<li class="list-group-item text-white bg-success font-size-13">
								<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_FILES_FOLDERS_INCORRECT_PERMISSIONS'); ?>
							</li>
							<li class="list-group-item">
								<span class="badge bg-dark"><?php echo (int) $this->files_with_incorrect_permissions; ?></span>
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
								data-bs-target="#view_logfile">
							<i class="fa fa-eye" aria-hidden="true"></i>
							<?php echo Text::_('COM_SECURITYCHECKPRO_VIEW_LOG'); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div id="scandata" class="col-lg-12 margin-top-10">
			<div class="card mb-3">
				<div class="card-body margin-left-10">
					<div id="container_repair">
						<div id="log-container_remember_text" class="centrado margen texto_14"></div>
						<div id="div_view_log_button" class="buttonwrapper"></div>
						<div id="log-container_header" class="centrado margen texto_20"></div>
						<div id="log-text" class="overflow-y-scroll height-150">
							<?php
							if (!empty($this->repair_log)) {
								// El contenido viene saneado desde el controlador.
								echo $this->repair_log;
							}
							?>
						</div>
					</div>

					<div id="error_message_container" class="securitycheck-bootstrap centrado margen-container">
						<div id="error_message"></div>
					</div>

					<div id="error_button" class="securitycheck-bootstrap centrado margen-container"></div>

					<div id="memory_limit_message" class="centrado margen-loading">
						<?php
						// Mostramos aviso sólo si no hay escaneos previos y el memory_limit es <= 128M
						$memory_limit_raw = ini_get('memory_limit'); // puede ser "128M" o "-1"
						$memory_limit_val = -1;
						if (is_string($memory_limit_raw) && $memory_limit_raw !== '-1') {
							// Extraemos parte numérica
							preg_match('/(\d+)/', $memory_limit_raw, $mm);
							$memory_limit_val = isset($mm[1]) ? (int) $mm[1] : -1;							
						}
						if ($memory_limit_val !== -1 && $memory_limit_val <= 128 && $this->last_check === Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER')) {
							echo '<div class="alert alert-warning">' . Text::_('COM_SECURITYCHECKPRO_MEMORY_LIMIT_LOW') . '</div>';
						}
						?>
					</div>

					<div id="scan_only_executable_message" class="centrado margen-loading">
						<?php
						if (!empty($this->scan_executables_only)) {
							echo '<div class="alert alert-warning">' . Text::_('COM_SECURITYCHECKPRO_SCAN_ONLY_EXECUTABLES_WARNING') . '</div>';
						}
						?>
					</div>

					<div id="completed_message2" class="centrado margen-loading color_verde"></div>
					<div id="warning_message2" class="centrado margen-loading"></div>

					<div id="backup-progress" class="progress">
						<div id="bar" class="progress-bar bg-success width-0" role="progressbar" aria-valuenow="0"
							 aria-valuemin="0" aria-valuemax="100"></div>
					</div>

					<div id="container_resultado">
						<div id="filter-bar" class="filter-search-bar btn-group">
							<div class="input-group">
								<input type="text"
									   class="form-control"
									   name="filter_filemanager_search"
									   placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>"
									   id="filter_filemanager_search"
									   value="<?php echo $this->escape((string) $this->state->get('filter.filemanager_search')); ?>"
									   title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />
								<span class="filter-search-bar__label visually-hidden">
									<label id="filter_search-lbl" for="filter_filemanager_search">Filter:</label>
								</span>
								<button type="submit" class="filter-search-bar__button btn btn-primary" aria-label="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
									<span class="filter-search-bar__button-icon icon-search" aria-hidden="true"></span>
								</button>
								<button class="btn btn-dark" type="button" id="filter_filemanager_search_clear_button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
									<i class="icon-remove" aria-hidden="true"></i>
								</button>
							</div>

							<div class="input-group pull-left margin-left-10">
								<?php
								echo $basemodel->renderSelect(
									'filter_filemanager_kind',
									[
										Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FILE')   => 'COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FILE',
										Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FOLDER') => 'COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FOLDER',
									],
									['class' => 'form-select', 'onchange' => 'this.form.submit()'],
									$this->state->get('filter.filemanager_kind'),
									false,
									true
								);

								echo $basemodel->renderSelect(
									'filter_filemanager_permissions_status',
									[
										['value' => '0', 'text' => 'COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_WRONG'],
										['value' => '1', 'text' => 'COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_OK'],
										['value' => '2', 'text' => 'COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_EXCEPTIONS'],
									],
									['class' => 'form-select', 'onchange' => 'this.form.submit()'],
									$this->state->get('filter.filemanager_permissions_status'),
									false,
									true
								);

								if (!empty($this->items_permissions)) {
									echo $this->pagination->getLimitBox();
								}
								?>
							</div>
						</div>

						<?php if (!$this->items_permissions == null) : ?>

							<?php if (($this->files_with_incorrect_permissions > 0) && empty($this->items_permissions)) : ?>
								<div class="alert alert-danger">
									<?php echo Text::_('COM_SECURITYCHECKPRO_EMPTY_ITEMS'); ?>
								</div>
							<?php endif; ?>

							<?php if (!empty($this->database_error) && $this->database_error === 'DATABASE_ERROR') : ?>
								<div class="alert alert-danger">
									<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_DATABASE_ERROR'); ?>
								</div>
							<?php endif; ?>

							<?php if ((int) $this->files_with_incorrect_permissions > 3000) : ?>
								<div class="alert alert-danger">
									<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ALERT'); ?>
								</div>
							<?php endif; ?>

							<?php if ((int) $this->show_all === 1) : ?>
								<div class="alert alert-info">
									<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_INFO'); ?>
								</div>
							<?php endif; ?>

							<div class="card margin-top-30 margin-bottom-20">
								<div class="card-header text-center">
									<?php echo Text::_('COM_SECURITYCHECKPRO_COLOR_CODE'); ?>
								</div>
								<div class="card-body">
									<table class="table table-borderless margin-top-30">
										<thead>
										<tr>
											<td><span class="badge bg-success">&nbsp;</span></td>
											<td class="text-start"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_GREEN_COLOR'); ?></td>
											<td><span class="badge bg-warning">&nbsp;</span></td>
											<td class="text-start"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_YELLOW_COLOR'); ?></td>
											<td><span class="badge bg-danger">&nbsp;</span></td>
											<td class="text-start"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RED_COLOR'); ?></td>
										</tr>
										</thead>
									</table>
								</div>
							</div>

							<?php if (!empty($this->items_permissions) && !$this->state->get('filter.filemanager_permissions_status')) : ?>
								<div id="permissions_buttons">
									<div class="pull-right">
										<button class="btn btn-success margin-right-5" id="add_exception_button" type="button">
											<i class="fa fa-plus" aria-hidden="true"></i>
											<?php echo Text::_('COM_SECURITYCHECKPRO_ADD_AS_EXCEPTION'); ?>
										</button>
										<button class="btn btn-primary" id="repair_button" type="button">
											<i class="fa fa-wrench" aria-hidden="true"></i>
											<?php echo Text::_('COM_SECURITYCHECKPRO_FILE_STATUS_REPAIR'); ?>
										</button>
									</div>
								</div>
							<?php elseif ((string) $this->state->get('filter.filemanager_permissions_status') === '2') : ?>
								<div id="permissions_buttons" class="btn-toolbar">
									<div class="btn-group pull-right">
										<button class="btn btn-danger" id="delete_exception_button" type="button">
											<i class="icon-trash" aria-hidden="true"></i>
											<?php echo Text::_('COM_SECURITYCHECKPRO_DELETE_EXCEPTION'); ?>
										</button>
									</div>
								</div>
							<?php endif; ?>

							<div>
								<span class="badge analyzed-files padding-10-10-10-10">
									<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ANALYZED_FILES'); ?>
								</span>
							</div>

							<div class="table-responsive overflow-x-auto margin-top-30">
								<table id="filesstatus_table" class="table table-bordered table-hover">
									<thead>
									<tr>
										<?php if ((int) $this->checkbox_position === 1) : ?>
											<th class="text-center width-5">
												<input type="checkbox"
													 class="form-check-input"
													 name="checkall-toggle"
													 title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>"
													 onclick="Joomla.checkAll(this);" />
											</th>
										<?php endif; ?>
										<th class="center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NAME'); ?></th>
										<th class="center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_EXTENSION'); ?></th>
										<th class="center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_KIND'); ?></th>
										<th class="center ruta-style"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RUTA'); ?></th>
										<th class="center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TAMANNO'); ?></th>
										<th class="center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_PERMISSIONS'); ?></th>
										<th class="center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_MODIFIED'); ?></th>
										<?php if ((int) $this->checkbox_position === 0) : ?>
											<th class="text-center width-5">
												<input type="checkbox"
													 class="form-check-input"
													 name="checkall-toggle"
													 title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>"
													 onclick="Joomla.checkAll(this);" />
											</th>
										<?php endif; ?>
									</tr>
									</thead>
									<tbody>
									<?php
									$k = 0;
									if (!empty($this->items_permissions)) :
										foreach ($this->items_permissions as $row) :
											// Partes del nombre / extensión
											$path = (string) $row['path'];
											$basename = basename($path);
											$nameParts = explode('.', $basename);
											$name = $nameParts[0] ?? $basename;
											$extension = count($nameParts) > 1 ? end($nameParts) : '';
											$safe = (string) $row['safe'];
											$permClass = $safe === '0' ? 'danger' : ($safe === '1' ? 'success' : 'warning');
											$fileSize = '';
											if (is_file($path) && is_readable($path)) {
												$fileSize = (string) @filesize($path);
											}
											?>
											<tr>
												<?php if ((int) $this->checkbox_position === 1) : ?>
													<td class="centrado">
														<?php
														// grid.id: ($i, $id, $checkedOut=false, $name='cid', $prefix='')
														echo HTMLHelper::_('grid.id', $k, $path, '', 'filesstatus_table'); 
														?>
													</td>
												<?php endif; ?>

												<td class="centrado"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
												<td class="centrado"><?php echo htmlspecialchars($extension, ENT_QUOTES, 'UTF-8'); ?></td>
												<td class="centrado"><?php echo htmlspecialchars((string) $row['kind'], ENT_QUOTES, 'UTF-8'); ?></td>
												<td class="centrado malwarescan-table-info"><?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?></td>
												<td class="centrado"><?php echo htmlspecialchars($fileSize, ENT_QUOTES, 'UTF-8'); ?></td>
												<td class="centrado">
													<span class="badge bg-<?php echo $permClass; ?>">
														<?php echo htmlspecialchars((string) $row['permissions'], ENT_QUOTES, 'UTF-8'); ?>
													</span>
												</td>
												<td class="centrado"><?php echo htmlspecialchars((string) $row['last_modified'], ENT_QUOTES, 'UTF-8'); ?></td>

												<?php if ((int) $this->checkbox_position === 0) : ?>
													<td class="centrado">
														<?php echo HTMLHelper::_('grid.id', $k, $path, '', 'filesstatus_table'); ?>
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

							<?php if (!empty($this->items_permissions)) : ?>
								<div>
									<?php echo $this->pagination->getListFooter(); ?>
								</div>
							<?php endif; ?>

						<?php else : ?>
							<?php
							if ((string) $this->state->get('filter.malwarescan_status') === '2') {
								if ((int) $this->file_manager_include_exceptions_in_database === 0) {
									echo '<div class="alert alert-info">' . Text::_('COM_SECURITYCHECKPRO_EXCEPTIONS_NOT_INCLUDED_IN_DATABASE') . '</div>';
								}
							}
							?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

	</div> <!-- /.row -->

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="controller" value="filemanager" />
	<input type="hidden" name="table" value="permissions" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>