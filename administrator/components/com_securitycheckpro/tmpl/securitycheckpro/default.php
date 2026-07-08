<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Path;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Securitycheckpro\HtmlView $this */

// CSRF en peticiones GET (listado/filtros)
Session::checkToken('get') or die('Invalid Token');

// Carga de idioma del plugin (ruta de admin para asegurar hit)
$lang = Factory::getApplication()->getLanguage();
$lang->load('plg_system_securitycheckpro', JPATH_ADMINISTRATOR);

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
$basemodel = $this->basemodel;

// Mapeos de clases bootstrap
$badgeByType = [
    'core'      => 'badge bg-light text-dark',   // distintivo suave para core
    'component' => 'badge bg-info',
    'module'    => 'badge bg-secondary',
    'plugin'    => 'badge bg-dark',
];

$badgeByVuln = [
    'Si'         => 'badge bg-danger',
    'Indefinido' => 'badge bg-warning',
    'No'         => 'badge bg-success',
];

// Estado del plugin "Update Database" (badge compacto en la action bar)
$msg = $this->database_message;

$rtBadgeClass  = 'bg-info';
$rtBadgeText   = Text::_('COM_SECURITYCHECKPRO_PLUGIN_NOT_INSTALLED');
$rtTooltip     = Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_NOT_INSTALLED') . ' ' . Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_NOT_RECEIVE');
$rtAlertText   = '';
$rtActionUrl   = '';
$rtActionLabel = '';
$rtActionClass = 'btn-outline-secondary';
$rtActionExternal = false;

if ($this->update_database_plugin_exists && $this->update_database_plugin_enabled) {
    if ($msg === 'PLG_SECURITYCHECKPRO_UPDATE_DATABASE_DATABASE_UPDATED') {
        $rtBadgeClass = 'bg-success';
        $rtBadgeText  = Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_OK_SHORT');
        $rtTooltip    = Text::_('COM_SECURITYCHECKPRO_DATABASE_VERSION') . $this->escape((string) $this->database_version);
    } elseif ($msg === null) {
        $rtBadgeClass = 'bg-secondary';
        $rtBadgeText  = Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_NOT_LAUNCHED_SHORT');
        $rtTooltip    = Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_NOT_LAUNCHED');
    } else {
        $rtBadgeClass = 'bg-danger';
        $rtBadgeText  = Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_PROBLEM_SHORT');
        $rtTooltip    = '';
        $rtAlertText  = Text::_('COM_SECURITYCHECKPRO_DATABASE_MESSAGE') . $this->escape(Text::_($msg));

        if ($msg !== 'COM_SECURITYCHECKPRO_UPDATE_DATABASE_SUBSCRIPTION_EXPIRED') {
            $rtActionUrl   = Route::_('index.php?option=com_installer&view=updatesites');
            $rtActionLabel = Text::_('COM_SECURITYCHECKPRO_CHECK_CONFIG');
            $rtActionClass = 'btn-outline-dark';
        } else {
            $rtActionUrl      = 'https://securitycheck.protegetuordenador.com/subscriptions';
            $rtActionLabel    = Text::_('COM_SECURITYCHECKPRO_RENEW');
            $rtActionClass    = 'btn-outline-warning';
            $rtActionExternal = true;
        }
    }
} elseif ($this->update_database_plugin_exists && !$this->update_database_plugin_enabled) {
    $rtBadgeClass = 'bg-warning';
    $rtBadgeText  = Text::_('COM_SECURITYCHECKPRO_PLUGIN_DISABLED');
    $rtTooltip    = Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_DISABLED');
}
?>

<!-- Modal: Vulnerable extension -->
<div class="modal fade" id="modal_vuln_extension" tabindex="-1" aria-labelledby="modal_vuln_extensionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="dialog" aria-modal="true">
    <div class="modal-content">
      <div class="modal-header alert alert-info mb-0">
        <h2 class="modal-title h5" id="modal_vuln_extensionLabel">
          <?php echo Text::_('COM_SECURITYCHECKPRO_VULN_INFO_TEXT'); ?>
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo $this->escape(Text::_('JCLOSE')); ?>"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive" id="response_result"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<form
  action="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=securitycheckpro&view=securitycheckpro&' . Session::getFormToken() . '=1'); ?>"
  method="post"
  name="adminForm"
  id="adminForm"
  class="mx-2"
>

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
        <i class="fa fa-shield-virus" aria-hidden="true"></i>
        <?php echo Text::_('COM_SECURITYCHECKPRO_VULNERABILITIES'); ?>
      </p>
      <p class="scp-actionbar__subtitle">
        <?php echo trim(Text::_('COM_SECURITYCHECKPRO_UPDATE_DATE')); ?>
        &middot; <?php echo $this->escape((string) $this->last_update); ?>
        <?php if ($this->update_database_plugin_exists && $this->update_database_plugin_enabled && (string) $this->last_check !== '') : ?>
          &middot; <?php echo trim(Text::_('COM_SECURITYCHECKPRO_LAST_CHECK')); ?>
          <?php echo $this->escape((string) $this->last_check); ?>
        <?php endif; ?>
        &middot;
        <?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME'); ?>
        <span id="real_time_status_badge" class="badge <?php echo $rtBadgeClass; ?>"
              <?php if ($rtTooltip !== '') : ?>data-bs-toggle="tooltip" title="<?php echo $this->escape($rtTooltip); ?>"<?php endif; ?>>
          <?php echo $this->escape($rtBadgeText); ?>
        </span>
      </p>
    </div>
    <?php if ($rtActionUrl !== '') : ?>
      <div class="scp-actionbar__actions">
        <a class="btn btn-sm <?php echo $rtActionClass; ?>"
           href="<?php echo $rtActionUrl; ?>"
           <?php if ($rtActionExternal) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>>
          <?php echo $this->escape($rtActionLabel); ?>
        </a>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($rtAlertText !== '') : ?>
    <div class="alert alert-danger py-2 px-3 small mb-3"><?php echo $rtAlertText; ?></div>
  <?php endif; ?>


  <!-- Metrics row -->
  <div class="scp-grid scp-grid--3 mb-3">
    <div class="card shadow-soft">
      <div class="card-body text-center">
        <div class="text-muted small"><?php echo Text::_('COM_SECURITYCHECKPRO_VULN_TOTAL_EXTENSIONS'); ?></div>
        <div class="fs-4 fw-bold"><?php echo (int) $this->extensions_total; ?></div>
      </div>
    </div>
    <div class="card shadow-soft">
      <div class="card-body text-center">
        <div class="text-muted small"><?php echo Text::_('COM_SECURITYCHECKPRO_VULN_VULNERABLE_TOTAL'); ?></div>
        <div class="fs-4 fw-bold <?php echo $this->vulnerable_total > 0 ? 'text-danger' : ''; ?>"><?php echo (int) $this->vulnerable_total; ?></div>
      </div>
    </div>
    <div class="card shadow-soft">
      <div class="card-body text-center">
        <div class="text-muted small"><?php echo Text::_('COM_SECURITYCHECKPRO_VULN_UNDEFINED_TOTAL'); ?></div>
        <div class="fs-4 fw-bold <?php echo $this->undefined_total > 0 ? 'text-warning' : ''; ?>"><?php echo (int) $this->undefined_total; ?></div>
      </div>
    </div>
  </div>

  <!-- Results -->
  <div class="card shadow-soft mb-3">
    <div class="card-body">

      <!-- Filtros -->
      <div class="scp-filter-row row gx-2 mb-3 align-items-end">
        <div class="col-12 col-md-auto">
          <?php
          // Añadimos 'All' (valor vacío) y 'Core'
          echo $basemodel->renderSelect(
            'filter_extension_type',
            [
              ''         => 'JALL', // mostrará "Todos" con traducción de Joomla
              'core'     => 'COM_SECURITYCHECKPRO_TYPE_core',
              'component'=> 'COM_SECURITYCHECKPRO_TITLE_COMPONENT',
              'plugin'   => 'COM_SECURITYCHECKPRO_TITLE_PLUGIN',
              'module'   => 'COM_SECURITYCHECKPRO_TITLE_MODULE',
            ],
            ['class' => 'form-select', 'style' => 'min-width: 11rem;', 'onchange' => 'this.form.submit()'],
            // <- valor por defecto vacío => lista TODO
            $this->state->get('filter.extension_type', ''),
            false,
            true
          );
          ?>
        </div>

        <div class="col-12 col-md-auto">
          <?php
          // Opcional: vulnerabilidad con opción "Todos" por defecto
          echo $basemodel->renderSelect(
            'filter_vulnerable',
            [
              ''   => 'JALL',
              'Si' => 'COM_SECURITYCHECKPRO_HEADING_VULNERABLE',
              'No' => 'COM_SECURITYCHECKPRO_GREEN_COLOR',
            ],
            ['class' => 'form-select', 'style' => 'min-width: 11rem;', 'onchange' => 'this.form.submit()'],
            $this->state->get('filter.vulnerable', ''),
            false,
            true
          );
          ?>
        </div>

        <?php if (!empty($this->items)) : ?>
          <div class="col-12 col-md-auto ms-md-auto">
            <?php echo $this->pagination->getLimitBox(); ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Tabla -->
      <div class="table-responsive">
        <table class="table table-bordered align-middle scp-table-responsive" id="dataTable">
          <thead>
            <tr>
              <th class="alert alert-info text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_HEADING_ID'); ?></th>
              <th class="alert alert-info text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_HEADING_PRODUCT'); ?></th>
              <th class="alert alert-info text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_HEADING_TYPE'); ?></th>
              <th class="alert alert-info text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_HEADING_INSTALLED_VERSION'); ?></th>
              <th class="alert alert-info text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_HEADING_VULNERABLE'); ?></th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($this->items)) : ?>
            <?php foreach ($this->items as $row) : ?>
              <?php
                $id          = (int) ($row->id ?? 0);
                $productRaw  = (string) ($row->Product ?? '');
                $product     = $this->escape($productRaw);
                $type        = (string) ($row->sc_type ?? '');
                $installed   = $this->escape((string) ($row->Installedversion ?? ''));
                $vuln        = (string) ($row->Vulnerable ?? 'Indefinido');

                $typeBadge   = $badgeByType[$type] ?? 'badge bg-dark';
                $vulnBadge   = $badgeByVuln[$vuln] ?? 'badge bg-warning';
                $typeLabel   = Text::_('COM_SECURITYCHECKPRO_TYPE_' . $type);
                $vulnLabel   = Text::_('COM_SECURITYCHECKPRO_VULNERABLE_' . $vuln);
              ?>
              <tr>
                <td class="text-center" data-label="<?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_HEADING_ID')); ?>"><?php echo $id; ?></td>
                <td class="text-center" data-label="<?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_HEADING_PRODUCT')); ?>">
                  <?php if ($vuln !== 'No') : ?>
                    <a href="#"
                       class="link-primary"
                       data-product="<?php echo htmlspecialchars($productRaw, ENT_QUOTES, 'UTF-8'); ?>"
                       data-bs-toggle="modal"
                       data-bs-target="#modal_vuln_extension"
                       onclick="filter_vulnerable_extension?.(this.dataset.product); return false;">
                       <?php echo $product; ?>
                    </a>
                  <?php else : ?>
                    <?php echo $product; ?>
                  <?php endif; ?>
                </td>
                <td class="text-center" data-label="<?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_HEADING_TYPE')); ?>">
                  <span class="<?php echo $this->escape($typeBadge); ?>">
                    <?php echo $this->escape($typeLabel); ?>
                  </span>
                </td>
                <td class="text-center" data-label="<?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_HEADING_INSTALLED_VERSION')); ?>"><?php echo $installed; ?></td>
                <td class="text-center" data-label="<?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_HEADING_VULNERABLE')); ?>">
                  <span class="<?php echo $this->escape($vulnBadge); ?>">
                    <?php echo $this->escape($vulnLabel); ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($this->items)) : ?>
        <div class="mt-2">
          <?php echo $this->pagination->getListFooter(); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <input type="hidden" name="option" value="com_securitycheckpro">
  <input type="hidden" name="task" value="">
  <input type="hidden" name="boxchecked" value="1">
  <input type="hidden" name="controller" value="securitycheckpro">
</form>
