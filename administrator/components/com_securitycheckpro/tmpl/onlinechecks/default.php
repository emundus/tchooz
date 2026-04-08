<?php
/**
 * @author  Jose A. Luque
 * @license GNU/GPL v2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Application\CMSApplication;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Onlinechecks\HtmlView $this */
/** @var list<stdClass> $items */

Session::checkToken('get') or die('Invalid Token');

// Token para acciones
/** @var \Joomla\CMS\Application\CMSApplication $app */
$app       = Factory::getApplication();
$tokenName = $app->getFormToken();
$csrfPair  = $tokenName . '=1';
?>

<!-- Modal para ver contenido de fichero -->
<div class="modal fade" id="view_file" tabindex="-1" aria-labelledby="viewfileLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header alert alert-info mb-0">
        <h2 class="modal-title h5 mb-0" id="viewfileLabel">
          <?php echo Text::_('COM_SECURITYCHECKPRO_FILE_CONTENT'); ?>
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
      </div>
      <div class="modal-body">
        <div id="logMeta" class="small text-muted mb-2"></div>
        <pre id="logContent" class="border p-3 bg-light" style="white-space: pre-wrap;"></pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <?php echo Text::_('JCLOSE'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<form
  action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=onlinechecks&' . $csrfPair); ?>"
  method="post"
  name="adminForm"
  id="adminForm"  
>
  <?php require JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php'; ?>

  <div class="alert alert-warning mt-3">
    <p class="mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_PROFESSIONAL_HELP'); ?></p>
    <p class="mb-0">
      <a href="https://securitycheck.protegetuordenador.com/index.php/contact-us"
         class="btn btn-primary text-white"
         target="_blank"
         rel="noopener noreferrer">
        <?php echo Text::_('COM_SECURITYCHECKPRO_CONTACT_US'); ?>
      </a>
    </p>
  </div>

  <!-- Filtro -->
  <div id="filter-bar" class="filter-search-bar btn-group my-3">
    <div class="input-group">
      <label for="filter_onlinechecks_search" class="visually-hidden">
        <?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>
      </label>
      <input
        type="text"
        class="form-control"
        name="filter_onlinechecks_search"
        id="filter_onlinechecks_search"
        placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>"
        title="<?php echo Text::_('JSEARCH_FILTER'); ?>"
        value="<?php echo $this->escape((string) ($this->state->get('filter.onlinechecks_search') ?? '')); ?>"
      />
      <button type="submit" class="filter-search-bar__button btn btn-primary" aria-label="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
        <span class="filter-search-bar__button-icon icon-search" aria-hidden="true"></span>
      </button>
      <button class="btn btn-dark" type="button" id="filter_onlinechecks_search_button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>" aria-label="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
        <span class="icon-remove" aria-hidden="true"></span>
      </button>
    </div>
  </div>

  <!-- Cabecera sección -->
  <div class="text-end mb-2">
    <span class="badge rounded-pill text-bg-info px-3 py-2">
      <?php echo Text::_('COM_SECURITYCHECKPRO_ONLINE_CHECK_LOGS'); ?>
    </span>
  </div>

  <!-- Tabla -->
  <div class="table-responsive">
    <table id="onlinechecks_logs_table" class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr class="text-center">
          <th><?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_FILES_SCANNED'); ?></th>
          <th><?php echo Text::_('COM_SECURITYCHECKPRO_THREATS_FOUND'); ?></th>
          <th><?php echo Text::_('COM_SECURITYCHECKPRO_INFECTED_FILES'); ?></th>
          <th><?php echo Text::_('COM_SECURITYCHECKPRO_CREATION_DATE'); ?></th>
          <th>
            <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" aria-label="<?php echo $this->escape(Text::_('JGLOBAL_CHECK_ALL')); ?>" />
          </th>
        </tr>
      </thead>
      <tbody>
      <?php if ($this->items): ?>
        <?php $k = 0; ?>
        <?php foreach ($this->items as $row): ?>
          <?php
            // Seguridad básica / saneo por posición esperada
			/** @var \stdClass $row */
			/** @phpstan-var object{files_checked:int,threats_found:int,scan_date:string,infected_files:string|null,id:int,filename:string} $row */
            $filesScanned = (int) ($row->files_checked ?? 0);
            $threats      = (int) ($row->threats_found ?? 0);
            $created      = $this->escape((string) ($row->scan_date ?? ''));
            $infectedRaw  = $row->infected_files ?? '';
            $rowId        = $row->id ?? null; // id del registro para checkbox y mapeo log
            $infectedList = [];

            if ($infectedRaw !== '') {
              $decoded = json_decode($infectedRaw, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
              if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // normaliza a array de strings
                foreach ($decoded as $v) {
                  if (is_string($v) && $v !== '') {
                    $infectedList[] = $v;
                  }
                }
              }
            }
            $showList = array_slice($infectedList, 0, 3);
            $more     = max(count($infectedList) - count($showList), 0);

            // Ruta de log asociada (si existe)
            $logFile = isset($this->logPaths[$rowId]) ? (string) $this->logPaths[$rowId] : '';
          ?>
          <tr class="text-center">
            <td>
              <span class="badge text-bg-dark"><?php echo $filesScanned; ?></span>
            </td>
            <td>
              <?php if ($threats === 0): ?>
                <span class="badge text-bg-success">0</span>
              <?php else: ?>
                <span class="badge text-bg-danger"><?php echo $threats; ?></span>
              <?php endif; ?>
            </td>
            <td class="text-start">
              <?php if (empty($infectedList)): ?>
                <span class="badge text-bg-success"><?php echo Text::_('COM_SECURITYCHECKPRO_NONE'); ?></span>
              <?php else: ?>
                <?php foreach ($showList as $name): ?>
                  <span class="badge text-bg-warning mb-1"><?php echo $this->escape($name); ?></span><br>
                <?php endforeach; ?>
                <?php if ($more > 0): ?>
                  <span class="badge text-bg-secondary">
                    <?php echo Text::sprintf('COM_SECURITYCHECKPRO_MORE_FILES', (int) $more); ?>
                  </span>
                <?php endif; ?>
              <?php endif; ?>
            </td>
            <td><?php echo $created; ?></td>
            <td>
              <?php echo HTMLHelper::_('grid.id', $k, $logFile, '', 'onlinechecks_logs_table'); ?>
            </td>
          </tr>
          <?php $k++; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="5" class="text-center text-muted py-4">
            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
          </td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($this->items): ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <div><?php echo $this->pagination->getListFooter(); ?></div>
      <div><?php echo $this->pagination->getLimitBox(); ?></div>
    </div>
  <?php endif; ?>

  <input type="hidden" name="option" value="com_securitycheckpro" />
  <input type="hidden" name="task" value="" />
  <input type="hidden" name="boxchecked" value="0" />
  <input type="hidden" name="controller" value="onlinechecks" />
  <input type="hidden" name="<?php echo $tokenName; ?>" value="1" />
</form>