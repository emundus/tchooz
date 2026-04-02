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

  <!-- Estado del plugin de actualización de base de datos -->
  <div class="card mb-3">
    <div class="card-body">
      <?php
      $msg = $this->database_message;

      if ($this->update_database_plugin_exists && $this->update_database_plugin_enabled) :	
        
        if ($msg === 'PLG_SECURITYCHECKPRO_UPDATE_DATABASE_DATABASE_UPDATED') : ?>
          <div class="badge bg-success p-3 d-block text-start">
            <h4 class="text-white mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES'); ?></h4>
            <p class="mb-1"><strong><?php echo Text::_('COM_SECURITYCHECKPRO_DATABASE_VERSION'); ?></strong> <?php echo $this->escape((string) $this->database_version); ?></p>
            <p class="mb-0"><strong><?php echo Text::_('COM_SECURITYCHECKPRO_LAST_CHECK'); ?></strong> <?php echo $this->escape((string) $this->last_check); ?></p>
          </div>

        <?php elseif (is_null($msg)) : ?>
          <div class="badge bg-success p-3 d-block text-start">
            <h4 class="mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES'); ?></h4>
            <p class="mb-0"><strong><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_NOT_LAUNCHED'); ?></strong></p>
          </div>

        <?php elseif ($msg !== 'PLG_SECURITYCHECKPRO_UPDATE_DATABASE_DATABASE_UPDATED') : ?>
          <div class="badge bg-danger p-3 d-block text-start">
            <h4 class="mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_PROBLEM'); ?></h4>
            <p class="mb-3">
              <strong><?php echo Text::_('COM_SECURITYCHECKPRO_DATABASE_MESSAGE'); ?></strong>
              <?php echo $this->escape(Text::_($msg)); ?>
            </p>

            <?php if ($msg !== 'COM_SECURITYCHECKPRO_UPDATE_DATABASE_SUBSCRIPTION_EXPIRED') : ?>
              <a class="btn btn-dark"
                 href="<?php echo Route::_('index.php?option=com_installer&view=updatesites'); ?>">
                <?php echo Text::_('COM_SECURITYCHECKPRO_CHECK_CONFIG'); ?>
              </a>
            <?php else : ?>
              <a class="btn btn-outline-light"
                 href="https://securitycheck.protegetuordenador.com/subscriptions"
                 target="_blank"
                 rel="noopener noreferrer">
                <?php echo Text::_('COM_SECURITYCHECKPRO_RENEW'); ?>
              </a>
            <?php endif; // <- cierre if interno de "subscription expired" ?>
          </div>
        <?php endif; // <- cierre de if ($msg...) ?>

      <?php elseif ($this->update_database_plugin_exists && !$this->update_database_plugin_enabled) : ?>
        <div class="badge bg-warning p-3 d-block text-start">
          <h4 class="mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME'); ?></h4>
          <p class="mb-0"><strong><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_DISABLED'); ?></strong></p>
        </div>

      <?php elseif (!$this->update_database_plugin_exists) : ?>
        <div class="badge bg-info p-3 d-block text-start">
          <h4 class="mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_NOT_INSTALLED'); ?></h4>
          <p class="mb-0"><strong><?php echo Text::_('COM_SECURITYCHECKPRO_REAL_TIME_UPDATES_NOT_RECEIVE'); ?></strong></p>
        </div>
      <?php endif; // <- cierre del if principal ($this->update_database_plugin_exists...) ?>
    </div>
  </div>


  <!-- Leyenda colores -->
  <div class="card mb-3 mx-2">
    <div class="card-header text-center">
      <?php echo Text::_('COM_SECURITYCHECKPRO_COLOR_CODE'); ?>
    </div>
    <div class="card-body py-2">
      <table class="table table-borderless mb-0">
        <tbody>
          <tr>
            <td><span class="badge bg-success">&nbsp;</span></td>
            <td class="text-start"><?php echo Text::_('COM_SECURITYCHECKPRO_GREEN_COLOR'); ?></td>
            <td><span class="badge bg-warning">&nbsp;</span></td>
            <td class="text-start"><?php echo Text::_('COM_SECURITYCHECKPRO_YELLOW_COLOR'); ?></td>
            <td><span class="badge bg-danger">&nbsp;</span></td>
            <td class="text-start"><?php echo Text::_('COM_SECURITYCHECKPRO_RED_COLOR'); ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Filtros y meta -->
	<div class="row gx-2 mx-2 mb-2 align-items-center">
	  <div class="col-12 col-md">
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
		  ['class' => 'form-select', 'onchange' => 'this.form.submit()'],
		  // <- valor por defecto vacío => lista TODO
		  $this->state->get('filter.extension_type', ''),
		  false,
		  true
		);
		?>
	  </div>

	  <div class="col-12 col-md">
		<?php
		// Opcional: vulnerabilidad con opción "Todos" por defecto
		echo $basemodel->renderSelect(
		  'filter_vulnerable',
		  [
			''   => 'JALL',
			'Si' => 'COM_SECURITYCHECKPRO_HEADING_VULNERABLE',
			'No' => 'COM_SECURITYCHECKPRO_GREEN_COLOR',
		  ],
		  ['class' => 'form-select', 'onchange' => 'this.form.submit()'],
		  $this->state->get('filter.vulnerable', ''),
		  false,
		  true
		);
		?>
	  </div>

	  <div class="col-6 col-md-auto">
		<?php if (!empty($this->items)) : echo $this->pagination->getLimitBox(); endif; ?>
	  </div>

	  <div class="col-6 col-md text-md-end">
		<span class="badge bg-info p-2">
		  <?php echo Text::_('COM_SECURITYCHECKPRO_UPDATE_DATE') . ' ' . $this->escape((string) $this->last_update); ?>
		</span>
	  </div>
	</div>

  <!-- Tabla -->
  <div class="card mb-3 mx-2">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered align-middle" id="dataTable" width="100%" cellspacing="0">
          <thead>
            <tr>
              <th width="5" class="alert alert-info text-center"><?php echo Text::_('COM_SECURITYCHECKPRO_HEADING_ID'); ?></th>
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
                <td class="text-center"><?php echo $id; ?></td>
                <td class="text-center">
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
                <td class="text-center">
                  <span class="<?php echo $this->escape($typeBadge); ?>">
                    <?php echo $this->escape($typeLabel); ?>
                  </span>
                </td>
                <td class="text-center"><?php echo $installed; ?></td>
                <td class="text-center">
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
