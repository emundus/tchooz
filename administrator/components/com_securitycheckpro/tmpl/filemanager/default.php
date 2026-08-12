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

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
$basemodel = $this->basemodel;

$hasRunBefore = !empty($this->last_check);
$lastCheckRelative = $hasRunBefore
	? $basemodel->relativeTime($this->last_check)
	: Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER');

switch ($this->scan_state) {
	case 'IN_PROGRESS':
		$scanStateLabel = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS');
		$scanStateClass = 'bg-info';
		break;
	case 'ERROR':
	case 'DATABASE_ERROR':
		$scanStateLabel = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ERROR');
		$scanStateClass = 'bg-danger';
		break;
	default:
		$scanStateLabel = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IDLE');
		$scanStateClass = 'bg-secondary';
}

// Obtenemos el tipo de servidor web
/** @var \Joomla\CMS\Application\CMSApplication $app */
$app    = Factory::getApplication();
$server = $app->getUserState('server', 'apache');
?>

<?php if (stripos((string) $server, 'iis') !== false) : ?>
	<div class="alert alert-info py-2 px-3 small">
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
				<a id="logDownloadLink" class="btn btn-outline-primary" href="#" download>
					<i class="fa fa-download" aria-hidden="true"></i> <?php echo Text::_('COM_SECURITYCHECKPRO_DOWNLOAD_LOG'); ?>
				</a>
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

	<!-- Action bar -->
	<div class="scp-actionbar">
		<div>
			<p class="scp-actionbar__title">
				<i class="fa fa-folder-open" aria-hidden="true"></i>
				<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_FILE_MANAGER_TEXT'); ?>
			</p>
			<p class="scp-actionbar__subtitle">
				<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_CHECK'); ?>
				&middot; <?php echo $this->escape($lastCheckRelative); ?>
				&middot;
				<span id="task_status"><span class="badge <?php echo $scanStateClass; ?>"><?php echo $scanStateLabel; ?></span></span>
				<span id="task_error" class="badge bg-danger d-none"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ERROR'); ?></span>
			</p>
		</div>
		<div class="scp-actionbar__actions">
			<?php if (!empty($this->log_filename) && $this->scan_state !== 'IN_PROGRESS') : ?>
				<button type="button"
						id="view_log_button"
						class="btn btn-outline-secondary js-view-log"
						data-logfilename="<?php echo htmlspecialchars($this->log_filename, ENT_QUOTES, 'UTF-8'); ?>"
						data-url="index.php?option=com_securitycheckpro&task=filemanager.fetchLog&format=json&<?php echo Session::getFormToken(); ?>=1"
						data-bs-toggle="modal"
						data-bs-target="#view_logfile">
					<i class="fa fa-eye" aria-hidden="true"></i> <?php echo Text::_('COM_SECURITYCHECKPRO_VIEW_LOG'); ?>
				</button>
			<?php endif; ?>
			<a class="btn btn-outline-secondary"
			   href="index.php?option=com_config&view=component&component=com_securitycheckpro&path=&return=<?php echo base64_encode(Uri::getInstance()->toString()); ?>">
				<i class="fa fa-cog" aria-hidden="true"></i> <?php echo Text::_('COM_SECURITYCHECKPRO_MALWARESCAN_CONFIGURE'); ?>
			</a>
			<div id="button_start_scan">
				<button class="btn btn-primary" type="button">
					<i class="fa fa-play me-1" aria-hidden="true"></i>
					<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_START_BUTTON'); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Config strip -->
	<div class="mb-3 d-flex flex-wrap gap-2">
		<span class="scp-chip scp-chip--outline" data-bs-toggle="tooltip" title="<?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_CONFIG_FILE_SCAN_EXECUTABLES_ONLY_DESCRIPTION')); ?>">
			<i class="fa fa-bolt" aria-hidden="true"></i>
			<?php echo Text::_('COM_SECURITYCHECKPRO_CONFIG_FILE_SCAN_EXECUTABLES_ONLY_LABEL'); ?>:
			<?php echo $this->scan_executables_only ? Text::_('COM_SECURITYCHECKPRO_YES') : Text::_('COM_SECURITYCHECKPRO_NO'); ?>
		</span>
	</div>

	<!-- Scan feedback -->
	<div id="container_repair">
		<?php if (!empty($this->repair_launched) && $this->repair_log !== '') : ?>
			<div class="card shadow-soft mb-3">
				<div class="card-body py-2 px-3 d-flex flex-wrap align-items-center gap-2">
					<i class="fa fa-check-circle text-success" aria-hidden="true"></i>
					<span class="fw-semibold"><?php echo Text::_('COM_SECURITYCHECKPRO_REPAIR_VIEW_LOG_HEADER'); ?></span>

					<?php if ($this->repair_ok_count > 0) : ?>
						<span class="badge bg-success"><?php echo Text::sprintf('COM_SECURITYCHECKPRO_REPAIR_SUMMARY_OK', $this->repair_ok_count); ?></span>
					<?php endif; ?>
					<?php if ($this->repair_warning_count > 0) : ?>
						<span class="badge bg-warning"><?php echo Text::sprintf('COM_SECURITYCHECKPRO_REPAIR_SUMMARY_WARNING', $this->repair_warning_count); ?></span>
					<?php endif; ?>
					<?php if ($this->repair_error_count > 0) : ?>
						<span class="badge bg-danger"><?php echo Text::sprintf('COM_SECURITYCHECKPRO_REPAIR_SUMMARY_ERROR', $this->repair_error_count); ?></span>
					<?php endif; ?>

					<button class="btn btn-link btn-sm p-0 ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#repair_log_details" aria-expanded="false" aria-controls="repair_log_details">
						<?php echo Text::_('COM_SECURITYCHECKPRO_REPAIR_VIEW_LOG_MESSAGE'); ?>
					</button>
				</div>
				<div class="collapse" id="repair_log_details">
					<div id="log-text" class="card-body border-top overflow-y-scroll height-150 bg-body-tertiary">
						<?php
						// El contenido viene saneado desde el modelo.
						echo $this->repair_log;
						?>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<div id="error_message"></div>
	<div id="error_button"></div>

	<div id="memory_limit_message" class="mb-2">
		<?php
		// Mostramos aviso sólo si no hay escaneos previos y el memory_limit es <= 128M
		$memory_limit_raw = ini_get('memory_limit'); // puede ser "128M" o "-1"
		$memory_limit_val = -1;
		if ($memory_limit_raw !== '-1') {
			preg_match('/(\d+)/', $memory_limit_raw, $mm);
			$memory_limit_val = isset($mm[1]) ? (int) $mm[1] : -1;
		}
		if ($memory_limit_val !== -1 && $memory_limit_val <= 128 && !$hasRunBefore) {
			echo '<span class="badge bg-warning">' . Text::_('COM_SECURITYCHECKPRO_MEMORY_LIMIT_LOW') . '</span>';
		}
		?>
	</div>

	<div id="scan_only_executable_message" class="mb-2">
		<?php if (!empty($this->scan_executables_only)) : ?>
			<span class="badge bg-warning"><?php echo Text::_('COM_SECURITYCHECKPRO_SCAN_ONLY_EXECUTABLES_WARNING'); ?></span>
		<?php endif; ?>
	</div>

	<div id="completed_message2" class="text-success mb-2"></div>
	<div id="warning_message2" class="mb-2"></div>

	<!-- Metrics row -->
	<?php if (!$hasRunBefore) : ?>
		<div class="card shadow-soft text-center mb-3">
			<div class="card-body py-4">
				<i class="fa fa-shield-alt fa-2x text-muted mb-2" aria-hidden="true"></i>
				<p class="fw-semibold mb-1"><?php echo Text::_('COM_SECURITYCHECKPRO_MALWARESCAN_RUN_FIRST_SCAN'); ?></p>
				<p class="text-muted small mb-0"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RUN_FIRST_SCAN_DESC'); ?></p>
			</div>
		</div>
	<?php else : ?>
		<div class="scp-grid scp-grid--3 mb-3">
			<div class="card shadow-soft">
				<div class="card-body text-center">
					<div class="text-muted small"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_FILES_SCANNED'); ?></div>
					<div class="fs-4 fw-bold"><?php echo (int) $this->files_scanned; ?></div>
				</div>
			</div>
			<div class="card shadow-soft">
				<div class="card-body text-center">
					<div class="text-muted small"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_FILES_FOLDERS_INCORRECT_PERMISSIONS'); ?></div>
					<div class="fs-4 fw-bold <?php echo $this->files_with_incorrect_permissions > 0 ? 'text-danger' : ''; ?>"><?php echo (int) $this->files_with_incorrect_permissions; ?></div>
				</div>
			</div>
			<div class="card shadow-soft">
				<div class="card-body text-center">
					<div class="text-muted small"><?php echo Text::_('COM_SECURITYCHECKPRO_TIME_TAKEN'); ?></div>
					<div class="fs-4 fw-bold"><?php echo $this->escape($this->time_taken); ?></div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!-- Results -->
	<div class="card shadow-soft mb-3">
		<div class="card-header"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ANALYZED_FILES'); ?></div>
		<div class="card-body" id="container_resultado">

			<div class="d-flex flex-wrap gap-2 mb-3 align-items-end">
				<div class="input-group" style="max-width: 22rem;">
					<label for="filter_filemanager_search" class="visually-hidden"><?php echo Text::_('JSEARCH_FILTER_LABEL'); ?></label>
					<input type="text"
						   class="form-control"
						   name="filter_filemanager_search"
						   placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>"
						   id="filter_filemanager_search"
						   value="<?php echo $this->escape((string) $this->state->get('filter.filemanager_search')); ?>"
						   title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />
					<button type="submit" class="btn btn-primary" aria-label="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
						<span class="icon-search" aria-hidden="true"></span>
					</button>
					<button class="btn btn-outline-secondary" type="button" id="filter_filemanager_search_clear_button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>" aria-label="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
						<i class="icon-remove" aria-hidden="true"></i>
					</button>
				</div>

				<div class="scp-filter-row ms-auto d-flex align-items-end gap-2 flex-wrap">
					<?php
					echo $basemodel->renderSelect(
						'filter_filemanager_kind',
						[
							Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FILE')   => 'COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FILE',
							Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FOLDER') => 'COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FOLDER',
						],
						['class' => 'form-select', 'style' => 'min-width: 9rem;', 'onchange' => 'this.form.submit()'],
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
						['class' => 'form-select', 'style' => 'min-width: 12rem;', 'onchange' => 'this.form.submit()'],
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

			<?php if (!empty($this->items_permissions)) : ?>

				<?php if (($this->files_with_incorrect_permissions > 0) && empty($this->items_permissions)) : ?>
					<div class="alert alert-danger py-2 px-3 small mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_EMPTY_ITEMS'); ?></div>
				<?php endif; ?>

				<?php if (!empty($this->database_error) && $this->database_error === 'DATABASE_ERROR') : ?>
					<div class="alert alert-danger py-2 px-3 small mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_DATABASE_ERROR'); ?></div>
				<?php endif; ?>

				<?php if ((int) $this->files_with_incorrect_permissions > 3000) : ?>
					<div class="alert alert-danger py-2 px-3 small mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ALERT'); ?></div>
				<?php endif; ?>

				<?php if ((int) $this->show_all === 1) : ?>
					<div class="alert alert-info py-2 px-3 small mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_INFO'); ?></div>
				<?php endif; ?>

				<?php if (!empty($this->items_permissions) && !$this->state->get('filter.filemanager_permissions_status')) : ?>
					<div class="d-flex flex-wrap justify-content-start gap-2 mb-3">
						<button class="btn btn-sm btn-success" id="add_exception_button" type="button">
							<i class="fa fa-plus" aria-hidden="true"></i>
							<?php echo Text::_('COM_SECURITYCHECKPRO_ADD_AS_EXCEPTION'); ?>
						</button>
						<button class="btn btn-sm btn-primary" id="repair_button" type="button">
							<i class="fa fa-wrench" aria-hidden="true"></i>
							<?php echo Text::_('COM_SECURITYCHECKPRO_FILE_STATUS_REPAIR'); ?>
						</button>
					</div>
				<?php elseif ((string) $this->state->get('filter.filemanager_permissions_status') === '2') : ?>
					<div class="mb-3">
						<button class="btn btn-sm btn-danger" id="delete_exception_button" type="button">
							<i class="icon-trash" aria-hidden="true"></i>
							<?php echo Text::_('COM_SECURITYCHECKPRO_DELETE_EXCEPTION'); ?>
						</button>
					</div>
				<?php endif; ?>

				<div class="table-responsive">
					<table id="filesstatus_table" class="table table-bordered table-hover align-middle">
						<thead>
						<tr>
							<?php if ((int) $this->checkbox_position === 1) : ?>
								<th class="text-center" style="width:2.5rem;">
									<input type="checkbox"
										 class="form-check-input"
										 name="checkall-toggle"
										 title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>"
										 onclick="Joomla.checkAll(this);" />
								</th>
							<?php endif; ?>
							<th><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NAME'); ?></th>
							<th class="text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_EXTENSION'); ?></th>
							<th class="text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_KIND'); ?></th>
							<th><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RUTA'); ?></th>
							<th class="text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TAMANNO'); ?></th>
							<th class="text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_PERMISSIONS'); ?></th>
							<th class="text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_MODIFIED'); ?></th>
							<?php if ((int) $this->checkbox_position === 0) : ?>
								<th class="text-center" style="width:6rem;">
									<?php echo Text::_('COM_SECURITYCHECKPRO_ACTIONS'); ?><br/>
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
								$path = (string) $row['path'];
								$basename = basename($path);
								$nameParts = explode('.', $basename);
								$name = $nameParts[0];
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
										<td class="text-center">
											<?php echo HTMLHelper::_('grid.id', $k, $path, '', 'filesstatus_table'); ?>
										</td>
									<?php endif; ?>

									<td class="text-center"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
									<td class="text-center"><?php echo htmlspecialchars($extension, ENT_QUOTES, 'UTF-8'); ?></td>
									<td class="text-center"><?php echo htmlspecialchars((string) $row['kind'], ENT_QUOTES, 'UTF-8'); ?></td>
									<td class="malwarescan-table-info"><?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?></td>
									<td class="text-center"><?php echo htmlspecialchars($fileSize, ENT_QUOTES, 'UTF-8'); ?></td>
									<td class="text-center">
										<span class="badge bg-<?php echo $permClass; ?>">
											<?php echo htmlspecialchars((string) $row['permissions'], ENT_QUOTES, 'UTF-8'); ?>
										</span>
									</td>
									<td class="text-center"><?php echo htmlspecialchars((string) $row['last_modified'], ENT_QUOTES, 'UTF-8'); ?></td>

									<?php if ((int) $this->checkbox_position === 0) : ?>
										<td class="text-center">
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
					<div class="mt-2"><?php echo $this->pagination->getListFooter(); ?></div>
				<?php endif; ?>

			<?php else : ?>
				<?php
				if ((string) $this->state->get('filter.malwarescan_status') === '2') {
					if ((int) $this->file_manager_include_exceptions_in_database === 0) {
						echo '<div class="alert alert-info py-2 px-3 small">' . Text::_('COM_SECURITYCHECKPRO_EXCEPTIONS_NOT_INCLUDED_IN_DATABASE') . '</div>';
					}
				}
				?>
			<?php endif; ?>
		</div>
	</div>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="controller" value="filemanager" />
	<input type="hidden" name="table" value="permissions" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
