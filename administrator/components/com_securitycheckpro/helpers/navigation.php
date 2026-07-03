<?php
defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Site\Model\JsonModel;

HTMLHelper::_('bootstrap.modal');
HTMLHelper::_('bootstrap.tooltip', '.scp-navbar [data-bs-toggle="tooltip"]');

/** @var \Joomla\CMS\Application\CMSApplication $app */
$app      = Factory::getApplication();
$document = $app->getDocument();
$input    = $app->getInput();
$view     = $input->getCmd('view', '');
$ctrl     = $input->getCmd('controller', '');
$option   = $input->getCmd('option', '');
$logo_src = Uri::root(true) . '/media/com_securitycheckpro/images/logo_securitycheck_pro_joomla.png';

// Datos dinámicos
$basemodel                  = new BaseModel();
$logs_pending               = (int) $basemodel->LogsPending();
$trackactions_plugin_exists = (bool) $basemodel->PluginStatus(8);
$exists_filemanager         = $app->getUserState('exists_filemanager', true);

// Estado MFA
$otp_enabled = (int) ComponentHelper::getParams('com_securitycheckpro')->get('otp', 1);
$mfa_status  = 0;
if ($otp_enabled) {
    try {
        $mfa_status = (new JsonModel())->get_two_factor_status();
    } catch (\Throwable $e) {
        $mfa_status = 0;
    }
}

// CSS de la barra de navegación
HTMLHelper::_('stylesheet', 'com_securitycheckpro/scp-navbar.css', ['relative' => true, 'version' => 'auto']);

// Tokens y return URL
$_tok = Session::getFormToken();
$_ret = base64_encode(Uri::getInstance()->toString());

// Estado activo de pestañas (calculado en PHP, sin JS)
$isViewLogs         = ($view === 'logs');
$isViewSysinfo      = ($view === 'sysinfo');
$isViewTrackactions = ($view === 'trackactions_logs');
$isViewDashboard    = (!$isViewLogs && !$isViewSysinfo && !$isViewTrackactions);

// Variables del chip MFA
if ($otp_enabled) {
    if ($mfa_status >= 2) {
        $mfaHref   = Route::_('index.php?option=com_users&view=mfamethods');
        $mfaTarget = '';
        $mfaChip   = 'scp-chip--ok';
        $mfaTitle  = Text::_('COM_SECURITYCHECKPRO_TWO_FACTOR_ENABLED_LABEL');
    } else {
        $mfaHref   = 'https://scpdocs.securitycheckextensions.com/troubleshooting/otp';
        $mfaTarget = ' target="_blank" rel="noopener noreferrer"';
        $mfaChip   = 'scp-chip--ko';
        $mfaTitle  = $mfa_status === 1
            ? Text::_('COM_SECURITYCHECKPRO_NO_2FA_USER_ENABLED')
            : Text::_('COM_SECURITYCHECKPRO_NO_2FA_ENABLED');
    }
}
?>

<!-- ── Modales (sin cambios) ─────────────────────────────────────────────── -->

<!-- Modal purgesessions -->
<div class="modal fade" id="purgesessions" tabindex="-1" role="dialog" aria-labelledby="purgesessionsLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="purgesessionsLabel">
          <i class="fa fa-user-times me-2" aria-hidden="true"></i>
          <?php echo Text::_('COM_SECURITYCHECKPRO_PURGE_SESSIONS'); ?>
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="div_messages">
          <p class="mb-1"><?php echo Text::_('COM_SECURITYCHECKPRO_PURGE_SESSIONS_MESSAGE'); ?></p>
          <p class="text-muted small mb-0"><?php echo Text::_('COM_SECURITYCHECKPRO_PURGE_SESSIONS_MESSAGE_EXPLAINED'); ?></p>
        </div>
        <div id="div_loading" class="text-center py-3 d-none">
          <div class="spinner-border" role="status">
            <span class="visually-hidden"><?php echo Text::_('COM_SECURITYCHECKPRO_PURGING'); ?></span>
          </div>
          <div class="text-muted small mt-2"><?php echo Text::_('COM_SECURITYCHECKPRO_PURGING'); ?></div>
        </div>
      </div>
      <div class="modal-footer" id="div_boton_purge_sessions">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <?php echo Text::_('JCANCEL'); ?>
        </button>
        <button class="btn btn-danger" id="boton_purge_sessions" type="button">
          <i class="fa fa-user-times me-1" aria-hidden="true"></i>
          <?php echo Text::_('COM_SECURITYCHECKPRO_YES'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal initialize_data -->
<div class="modal fade" id="initialize_data" tabindex="-1" role="dialog" aria-labelledby="initializedataLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="initializedataLabel">
          <i class="fa fa-database me-2" aria-hidden="true"></i>
          <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_INITIALIZE_DATA'); ?>
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="warning_message" class="text-muted small">
          <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_CLEAR_DATA_WARNING_START_MESSAGE'); ?>
        </div>
        <div id="completed_message" class="text-success fw-semibold text-center d-none py-2"></div>
        <div id="loading-container" class="text-center py-2"></div>
      </div>
      <div class="modal-footer" id="buttonwrapper">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <?php echo Text::_('JCANCEL'); ?>
        </button>
        <button class="btn btn-danger" type="button" onclick="hideElement('buttonwrapper'); clear_data_button();">
          <i class="fa fa-undo me-1" aria-hidden="true"></i>
          <?php echo Text::_('COM_SECURITYCHECKPRO_CLEAR_DATA_CLEAR_BUTTON'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal clean tmp dir -->
<div class="modal fade" id="cleantmpdir" tabindex="-1" role="dialog" aria-labelledby="cleantmpdirLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="cleantmpdirLabel">
          <i class="fa fa-broom me-2" aria-hidden="true"></i>
          <?php echo Text::_('COM_SECURITYCHECKPRO_CLEAN_TMP_DIR'); ?>
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="warning_message_tmpdir" class="text-muted small">
          <?php echo Text::_('COM_SECURITYCHECKPRO_CLEAN_TMP_DIR_MESSAGE'); ?>
        </div>
        <div id="completed_message_tmpdir" class="fw-semibold text-center d-none py-2"></div>
        <div id="tmpdir-container" class="text-center py-2"></div>
        <div id="container_result" class="d-none mt-2">
          <pre id="container_result_area" class="bg-body-tertiary border rounded p-2 small text-start" style="max-height:10rem;overflow:auto;"></pre>
        </div>
      </div>
      <div class="modal-footer">
        <div id="buttonwrapper_tmpdir" class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <?php echo Text::_('JCANCEL'); ?>
          </button>
          <button class="btn btn-warning" type="button" onclick="hideElement('buttonwrapper_tmpdir'); clean_tmp_dir();">
            <i class="fa fa-broom me-1" aria-hidden="true"></i>
            <?php echo Text::_('COM_SECURITYCHECKPRO_CLEAR_DATA_CLEAR_BUTTON'); ?>
          </button>
        </div>
        <button type="button" id="buttonclose_tmpdir" class="btn btn-secondary d-none" data-bs-dismiss="modal">
          <?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── Barra de navegación: pestañas (izquierda) + acciones (derecha) ────── -->
<div class="scp-navbar" role="navigation"
     aria-label="<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_DASHBOARD'); ?>">

  <!-- Logo / Home -->
  <a class="scp-navbar__logo"
     href="<?php echo Route::_('index.php?option=com_securitycheckpro'); ?>"
     title="<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_DASHBOARD'); ?>"
     aria-label="<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_DASHBOARD'); ?>">
    <img src="<?php echo htmlspecialchars($logo_src, ENT_QUOTES, 'UTF-8'); ?>"
         alt="SecurityCheck Pro" style="max-height:20px;">
  </a>

  <!-- Pestañas de vistas (segmented nav-pills, estado calculado en PHP) -->
  <ul class="nav nav-pills scp-navbar__tabs mb-0" role="tablist"
      aria-label="<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_DASHBOARD'); ?>">

    <li class="nav-item" role="presentation">
      <a class="nav-link<?php echo $isViewSysinfo ? ' active' : ''; ?>"
         href="<?php echo Route::_('index.php?option=com_securitycheckpro&view=sysinfo&' . $_tok . '=1'); ?>"
         data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-delay="250"
         title="<?php echo htmlspecialchars(Text::_('COM_SECURITYCHECKPRO_NAVBAR_TIP_SYSINFO'), ENT_QUOTES, 'UTF-8'); ?>"
         <?php echo $isViewSysinfo ? 'aria-current="page"' : ''; ?>>
        <i class="fa fa-info-circle me-1" aria-hidden="true"></i>
        <?php echo Text::_('COM_SECURITYCHECKPRO_NAVBAR_TAB_SYSINFO'); ?>
      </a>
    </li>

    <li class="nav-item" role="presentation">
      <a class="nav-link<?php echo $isViewLogs ? ' active' : ''; ?>"
         href="index.php?option=com_securitycheckpro&controller=securitycheckpro&view=logs"
         data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-delay="250"
         title="<?php echo htmlspecialchars(Text::_('COM_SECURITYCHECKPRO_NAVBAR_TIP_LOGS'), ENT_QUOTES, 'UTF-8'); ?>"
         <?php echo $isViewLogs ? 'aria-current="page"' : ''; ?>>
        <i class="fa fa-eye me-1" aria-hidden="true"></i>
        <?php echo Text::_('COM_SECURITYCHECKPRO_NAVBAR_TAB_LOGS'); ?>
        <span class="badge ms-1 <?php echo $logs_pending > 0 ? 'bg-warning text-dark' : 'bg-success'; ?>"
              aria-label="<?php echo (int)$logs_pending; ?> logs">
          <?php echo $logs_pending >= 99 ? '+99' : (int)$logs_pending; ?>
        </span>
      </a>
    </li>

    <?php if ($trackactions_plugin_exists): ?>
    <li class="nav-item" role="presentation">
      <a class="nav-link<?php echo $isViewTrackactions ? ' active' : ''; ?>"
         href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=trackactions_logs&view=trackactions_logs'); ?>"
         data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-delay="250"
         title="<?php echo htmlspecialchars(Text::_('COM_SECURITYCHECKPRO_NAVBAR_TIP_TRACKACTIONS'), ENT_QUOTES, 'UTF-8'); ?>"
         <?php echo $isViewTrackactions ? 'aria-current="page"' : ''; ?>>
        <i class="fa fa-binoculars me-1" aria-hidden="true"></i>
        <?php echo Text::_('COM_SECURITYCHECKPRO_NAVBAR_TAB_TRACKACTIONS'); ?>
      </a>
    </li>
    <?php endif; ?>

  </ul>

  <!-- Acciones (derecha) -->
  <div class="scp-navbar__actions">

    <!-- Comprobar vulnerabilidades (botón primario) -->
    <a href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=securitycheckpro&view=securitycheckpro&' . $_tok . '=1'); ?>"
       class="btn btn-sm btn-info"
       data-bs-toggle="tooltip" data-bs-placement="bottom"
       title="<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_CHECK_VULNERABILITIES_TEXT'); ?>">
      <i class="fa fa-check-circle" aria-hidden="true"></i>
      <span class="d-none d-lg-inline ms-1"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_CHECK_VULNERABILITIES_TEXT'); ?></span>
    </a>

    <!-- Dropdown Ajustes (Opciones + Configuración) -->
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
              data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"
              title="<?php echo Text::_('COM_SECURITYCHECKPRO_CMDBAR_SETTINGS'); ?>">
        <i class="fa fa-sliders-h" aria-hidden="true"></i>
        <span class="d-none d-lg-inline ms-1"><?php echo Text::_('COM_SECURITYCHECKPRO_CMDBAR_SETTINGS'); ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <?php if ($exists_filemanager): ?>
        <li><h6 class="dropdown-header"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_OPTIONS'); ?></h6></li>
        <li>
          <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&' . $_tok . '=1'); ?>">
            <i class="fa fa-folder-open me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_FILE_MANAGER_TEXT'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&' . $_tok . '=1'); ?>">
            <i class="fa fa-file-signature me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_FILE_INTEGRITY_TEXT'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=protection&view=protection&' . $_tok . '=1'); ?>">
            <i class="fa fa-file-alt me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_HTACCESS_PROTECTION_TEXT'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=malwarescan&' . $_tok . '=1'); ?>">
            <i class="fa fa-bug me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_MALWARESCAN'); ?>
          </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <?php endif; ?>
        <li><h6 class="dropdown-header"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_CONFIGURATION'); ?></h6></li>
        <li>
          <a class="dropdown-item" href="index.php?option=com_config&view=component&component=com_securitycheckpro&path=&return=<?php echo $_ret; ?>">
            <i class="fa fa-cog me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_GLOBAL_CONFIGURATION'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=firewallconfig&view=firewallconfig&' . $_tok . '=1'); ?>">
            <i class="fa fa-shield-alt me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_WAF_CONFIG'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_scheduler&view=tasks'); ?>">
            <i class="fa fa-clock me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_CRON_CONFIGURATION'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=rules&view=rules&' . $_tok . '=1'); ?>">
            <i class="fa fa-list me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_RULES_TEXT'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=controlcenter&view=controlcenter&' . $_tok . '=1'); ?>">
            <i class="fa fa-tachometer-alt me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_CONTROLCENTER_TEXT'); ?>
          </a>
        </li>
      </ul>
    </div>

    <!-- Dropdown Mantenimiento (Tareas + Rendimiento) -->
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
              data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"
              title="<?php echo Text::_('COM_SECURITYCHECKPRO_NAVBAR_MAINTENANCE'); ?>">
        <i class="fa fa-tools" aria-hidden="true"></i>
        <span class="d-none d-lg-inline ms-1"><?php echo Text::_('COM_SECURITYCHECKPRO_NAVBAR_MAINTENANCE'); ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><h6 class="dropdown-header"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_TASKS'); ?></h6></li>
        <li>
          <a class="dropdown-item" href="#initialize_data" data-bs-toggle="modal" data-bs-target="#initialize_data">
            <i class="fa fa-undo me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_INITIALIZE_DATA'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="#" onclick="Joomla.submitbutton('Export_config'); return false;">
            <i class="fa fa-download me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_EXPORT_CONFIG'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=upload&' . $_tok . '=1'); ?>">
            <i class="fa fa-upload me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_IMPORT_CONFIG'); ?>
          </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li><h6 class="dropdown-header"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_PERFORMANCE'); ?></h6></li>
        <li>
          <a class="dropdown-item" href="#purgesessions" data-bs-toggle="modal" data-bs-target="#purgesessions">
            <i class="fa fa-user-times me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_PURGE_SESSIONS'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="#cleantmpdir" data-bs-toggle="modal" data-bs-target="#cleantmpdir">
            <i class="fa fa-recycle me-2" aria-hidden="true"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CLEAN_TMP_DIR'); ?>
          </a>
        </li>
      </ul>
    </div>

    <!-- Chip MFA (sin cambios) -->
    <?php if ($otp_enabled): ?>
    <a href="<?php echo htmlspecialchars($mfaHref, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $mfaTarget; ?>
       class="scp-chip <?php echo $mfaChip; ?> text-decoration-none align-self-center"
       data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-theme="dark"
       title="<?php echo htmlspecialchars($mfaTitle, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="fa fa-shield-alt" aria-hidden="true"></i>
      <span class="d-none d-sm-inline ms-1">MFA</span>
    </a>
    <?php endif; ?>

  </div>

</div>
