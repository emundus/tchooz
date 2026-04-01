<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Input\Input;
use Joomla\Filesystem\Path;
use Joomla\CMS\Application\CMSApplication;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Cpanel\HtmlView $this */

// ---------- Escapers ----------
function e($v): string { return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function a($v): string { return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
/** URL SIN ESCAPAR; opcionalmente pasa por Route::_ */
function u(string $url, bool $route = true): string { return $route ? Route::_($url) : $url; }

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

// Textos/URLs
$review                     = sprintf(Text::_('COM_SECURITYCHECKPRO_REVIEW'), '<a href="http://extensions.joomla.org/extensions/extension/access-a-security/site-security/securitycheck-pro" target="_blank" rel="noopener noreferrer">', '</a>');
$translatorName             = Text::_('COM_SECURITYCHECKPRO_TRANSLATOR_NAME');
$translatorUrl              = Text::_('COM_SECURITYCHECKPRO_TRANSLATOR_URL');
$firewallPluginStatus       = Text::_('COM_SECURITYCHECKPRO_FIREWALL_PLUGIN_STATUS');
$cronPluginStatus           = Text::_('COM_SECURITYCHECKPRO_CRON_PLUGIN_STATUS');
$updateDatabasePluginStatus = Text::_('COM_SECURITYCHECKPRO_UPDATE_DATABASE_PLUGIN_STATUS');
$spamProtectionPluginStatus = Text::_('COM_SECURITYCHECKPRO_SPAM_PROTECTION_PLUGIN_STATUS');

/** @var \Joomla\CMS\Application\CMSApplication $app */
$app       = Factory::getApplication();
$lang = $app->getLanguage();
$lang->load('com_admin', JPATH_ADMINISTRATOR);

// URL base para logs
$logUrl   = 'index.php?option=com_securitycheckpro&controller=securitycheckpro&view=logs&datefrom=%s&dateto=%s';
$lastYear = gmdate('Y', strtotime('-1 year'));

// ---------- Helpers visuales ----------
function chip(bool $ok, string $tOk, string $tKo): string {
    $cls = $ok ? 'scp-chip scp-chip--ok' : 'scp-chip scp-chip--ko';
    $tx  = $ok ? $tOk : $tKo;
    return '<span class="' . $cls . '">' . e($tx) . '</span>';
}

/** Card simple (Mockup 1) */
function renderSimpleStatusCard(string $title, bool $enabled, string $icon, string $actionsHtml = ''): void { ?>
    <div class="card shadow-soft compact">
        <div class="scp-card__body">
            <div class="scp-card__icon"><span class="fa <?php echo a($icon); ?>"></span></div>
            <div class="scp-card__title"><?php echo e($title); ?></div>
            <div class="scp-card__status"><?php echo chip($enabled, Text::_('COM_SECURITYCHECKPRO_PLUGIN_ENABLED'), Text::_('COM_SECURITYCHECKPRO_PLUGIN_DISABLED')); ?></div>
            <?php if ($actionsHtml): ?>
                <div class="scp-card__actions"><?php echo $actionsHtml; ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php }

function renderExistsStatusCard(string $title, bool $exists, bool $enabled, string $icon, array $buttons): void {
    $statusHtml = !$exists
        ? '<span class="scp-chip scp-chip--dark">' . Text::_('COM_SECURITYCHECKPRO_PLUGIN_NOT_INSTALLED') . '</span>'
        : chip($enabled, Text::_('COM_SECURITYCHECKPRO_PLUGIN_ENABLED'), Text::_('COM_SECURITYCHECKPRO_PLUGIN_DISABLED'));
    $actionHtml = '';
    if ($exists && $enabled && isset($buttons['disable']))      $actionHtml = $buttons['disable'];
    elseif ($exists && !$enabled && isset($buttons['enable']))  $actionHtml = $buttons['enable'];
    elseif (!$exists && isset($buttons['more']))                $actionHtml = $buttons['more'];
    ?>
    <div class="card shadow-soft compact">
        <div class="scp-card__body">
            <div class="scp-card__icon"><span class="fa <?php echo a($icon); ?>"></span></div>
            <div class="scp-card__title"><?php echo e($title); ?></div>
            <div class="scp-card__status"><?php echo $statusHtml; ?></div>
            <?php if ($actionHtml): ?>
                <div class="scp-card__actions"><?php echo $actionHtml; ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php }

/** Selección aleatoria de periodo con dato > 0 */
function pickRandomPeriod($view): array {
    $candidates = [
        ['value' => (int) $view->this_year_logs,  'label' => Text::_('COM_SECURITYCHECKPRO_CPANEL_THIS_YEAR')],
        ['value' => (int) $view->last_month_logs, 'label' => Text::_('COM_SECURITYCHECKPRO_CPANEL_LAST_MONTH')],
        ['value' => (int) $view->this_month_logs, 'label' => Text::_('COM_SECURITYCHECKPRO_CPANEL_THIS_MONTH')],
        ['value' => (int) $view->yesterday,       'label' => Text::_('COM_SECURITYCHECKPRO_CPANEL_YESTERDAY')],
        ['value' => (int) $view->today,           'label' => Text::_('COM_SECURITYCHECKPRO_CPANEL_TODAY')],
    ];
    shuffle($candidates);
    $tries = 0;
    foreach ($candidates as $c) {
        if ($c['value'] > 0) return [$c['value'], $c['label']];
        if (++$tries >= 3) break;
    }
    return [0, ''];
}

// -------- Mensaje informativo (cookie 24h) --------
[$valorMostrar, $periodLabel] = pickRandomPeriod($this);
$input     = new Input();
$cookieVal = $input->cookie->get('SCPInfoMessage', null);
if ($valorMostrar && is_null($cookieVal)) {
    $time = time() + 86400; // 1 día
	/** @var \Joomla\CMS\Application\CMSApplication $app */
    $app  = Factory::getApplication();
    $input->cookie->set('SCPInfoMessage', 'SCPInfoMessage', [
        'expires'  => $time,
        'path'     => $app->get('cookie_path', '/'),
        'domain'   => $app->get('cookie_domain', ''),
        'secure'   => $app->isHttpsForced(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}
?>

<form action="<?php echo u('index.php?option=com_securitycheckpro'); ?>" method="post" name="adminForm" id="adminForm">
    <?php
    // Navegación (include robusto)
    $navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
    if (is_file($navFile)) {
        require $navFile;
    }
    ?>

    <?php if ($valorMostrar && is_null($cookieVal)) : ?>
        <div id="mensaje_informativo" class="alert alert-success">
            <strong><?php echo Text::sprintf('COM_SECURITYCHECKPRO_INFO_MESSAGE', e($valorMostrar), e($periodLabel)); ?></strong>
        </div>
    <?php endif; ?>

        <!-- HERO Overall Security -->
    <?php
    $overall = (int) $this->overall;
    $class   = "c100 p{$overall} green";
    if ($overall > 0 && $overall < 60) { $class = "c100 p{$overall} orange"; }
    elseif ($overall >= 60 && $overall < 80) { $class = "c100 p{$overall}"; }
    ?>
    <div class="scp-hero">
        <div>
            <div class="scp-hero__title">
                <i class="fa fa-shield-alt"></i>
                <?php echo Text::_('COM_SECURITYCHECKPRO_SECURITY_OVERALL_SECURITY_STATUS'); ?>
            </div>
            <p class="scp-hero__subtitle mb-2">
                <?php echo Text::_('COM_SECURITYCHECKPRO_UPDATE_DATE'); ?>:
                <?php echo e(date('Y-m-d H:i:s')); ?>
            </p>
            <button id="go_system_info_buton" class="btn btn-sm btn-warning btn-hero" type="button">
				<?php echo Text::_('COM_SECURITYCHECKPRO_CHECK_STATUS'); ?>
			</button>
        </div>
        <div class="scp-hero__gauge">
            <div class="<?php echo a($class); ?>">
                <span><?php echo $overall . '%'; ?></span>
                <div class="slice"><div class="bar"></div><div class="fill"></div></div>
            </div>
        </div>
    </div>

    <!-- TOP STATUS CARDS (4) -->
    <div class="scp-grid">
        <?php
        // Web Firewall
        $firewallActions = $this->firewall_plugin_enabled
            ? '<button id="disable_firewall_button" class="btn btn-sm btn-danger" type="button"><i class="fa fa-power-off"></i> ' . Text::_('COM_SECURITYCHECKPRO_DISABLE') . '</button>'
            : '<button id="enable_firewall_button" class="btn btn-sm btn-success" type="button"><i class="fa fa-check"></i> ' . Text::_('COM_SECURITYCHECKPRO_ENABLE') . '</button>';
        renderSimpleStatusCard($firewallPluginStatus, (bool) $this->firewall_plugin_enabled, 'fa-shield-alt', $firewallActions);

        // Cron
        $cronAction = '<a href="' . u('index.php?option=com_scheduler&view=tasks') . '" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="' . $lang->_('COM_ADMIN_HELP_SCHEDULED_TASKS') . '"><i class="fa fa-cog"></i></a>';
        renderSimpleStatusCard($cronPluginStatus, (bool) $this->cron_plugin_enabled, 'fa-clock', $cronAction);

        // Update Database
        renderExistsStatusCard(
            $updateDatabasePluginStatus,
            (bool) $this->update_database_plugin_exists,
            (bool) $this->update_database_plugin_enabled,
            'fa-database',
            [
                'enable'  => '<button id="enable_update_database_button" class="btn btn-sm btn-success" type="button"><i class="fa fa-check"></i> ' . Text::_('COM_SECURITYCHECKPRO_ENABLE') . '</button>',
                'disable' => '<button id="disable_update_database_button" class="btn btn-sm btn-danger" type="button"><i class="fa fa-power-off"></i> ' . Text::_('COM_SECURITYCHECKPRO_DISABLE') . '</button>',
                'more'    => '<a class="btn btn-sm btn-info" href="https://securitycheck.protegetuordenador.com/index.php/our-products/securitycheck-pro-database-update" target="_blank" rel="noopener noreferrer">' . Text::_('COM_SECURITYCHECKPRO_MORE_INFO') . '</a>',
            ]
        );

        // Spam Protection
        renderExistsStatusCard(
            $spamProtectionPluginStatus,
            (bool) $this->spam_protection_plugin_exists,
            (bool) $this->spam_protection_plugin_enabled,
            'fa-user-shield',
            [
                'enable'  => '<button id="enable_spam_protection_button" class="btn btn-sm btn-success" type="button"><i class="fa fa-check"></i> ' . Text::_('COM_SECURITYCHECKPRO_ENABLE') . '</button>',
                'disable' => '<button id="disable_spam_protection_button" class="btn btn-sm btn-danger" type="button"><i class="fa fa-power-off"></i> ' . Text::_('COM_SECURITYCHECKPRO_DISABLE') . '</button>',
                'more'    => '<a class="btn btn-sm btn-info" href="https://securitycheck.protegetuordenador.com/index.php/our-products/securitycheck-spam-protection" target="_blank" rel="noopener noreferrer">' . Text::_('COM_SECURITYCHECKPRO_MORE_INFO') . '</a>',
            ]
        );
        ?>
    </div>

    <!-- MAIN SECTIONS: IZQ (Subscriptions + Configuration) / DCHA (Stats) -->
    <div class="scp-section scp-two-cols">

        <!-- IZQUIERDA -->
        <div>
            <!-- Subscriptions (arriba) -->
			<?php            // --- Subscriptions + (opcional) Download ID side-by-side ---
			/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
			$mainframe      = Factory::getApplication();
			$existsUpdateDb = (bool) $this->update_database_plugin_exists;
			$existsTrack    = (bool) $this->trackactions_plugin_exists;

			$expired   = false;
			// SCP core
			$scpVer       = e($this->version_scp);
			$scpSubStatus = $mainframe->getUserState('scp_subscription_status', Text::_('COM_SECURITYCHECKPRO_NOT_DEFINED'));
			$scpBadge     = 'bg-dark';
			if ($scpSubStatus === Text::_('COM_SECURITYCHECKPRO_ACTIVE')) { $scpBadge = 'bg-success'; }
			elseif ($scpSubStatus === Text::_('COM_SECURITYCHECKPRO_EXPIRED')) { $scpBadge = 'bg-danger'; $expired = true; }

			// Update Database
			$updBadge = 'bg-dark';
			if (!$existsUpdateDb) { $updLabel = Text::_('COM_SECURITYCHECKPRO_PLUGIN_NOT_INSTALLED'); $updVer=''; }
			else {
				$updVer   = e($this->version_update_database);
				$updLabel = $mainframe->getUserState('scp_update_database_subscription_status', Text::_('COM_SECURITYCHECKPRO_NOT_DEFINED'));
				if ($updLabel === Text::_('COM_SECURITYCHECKPRO_ACTIVE')) { $updBadge = 'bg-success'; }
				elseif ($updLabel === Text::_('COM_SECURITYCHECKPRO_EXPIRED')) { $updBadge = 'bg-danger'; $expired = true; }
			}

			// Track Actions
			$trkBadge = 'bg-dark';
			if (!$existsTrack) { $trkLabel = Text::_('COM_SECURITYCHECKPRO_PLUGIN_NOT_INSTALLED'); $trkVer=''; }
			else {
				$trkVer   = e($this->version_trackactions);
				$trkLabel = $mainframe->getUserState('trackactions_subscription_status', Text::_('COM_SECURITYCHECKPRO_NOT_DEFINED'));
				if ($trkLabel === Text::_('COM_SECURITYCHECKPRO_ACTIVE')) { $trkBadge = 'bg-success'; }
				elseif ($trkLabel === Text::_('COM_SECURITYCHECKPRO_EXPIRED')) { $trkBadge = 'bg-danger'; $expired = true; }
			}

			// Enlace para editar Download ID
			$downloadIdLink = u('index.php?option=com_config&view=component&component=com_securitycheckpro&path=&return=' . base64_encode(Uri::getInstance()->toString()), false);
			?>

			<?php if (empty($this->downloadid)) : ?>
				<!-- Cuando NO hay Download ID: dos cards en una fila -->
				<div class="scp-two-up mb-3">
					<!-- Subscriptions (compact) -->
					<div class="card shadow-soft compact">
						<div class="card-header">
							<i class="fa fa-ellipsis-v"></i>
							<a id="subscriptions_status"
							   data-bs-toggle="tooltip"
							   title="<?php echo Text::_('COM_SECURITYCHECKPRO_SUBSCRIPTIONS_STATUS_EXPLAINED'); ?>"
							   href="javascript:void(0)" class="text-reset text-decoration-none">
							   <?php echo Text::_('COM_SECURITYCHECKPRO_SUBSCRIPTIONS_STATUS'); ?>
							</a>
						</div>
						<div class="card-body">
							<p class="mb-2">
								Securitycheck Pro
								(<span id="scp_version" class="badge bg-info"
									   data-bs-toggle="tooltip"
									   title="<?php echo Text::_('COM_SECURITYCHECKPRO_VERSION_INSTALLED') . ': ' . $scpVer; ?>">
									<?php echo $scpVer; ?>
								</span>)
								&nbsp;<span class="badge <?php echo a($scpBadge); ?>"><?php echo e($scpSubStatus); ?></span>
							</p>
							<p class="mb-2">
								Securitycheck Pro Update Database
								<?php if ($existsUpdateDb): ?>
									(<span id="update_database_version" class="badge bg-info"
										   data-bs-toggle="tooltip"
										   title="<?php echo Text::_('COM_SECURITYCHECKPRO_VERSION_INSTALLED') . ': ' . $updVer; ?>">
										<?php echo $updVer; ?>
									</span>)
								<?php endif; ?>
								&nbsp;<span class="badge <?php echo a($updBadge); ?>"><?php echo e($updLabel); ?></span>
							</p>
							<p class="mb-0">
								Track Actions
								<?php if ($existsTrack): ?>
									(<span id="trackactions_version" class="badge bg-info"
										   data-bs-toggle="tooltip"
										   title="<?php echo Text::_('COM_SECURITYCHECKPRO_VERSION_INSTALLED') . ': ' . $trkVer; ?>">
										<?php echo $trkVer; ?>
									</span>)
								<?php endif; ?>
								&nbsp;<span class="badge <?php echo a($trkBadge); ?>"><?php echo e($trkLabel); ?></span>
							</p>

							<?php if ($expired): ?>
								<div class="mt-3">
									<a class="btn btn-sm btn-info" href="https://securitycheck.protegetuordenador.com/subscriptions" target="_blank" rel="noopener noreferrer">
										<?php echo Text::_('COM_SECURITYCHECKPRO_RENEW'); ?>
									</a>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<!-- Download ID (nuevo card compacto) -->
					<div class="card border-info shadow-soft compact">
						<div class="card-header">
							<i class="fa fa-key"></i>
							<?php echo Text::_('COM_SECURITYCHECKPRO_DOWNLOAD_ID'); ?>
						</div>
						<div class="card-body">
							<p class="mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_DOWNLOAD_ID_MESSAGE'); ?></p>
							<a href="<?php echo $downloadIdLink; ?>" class="btn btn-sm btn-primary">
								<i class="fa fa-edit"></i>
								<?php echo Text::_('COM_SECURITYCHECKPRO_FILL_IT_NOW'); ?>
							</a>
						</div>
					</div>
				</div>
			<?php else: ?>
				<!-- Cuando SÍ hay Download ID: subscriptions normal -->
				<div class="card shadow-soft mb-3">
					<div class="card-header">
						<i class="fa fa-ellipsis-v"></i>
						<a id="subscriptions_status"
						   data-bs-toggle="tooltip"
						   title="<?php echo Text::_('COM_SECURITYCHECKPRO_SUBSCRIPTIONS_STATUS_EXPLAINED'); ?>"
						   href="javascript:void(0)" class="text-reset text-decoration-none">
						   <?php echo Text::_('COM_SECURITYCHECKPRO_SUBSCRIPTIONS_STATUS'); ?>
						</a>
					</div>
					<div class="card-body">
						<p class="mb-2">
							Securitycheck Pro
							(<span id="scp_version" class="badge bg-info"
								   data-bs-toggle="tooltip"
								   title="<?php echo Text::_('COM_SECURITYCHECKPRO_VERSION_INSTALLED') . ': ' . $scpVer; ?>">
								<?php echo $scpVer; ?>
							</span>)
							&nbsp;<span class="badge <?php echo a($scpBadge); ?>"><?php echo e($scpSubStatus); ?></span>
						</p>

						<p class="mb-2">
							Securitycheck Pro Update Database
							<?php if ($existsUpdateDb): ?>
								(<span id="update_database_version" class="badge bg-info"
									   data-bs-toggle="tooltip"
									   title="<?php echo Text::_('COM_SECURITYCHECKPRO_VERSION_INSTALLED') . ': ' . $updVer; ?>">
									<?php echo $updVer; ?>
								</span>)
							<?php endif; ?>
							&nbsp;<span class="badge <?php echo a($updBadge); ?>"><?php echo e($updLabel); ?></span>
						</p>

						<p class="mb-0">
							Track Actions
							<?php if ($existsTrack): ?>
								(<span id="trackactions_version" class="badge bg-info"
									   data-bs-toggle="tooltip"
									   title="<?php echo Text::_('COM_SECURITYCHECKPRO_VERSION_INSTALLED') . ': ' . $trkVer; ?>">
									<?php echo $trkVer; ?>
								</span>)
							<?php endif; ?>
							&nbsp;<span class="badge <?php echo a($trkBadge); ?>"><?php echo e($trkLabel); ?></span>
						</p>

						<?php if ($expired): ?>
							<div class="mt-3">
								<a class="btn btn-sm btn-info" href="https://securitycheck.protegetuordenador.com/subscriptions" target="_blank" rel="noopener noreferrer">
									<?php echo Text::_('COM_SECURITYCHECKPRO_RENEW'); ?>
								</a>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

            <!-- Configuration: agrupa Easy Config + Lock -->
            <div class="card shadow-soft mb-3">
			  <div class="card-header">
				<i class="fa fa-sliders-h"></i>
				<?php echo Text::_('COM_SECURITYCHECKPRO_CONFIGURATION') ?: 'Configuration'; ?>
			  </div>

			  <div class="card-body">
				<div class="sci-grid-two">

				  <!-- Easy Config -->
				  <div class="sci-box">
					<div class="sci-title">
					  <i class="fa fa-cog"></i>
					  <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_EASY_CONFIG'); ?>
					  <!-- Tooltip con la definición -->
					  <span class="fa fa-info-circle text-muted"
						  data-bs-toggle="tooltip"
						  data-bs-html="true"
						  data-bs-title="<?php echo htmlspecialchars(Text::_('COM_SECURITYCHECKPRO_CPANEL_EASY_CONFIG_DEFINITION'), ENT_QUOTES, 'UTF-8'); ?>">
					</span>
					</div>
					<div class="sci-body">
					  <div class="mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_EASY_CONFIG_STATUS'); ?></div>
					  <?php if ($this->easy_config_applied): ?>
						<span class="scp-chip scp-chip--ok"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_APPLIED'); ?></span>
						<div class="mt-2">
						  <button id="apply_default_config_button" class="btn btn-sm btn-primary" type="button">
							<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_APPLY_DEFAULT_CONFIG'); ?>
						  </button>
						</div>
					  <?php else: ?>
						<span class="scp-chip scp-chip--dark"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_NOT_APPLIED'); ?></span>
						<div class="mt-2">
						  <button id="apply_easy_config_button" class="btn btn-sm btn-success" type="button">
							<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_APPLY_EASY_CONFIG'); ?>
						  </button>
						</div>
					  <?php endif; ?>
					</div>
				  </div>

				  <!-- Lock Status (mismo look & feel) -->
				  <div class="sci-box">
					<div class="sci-title">
					  <i class="fa fa-lock"></i>
					  <?php echo Text::_('COM_SECURITYCHECKPRO_LOCK_STATUS'); ?>
					</div>
					<div class="sci-body">
					  <?php if ($this->lock_status): ?>
						<span class="scp-chip scp-chip--ok mb-2 d-inline-block">
						  <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_APPLIED'); ?>
						</span>
						<div>
						  <button id="unlock_tables_button" class="btn btn-sm btn-info" type="button">
							<?php echo Text::_('COM_SECURITYCHECKPRO_UNLOCK_TABLES'); ?>
						  </button>
						</div>
					  <?php else: ?>
						<span class="scp-chip scp-chip--dark mb-2 d-inline-block">
						  <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_NOT_APPLIED'); ?>
						</span>
						<div>
						  <button id="lock_tables_button" class="btn btn-sm btn-info" type="button">
							<?php echo Text::_('COM_SECURITYCHECKPRO_LOCK_TABLES'); ?>
						  </button>
						</div>
					  <?php endif; ?>

					  <div class="mt-2">
						<a class="btn btn-sm btn-outline-dark"
						   href="https://scpdocs.securitycheckextensions.com/dashboard/cpanel/lock-tables-cpanel"
						   target="_blank" rel="noopener noreferrer">
						   <?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>
						</a>
					  </div>
					</div>
				  </div>

				</div><!-- /.sci-grid-two -->
			  </div><!-- /.card-body -->
			</div><!-- /Configuration card -->            
        </div>

        <!-- DERECHA: Stats compactas -->
        <div>
            <div class="card shadow-soft mb-3">
                <div class="card-header"><i class="fa fa-chart-pie"></i> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_STATISTICS'); ?></div>
                <div class="card-body">
                    <ul class="nav nav-tabs" role="tablist" id="scpStatsTabs">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#historic" role="tab"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_HISTORIC'); ?></a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#detail" role="tab"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_DETAIL'); ?></a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#lists" role="tab"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_LISTS'); ?></a></li>
                    </ul>

                    <div class="tab-content pt-2">
                        <div class="tab-pane active" id="historic" role="tabpanel">
                            <h6 class="centrado mb-2"><?php echo Text::_('COM_SECURITYCHECKPRO_GRAPHIC_HEADER'); ?></h6>
                            <canvas id="piechart" width="100%" height="28"></canvas>
                        </div>

                        <div class="tab-pane" id="detail" role="tabpanel">
                            <table class="table table-sm table-striped align-middle mb-2">
                                <thead>
                                <tr>
                                    <th><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_PERIOD'); ?></th>
                                    <th class="text-right"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_ENTRIES'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td><a href="<?php echo u(sprintf($logUrl, $lastYear . '-01-01 00:00:00', $lastYear . '-12-31 23:59:59')); ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_LAST_YEAR'); ?></a></td>
                                    <td class="text-right"><b><?php echo (int) $this->last_year_logs; ?></b></td>
                                </tr>
                                <tr>
                                    <td><a href="<?php echo u(sprintf($logUrl, gmdate('Y') . '-01-01', gmdate('Y') . '-12-31 23:59:59')); ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_THIS_YEAR'); ?></a></td>
                                    <td class="text-right"><b><?php echo (int) $this->this_year_logs; ?></b></td>
                                </tr>
                                <?php
								$startLastMonth = new \DateTimeImmutable('first day of last month 00:00:00', new \DateTimeZone('UTC'));
								$endLastMonth   = $startLastMonth->modify('last day of this month 23:59:59');
								?>
								<tr>
									<td>
										<a href="<?php echo u(sprintf(
											$logUrl,
											$startLastMonth->format('Y-m-d'),
											$endLastMonth->format('Y-m-d H:i:s')
										)); ?>">
											<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_LAST_MONTH'); ?>
										</a>
									</td>
									<td class="text-right">
										<b><?php echo (int) $this->last_month_logs; ?></b>
									</td>
								</tr>
                                <?php
								$startThisMonth = new \DateTimeImmutable('first day of this month 00:00:00', new \DateTimeZone('UTC'));
								$endThisMonth   = $startThisMonth->modify('last day of this month 23:59:59');
								?>
								<tr>
									<td>
										<a href="<?php echo u(sprintf(
											$logUrl,
											$startThisMonth->format('Y-m-d'),
											$endThisMonth->format('Y-m-d H:i:s')
										)); ?>">
											<?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_THIS_MONTH'); ?>
										</a>
									</td>
									<td class="text-right">
										<b><?php echo (int) $this->this_month_logs; ?></b>
									</td>
								</tr>
                                <tr>
                                    <td><a href="<?php echo u(sprintf($logUrl, gmdate('Y-m-d', time() - 7 * 24 * 3600), gmdate('Y-m-d 23:59:59'))); ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_LAST_7_DAYS'); ?></a></td>
                                    <td class="text-right"><b><?php echo (int) $this->last_7_days; ?></b></td>
                                </tr>
                                <tr>
                                    <?php
                                    $date = new DateTime('now', new DateTimeZone('UTC'));
                                    $date->setDate((int) gmdate('Y'), (int) gmdate('n'), (int) gmdate('j'));
                                    $date->modify('-1 day');
                                    $yesterday = $date->format('Y-m-d');
                                    $date->modify('+1 day');
                                    ?>
                                    <td><a href="<?php echo u(sprintf($logUrl, $yesterday, $date->format('Y-m-d'))); ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_YESTERDAY'); ?></a></td>
                                    <td class="text-right"><b><?php echo (int) $this->yesterday; ?></b></td>
                                </tr>
                                <tr>
                                    <?php $expiry = (clone $date)->modify('+1 day'); ?>
                                    <td><a href="<?php echo u(sprintf($logUrl, $date->format('Y-m-d'), $expiry->format('Y-m-d'))); ?>"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_TODAY'); ?></a></td>
                                    <td class="text-right"><b><?php echo (int) $this->today; ?></b></td>
                                </tr>
                                </tbody>
                            </table>

                            <div class="alert alert-warning py-2 mb-0">
                                <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_HELP'); ?>
                            </div>
                        </div>

                        <div class="tab-pane" id="lists" role="tabpanel">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                <tr>
                                    <th><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_LIST'); ?></th>
                                    <th class="text-right"><?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_LIST_ELEMENTS'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td><?php echo Text::_('COM_SECURITYCHECKPRO_BLACKLIST'); ?></td>
                                    <td class="text-right"><b><?php $black = (int) count((array) $this->blacklist_elements); echo $black; ?></b></td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php if ($black - 1 >= 0): ?><span class="badge bg-danger"><?php echo e($this->blacklist_elements[$black - 1] ?? ''); ?></span><?php endif; ?>
                                        <?php if ($black - 2 >= 0): ?><span class="badge bg-danger"><?php echo e($this->blacklist_elements[$black - 2] ?? ''); ?></span><?php endif; ?>
                                        <?php if ($black - 3 >= 0): ?><span class="badge bg-danger"><?php echo Text::_('COM_SECURITYCHECKPRO_MORE'); ?></span><?php endif; ?>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td><?php echo Text::_('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST'); ?></td>
                                    <td class="text-right"><b><?php $dynamic = (int) count((array) $this->dynamic_blacklist_elements); echo $dynamic; ?></b></td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php if ($dynamic - 1 >= 0): ?><span class="badge bg-warning"><?php echo e($this->dynamic_blacklist_elements[$dynamic - 1] ?? ''); ?></span><?php endif; ?>
                                        <?php if ($dynamic - 2 >= 0): ?><span class="badge bg-warning"><?php echo e($this->dynamic_blacklist_elements[$dynamic - 2] ?? ''); ?></span><?php endif; ?>
                                        <?php if ($dynamic - 3 >= 0): ?><span class="badge bg-warning"><?php echo Text::_('COM_SECURITYCHECKPRO_MORE'); ?></span><?php endif; ?>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td><?php echo Text::_('COM_SECURITYCHECKPRO_WHITELIST'); ?></td>
                                    <td class="text-right"><b><?php $white = (int) count((array) $this->whitelist_elements); echo $white; ?></b></td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php if ($white - 1 >= 0): ?><span class="badge bg-success"><?php echo e($this->whitelist_elements[$white - 1] ?? ''); ?></span><?php endif; ?>
                                        <?php if ($white - 2 >= 0): ?><span class="badge bg-success"><?php echo e($this->whitelist_elements[$white - 2] ?? ''); ?></span><?php endif; ?>
                                        <?php if ($white - 3 >= 0): ?><span class="badge bg-success"><?php echo Text::_('COM_SECURITYCHECKPRO_MORE'); ?></span><?php endif; ?>
                                    </td>
                                    <td></td>
                                </tr>
                                </tbody>
                            </table>

                            <div class="btn-toolbar">
                                <div class="btn-group">
                                    <button id="manage_lists_button" class="btn btn-info" type="button">
                                        <i class="fa fa-wrench"></i>
                                        <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_MANAGE_LISTS'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div> <!-- /tab-content -->
					<div class="alert alert-info py-2 mt-3 mb-0">
  <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_DISCLAIMER_TEXT'); ?>
</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Help us -->
    <div class="card shadow-soft mt-3">
        <div class="card-body text-center">
            <h5 class="card-title mb-2"><i class="fa fa-thumbs-up"></i> <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_HELP_US'); ?></h5>
            <p class="card-text mb-2"><?php echo $review; ?></p>
            <p class="mb-0"><i class="fa fa-language"></i> <a href="<?php echo a($translatorUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo e($translatorName); ?></a></p>
        </div>
    </div>

    <input type="hidden" name="option" value="com_securitycheckpro" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="1" />
    <input type="hidden" name="controller" value="cpanel" />
</form>
