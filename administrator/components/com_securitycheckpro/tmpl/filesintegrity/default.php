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

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
$basemodel = $this->basemodel;

$hasRunBefore = !empty($this->last_check_integrity);
$lastCheckRelative = $hasRunBefore
	? $basemodel->relativeTime($this->last_check_integrity)
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
        <a id="logDownloadLink" class="btn btn-outline-primary" href="#" download>
          <i class="fa fa-download" aria-hidden="true"></i> <?php echo Text::_('COM_SECURITYCHECKPRO_DOWNLOAD_LOG'); ?>
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Last scan info modal -->
<div class="modal hide bd-example-modal-lg" id="last_scan_info_modal" tabindex="-1" role="dialog" aria-labelledby="modal_last_scan_info_modalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header alert alert-info">
        <h2 class="modal-title"><?php echo Text::_('COM_SECURITYCHECKPRO_LAST_SCAN_INFO'); ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
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

<!-- Extensions updated/installed modal -->
<div class="modal fade" id="extensions_updated_modal" tabindex="-1" aria-labelledby="extensionsUpdatedModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header alert alert-info mb-0">
        <h2 class="modal-title h5" id="extensionsUpdatedModalLabel"><?php echo rtrim(Text::_('COM_SECURITYCHECKPRO_EXTENSIONS_UPDATED_INSTALLED'), ': '); ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
      </div>
      <div class="modal-body p-3">
        <p class="text-muted small mb-3"><?php echo Text::_('COM_SECURITYCHECKPRO_EXTENSIONS_UPDATED_INSTALLED_DESC'); ?></p>
        <?php if (!empty($this->installs)) :
            $extTypeBadge = [
                'core'      => 'bg-light text-dark',
                'component' => 'bg-info',
                'module'    => 'bg-secondary',
                'plugin'    => 'bg-dark',
            ];
        ?>
          <ul class="list-group">
            <?php foreach ($this->installs as $extension) :
                $extType    = $extension['type'];
                $extBadgeCls = $extTypeBadge[$extType] ?? 'bg-secondary';
            ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <?php echo htmlspecialchars($extension['name'], ENT_QUOTES, 'UTF-8'); ?>
                <span class="badge <?php echo $extBadgeCls; ?>"><?php echo htmlspecialchars($extType, ENT_QUOTES, 'UTF-8'); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
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

	<!-- Action bar -->
	<div class="scp-actionbar">
		<div>
			<p class="scp-actionbar__title">
				<i class="fa fa-file-signature" aria-hidden="true"></i>
				<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_FILE_INTEGRITY_TEXT'); ?>
			</p>
			<p class="scp-actionbar__subtitle">
				<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_CHECK'); ?>
				&middot; <?php echo $this->escape($lastCheckRelative); ?>
				&middot;
				<span id="task_status"><span class="badge <?php echo $scanStateClass; ?>"><?php echo $scanStateLabel; ?></span></span>
				<span id="task_error" class="badge bg-danger display-none"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ERROR'); ?></span>
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
			<div id="button_start_scan_wrap">
				<button class="btn btn-primary" type="button" id="button_start_scan">
					<i class="fa fa-play me-1" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_START_BUTTON'); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Config strip -->
	<div class="mb-3 d-flex flex-wrap gap-2">
		<span class="scp-chip scp-chip--outline">
			<i class="fa fa-fingerprint" aria-hidden="true"></i>
			<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_HASH_ALG'); ?>: <?php echo $this->escape($this->hash_alg); ?>
		</span>
		<span class="scp-chip scp-chip--outline">
			<i class="fa fa-bolt" aria-hidden="true"></i>
			<?php echo Text::_('COM_SECURITYCHECKPRO_CONFIG_FILE_SCAN_EXECUTABLES_ONLY_LABEL'); ?>:
			<?php echo $this->scan_executables_only ? Text::_('COM_SECURITYCHECKPRO_YES') : Text::_('COM_SECURITYCHECKPRO_NO'); ?>
		</span>
		<span class="scp-chip scp-chip--outline">
			<span class="badge bg-success">&nbsp;</span> <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_OK'); ?>
			<span class="badge bg-warning ms-2">&nbsp;</span> <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_EXCEPTIONS'); ?>
			<span class="badge bg-danger ms-2">&nbsp;</span> <?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_TITLE_COMPROMISED'); ?>
		</span>
	</div>

	<!-- Scan feedback -->
	<div id="container_repair"></div>

	<div id="error_message"></div>
	<div id="error_button"></div>

	<div id="memory_limit_message" class="mb-2">
		<?php
			$memory_limit = (int) substr((string) ini_get('memory_limit'), 0, -1);
			if (($memory_limit <= 128) && !$hasRunBefore) {
				echo '<span class="badge bg-warning">' . Text::_('COM_SECURITYCHECKPRO_MEMORY_LIMIT_LOW') . '</span>';
			}
		?>
	</div>

	<div id="scan_only_executable_message" class="mb-2">
		<?php if ($this->scan_executables_only) : ?>
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
				<p class="text-muted small mb-0"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_RUN_FIRST_SCAN_DESC'); ?></p>
			</div>
		</div>
	<?php else : ?>
		<div class="scp-grid mb-3">
			<div class="card shadow-soft">
				<div class="card-body text-center">
					<div class="text-muted small"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_FILES_SCANNED'); ?></div>
					<div class="fs-4 fw-bold"><?php echo (int) $this->files_scanned_integrity; ?></div>
					<?php if (!empty($this->time_taken) && is_array($this->last_scan_info)) : ?>
						<a href="#last_scan_info_modal" role="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<div class="card shadow-soft">
				<div class="card-body text-center">
					<div class="text-muted small"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_FILES_MODIFIED'); ?></div>
					<div class="fs-4 fw-bold <?php echo $this->files_with_bad_integrity > 0 ? 'text-danger' : ''; ?>"><?php echo (int) $this->files_with_bad_integrity; ?></div>
				</div>
			</div>
			<div class="card shadow-soft">
				<div class="card-body text-center">
					<div class="text-muted small"><?php echo Text::_('COM_SECURITYCHECKPRO_TIME_TAKEN'); ?></div>
					<div class="fs-4 fw-bold"><?php echo $this->escape($this->time_taken); ?></div>
				</div>
			</div>
			<div class="card shadow-soft">
				<div class="card-body text-center">
					<div class="text-muted small"><?php echo rtrim(Text::_('COM_SECURITYCHECKPRO_EXTENSIONS_UPDATED_INSTALLED'), ': '); ?></div>
					<button type="button" class="btn btn-link fs-4 fw-bold p-0" data-bs-toggle="modal" data-bs-target="#extensions_updated_modal">
						<?php echo count($this->installs); ?>
					</button>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!-- Results -->
	<div class="card shadow-soft mb-3">
		<div class="card-header"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_CHECKED_FILES'); ?></div>
		<div class="card-body" id="container_resultado">

			<div class="d-flex flex-wrap gap-2 mb-3 align-items-end">
				<div class="input-group" style="max-width: 22rem;">
					<label for="filter_fileintegrity_search" class="visually-hidden"><?php echo Text::_('JSEARCH_FILTER_LABEL'); ?></label>
					<input type="text" class="form-control" name="filter_fileintegrity_search" placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>" id="filter_fileintegrity_search" value="<?php echo $this->escape($this->state->get('filter.fileintegrity_search')); ?>" title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />
					<button type="submit" class="btn btn-primary" aria-label="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>">
						<span class="icon-search" aria-hidden="true"></span>
					</button>
					<button class="btn btn-outline-secondary" type="button" id="filter_fileintegrity_search_clear" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>" aria-label="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove" aria-hidden="true"></i></button>
				</div>
				<div class="scp-filter-row ms-auto d-flex align-items-end gap-2">
					<?php
						echo $basemodel->renderSelect(
							'filter_fileintegrity_status',
							[
								['value'=>'0','text'=>'COM_SECURITYCHECKPRO_FILEINTEGRITY_TITLE_COMPROMISED'],
								['value'=>'1','text'=>'COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_OK'],
								['value'=>'2','text'=>'COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_EXCEPTIONS']
							],
							['class' => 'form-select', 'style' => 'min-width: 12rem;', 'onchange' => 'this.form.submit()'],
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

			<?php if (!empty($this->items)) : ?>
				<?php if (($this->files_with_bad_integrity > 0) && (empty($this->items))) : ?>
					<div class="alert alert-danger py-2 px-3 small mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_EMPTY_ITEMS'); ?></div>
				<?php endif; ?>

				<?php if ($this->database_error == "DATABASE_ERROR") : ?>
					<div class="alert alert-danger py-2 px-3 small mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_DATABASE_ERROR'); ?></div>
				<?php endif; ?>

				<?php if ($this->files_with_bad_integrity > 3000) : ?>
					<div class="scp-callout scp-callout--warning mb-2">
						<details>
							<summary class="small fw-semibold" style="cursor:pointer; list-style:revert">
								<i class="fa fa-exclamation-triangle me-1" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_FILESINTEGRITY_ALERT'); ?>
							</summary>
							<div class="scp-callout__detail mt-1">
								<?php echo Text::_('COM_SECURITYCHECKPRO_FILESINTEGRITY_ALERT_BODY'); ?>
							</div>
						</details>
					</div>
				<?php endif; ?>

				<?php if ($this->show_all == 1) : ?>
					<div class="alert alert-info py-2 px-3 small mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_INFO'); ?></div>
				<?php endif; ?>

				<?php if ((!empty($this->items)) && (!$this->state->get('filter.fileintegrity_status'))) : ?>
					<div class="d-flex flex-wrap justify-content-start gap-2 mb-3">
						<button class="btn btn-sm btn-success" id="add_exception_button" type="button">
							<i class="fa fa-plus" aria-hidden="true"></i>
							<?php echo Text::_('COM_SECURITYCHECKPRO_ADD_AS_EXCEPTION'); ?>
						</button>
					</div>
				<?php elseif ($this->state->get('filter.fileintegrity_status') == 2) : ?>
					<div class="mb-3">
						<button class="btn btn-sm btn-danger" id="delete_exception_button" type="button">
							<i class="fa fa-trash" aria-hidden="true"></i>
							<?php echo Text::_('COM_SECURITYCHECKPRO_DELETE_EXCEPTION'); ?>
						</button>
					</div>
				<?php endif; ?>

				<div class="table-responsive">
					<table id="filesintegritystatus_table" class="table table-bordered table-hover align-middle">
						<thead>
							<tr>
								<?php if ($this->checkbox_position == 1) : ?>
									<th class="text-center" style="width:2.5rem;">
										<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" aria-label="<?php echo Text::_('COM_SECURITYCHECKPRO_SELECT_ALL'); ?>" />
									</th>
								<?php endif; ?>
								<th><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NAME'); ?></th>
								<th><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RUTA'); ?></th>
								<th class="text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TAMANNO'); ?></th>
								<th class="text-center"><?php echo Text::_('JSTATUS'); ?></th>
								<th class="text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_MODIFIED'); ?></th>
								<?php if ($this->checkbox_position == 0) : ?>
									<th class="text-center" style="width:6rem;">
										<?php echo Text::_('COM_SECURITYCHECKPRO_ACTIONS'); ?><br/>
										<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" aria-label="<?php echo Text::_('COM_SECURITYCHECKPRO_SELECT_ALL'); ?>" />
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
								<?php if ($this->checkbox_position == 1) : ?>
									<td class="text-center">
										<?php echo HTMLHelper::_('grid.id', $k, $path, '', 'filesintegritystatus_table'); ?>
									</td>
								<?php endif; ?>

								<td class="text-center">
									<?php echo $isDir ? '' : htmlspecialchars(basename($path), ENT_QUOTES, 'UTF-8'); ?>
								</td>

								<td class="malwarescan-table-info">
									<?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>
								</td>

								<td class="text-center">
									<?php
										if (!$isDir && file_exists($path)) {
											$size = @filesize($path);
											if ($size === false) {
												echo 'N/A';
											} else {
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

								<td class="text-center">
									<?php
										$badgeClass = 'bg-warning';
										if ($safe_integrity === '0') {
											$badgeClass = 'bg-danger';
										} elseif ($safe_integrity === '1') {
											$badgeClass = 'bg-success';
										}
									?>
									<span class="badge <?php echo $badgeClass; ?>">
										<?php echo htmlspecialchars($notes, ENT_QUOTES, 'UTF-8'); ?>
									</span>
								</td>

								<td class="text-center">
									<?php
										$wrapOpen = ($safe_integrity === '0') ? '<span class="badge bg-danger">' : '';
										$wrapClose = ($safe_integrity === '0') ? '</span>' : '';
										if (file_exists($path)) {
											$mtime = @filemtime($path);
											echo $wrapOpen . htmlspecialchars($mtime ? date('Y-m-d H:i:s', $mtime) : 'N/A', ENT_QUOTES, 'UTF-8') . $wrapClose;
										} else {
											echo $wrapOpen . 'N/A' . $wrapClose;
										}
									?>
								</td>

								<?php if ($this->checkbox_position == 0) : ?>
									<td class="text-center">
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
					<div class="mt-2"><?php echo $this->pagination->getListFooter(); ?></div>
				<?php endif; ?>

			<?php else : ?>
				<?php
					if ($this->state->get('filter.malwarescan_status') == 2) {
						if ($this->file_manager_include_exceptions_in_database == 0) {
							echo '<div class="alert alert-info py-2 px-3 small">' . Text::_('COM_SECURITYCHECKPRO_EXCEPTIONS_NOT_INCLUDED_IN_DATABASE'). '</div>';
						}
					}
				?>
			<?php endif; ?>
		</div>
	</div>

<input type="hidden" name="controller" value="filemanager" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="table" value="integrity" />
<?php echo HTMLHelper::_('form.token'); ?>
</form>
