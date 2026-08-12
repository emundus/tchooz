<?php
declare(strict_types=1);

/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\ProtectionModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Site\Model\JsonModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Helper\OverallScoreHelper;

class SysinfoModel extends BaseModel
{    
	/**
     * Some system values
	 * @var array<string, mixed>|null $info 
     */
    protected $info = null;
	
	/**
	 * Method to get the system information
	 *
	 * @return array<string, mixed> system information values
	 */
	public function getInfo(): array
	{
		if ($this->info !== null) {
			return $this->info;
		}

		/** @var \Joomla\CMS\Application\CMSApplicationInterface $app */
		$app  = Factory::getApplication();
		/** @var User|null $user */
		$user = $app->getIdentity();

		if (!$user || !$user->authorise('core.manage', 'com_securitycheckpro')) {
			return $this->info = [
				'authorized' => false,
				'phpversion' => PHP_VERSION,
				'version'    => (new Version())->getLongVersion(),
			];
		}

		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Helpers (compatibles PHP 8.1)
		$toBytes = static function (string $val): int {
			$v = trim($val);
			if ($v === '' || $v === '-1') {
				return -1;
			}
			$num = (float) $v;
			$suf = strtolower((string) substr($v, -1));
			if ($suf === 'g') { return (int) ($num * 1024 * 1024 * 1024); }
			if ($suf === 'm') { return (int) ($num * 1024 * 1024); }
			if ($suf === 'k') { return (int) ($num * 1024); }
			return (int) $num;
		};

		$bytesToHuman = static function (int $bytes): string {
			if ($bytes < 0) {
				return 'unlimited';
			}
			$units = ['B','KB','MB','GB','TB'];
			$i = 0;
			$val = (float) $bytes;
			while ($val >= 1024 && $i < 4) {
				$val /= 1024;
				$i++;
			}
			$s = sprintf('%.2f %s', $val, $units[$i]);
			return rtrim(rtrim($s, '0'), '.');
		};

		// max_allowed_packet (s�lo MySQL/MariaDB)
		$maxAllowedPacketMB = 0;
		try {
			$driverName = strtolower($db->getName());
			if (strpos($driverName, 'mysql') !== false) {
				$db->setQuery('SELECT @@max_allowed_packet AS map');
				/** @var object|null $row */
				$row = $db->loadObject();
				if ($row !== null && isset($row->map)) {
					$maxAllowedPacketMB = (int) floor(((int) $row->map) / 1024 / 1024);
				}
			}
		} catch (\Throwable) {
			$maxAllowedPacketMB = 0;
		}

		// memory_limit (sin phpinfo)
		$iniLocal     = (string) (ini_get('memory_limit') ?: '');
		$localBytes   = $toBytes($iniLocal);
		$params       = ComponentHelper::getParams('com_securitycheckpro');
		$configured   = (string) $params->get('memory_limit', $iniLocal !== '' ? $iniLocal : '512M');
		$configuredBy = ($configured !== '' ? 'component' : 'php');
		$configuredB  = $toBytes($configured);

		$memoryLimit = [
			'local'            => $iniLocal !== '' ? $iniLocal : 'unknown',
			'local_bytes'      => $localBytes,
			'local_human'      => $bytesToHuman($localBytes),
			'configured_by'    => $configuredBy,
			'configured'       => $configured,
			'configured_bytes' => $configuredB,
			'configured_human' => $bytesToHuman($configuredB),
		];

		// Datos dependientes del componente
		$core = [
			'coreinstalled'                    => null,
			'corelatest'                       => null,
			'files_with_incorrect_permissions' => null,
			'files_with_bad_integrity'         => null,
			'vuln_extensions'                  => null,
			'suspicious_files'                 => null,
			'backend_protection'               => null,
			'twofactor_enabled'                => null,
			'overall'                          => null,
			'last_check'                       => null,
			'last_check_integrity'             => null,
			'kickstart_exists'                 => null,
		];
		try {
			/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Site\Model\JsonModel $values */
			$values = new JsonModel();
			$values->getStatus(false);
			$data = is_array($values->data) ? $values->data : [];
			foreach ($core as $k => $_) {
				$core[$k] = $data[$k] ?? null;
			}
		} catch (\Throwable) {
			// deja $core con nulls
		}

		// Estado de plugins
		$pluginState = [
			'firewall_plugin_enabled'         => null,
			'cron_plugin_enabled'             => null,
			'spam_protection_plugin_enabled'  => null,
		];
		try {
			$pluginState['firewall_plugin_enabled']        = (bool) $this->PluginStatus(1);
			$pluginState['cron_plugin_enabled']            = (bool) $this->PluginStatus(2);
			$pluginState['spam_protection_plugin_enabled'] = (bool) $this->PluginStatus(5);
		} catch (\Throwable) {}

		// Opciones del firewall
		$firewallOptions = [];
		try {
			$fw = $this->getConfig();
			// Forzamos array, nunca null
			$firewallOptions = is_array($fw) ? $fw : (array) $fw;
		} catch (\Throwable) {
			$firewallOptions = [];
		}

		// Protecci�n .htaccess
		$htaccessProtection = [];
		try {
			$pm = new ProtectionModel();
			/** @var array<string, int|list<string>|string> $hp */
			$hp = $pm->GetConfigApplied();

			$htaccessProtection = $hp;
		} catch (\Throwable) {
			$htaccessProtection = [];
		}

		// Logs pendientes
		$unreadLogs = 0;
		try {
			$unreadLogs = (int) $this->LogsPending();
		} catch (\Throwable) {
			$unreadLogs = 0;
		}

		// Montamos $info para getOverall() evitando �ndices indefinidos
		$overallInput = [
			'phpversion'                    => PHP_VERSION,
			'version'                       => (new Version())->getLongVersion(),
			'max_allowed_packet'            => $maxAllowedPacketMB,
			'memory_limit'                  => $memoryLimit,
			'coreinstalled'                 => (string) ($core['coreinstalled'] ?? ''),
			'corelatest'                    => (string) ($core['corelatest'] ?? ''),
			'unread_logs'                   => (int)    ($unreadLogs),
			'files_with_incorrect_permissions' => (int) ($core['files_with_incorrect_permissions'] ?? 0),
			'files_with_bad_integrity'      => (int)    ($core['files_with_bad_integrity'] ?? 0),
			'vuln_extensions'               => (int)    ($core['vuln_extensions'] ?? 0),
			'suspicious_files'              => (int)    ($core['suspicious_files'] ?? 0),
			'backend_protection'            => (bool)   ($core['backend_protection'] ?? false),
			'twofactor_enabled'             => (int)    ($core['twofactor_enabled'] ?? 0),
			'last_check'                    => (string) ($core['last_check'] ?? ''),
			'last_check_integrity'          => (string) ($core['last_check_integrity'] ?? ''),
			'firewall_plugin_enabled'       => (bool)   ($pluginState['firewall_plugin_enabled'] ?? false),
			'spam_protection_plugin_enabled'=> (bool)   ($pluginState['spam_protection_plugin_enabled'] ?? false),
			'firewall_options'              => (array)  $firewallOptions,
			'htaccess_protection'           => (array)  $htaccessProtection,
		];

		$overallWebFirewall = null;
		try {
			$overallWebFirewall = $this->getOverall($overallInput, 2);
		} catch (\Throwable) {
			$overallWebFirewall = null;
		}

		// Resultado final (sin �ndices indefinidos)
		$this->info = [
			'authorized'                    => true,
			'phpversion'                    => PHP_VERSION,
			'version'                       => (new Version())->getLongVersion(),
			'platform'                      => 'Not defined',
			'max_allowed_packet'            => $maxAllowedPacketMB,
			'memory_limit'                  => $memoryLimit,
			'coreinstalled'                 => $overallInput['coreinstalled'],
			'corelatest'                    => $overallInput['corelatest'],
			'files_with_incorrect_permissions' => $overallInput['files_with_incorrect_permissions'],
			'files_with_bad_integrity'      => $overallInput['files_with_bad_integrity'],
			'vuln_extensions'               => $overallInput['vuln_extensions'],
			'suspicious_files'              => $overallInput['suspicious_files'],
			'backend_protection'            => $overallInput['backend_protection'],
			'kickstart_exists'              => $core['kickstart_exists'] ?? null,
			'firewall_options'              => $overallInput['firewall_options'],
			'twofactor_enabled'             => $overallInput['twofactor_enabled'],
			'overall_joomla_configuration'  => $core['overall'] ?? null,
			'cron_plugin_enabled'           => (bool) ($pluginState['cron_plugin_enabled'] ?? false),
			'firewall_plugin_enabled'       => (bool) ($pluginState['firewall_plugin_enabled'] ?? false),
			'spam_protection_plugin_enabled'=> (bool) ($pluginState['spam_protection_plugin_enabled'] ?? false),
			'unread_logs'                   => $unreadLogs,
			'last_check'                    => $overallInput['last_check'],
			'last_check_integrity'          => $overallInput['last_check_integrity'],
			'htaccess_protection'           => $overallInput['htaccess_protection'],
			'overall_web_firewall'          => $overallWebFirewall,
		];

		return $this->info;
	}

	/**
	 * Calcula un �overall� robusto sin depender de �ndices indefinidos.
	 *
	 * @param array<string,mixed> $info
	 * @param int                 $opcion
	 * @return int
	 */
	public function getOverall(array $info, int $opcion): int
	{
		return OverallScoreHelper::score($info, $opcion);
	}

	
	/**
	 * Devuelve el HTML de una tarjeta de estado (list-group con badge, fix button y modal opcional).
	 *
	 * @phpstan-param array{
	 *   colClass?: string,
	 *   title?: string,
	 *   ok?: bool,
	 *   problems?: int,
	 *   valueBadge?: string,
	 *   fixButtonId?: string,
	 *   modalId?: string,
	 *   modalText?: string,
	 *   headerItemClass?: string,
	 *   valueBadgeClass?: string
	 * } $cfg
	 *
	 * @return string
	 */
    public function renderStatusItem(array $cfg): string
    {
        $colClass    = $cfg['colClass'] ?? 'col-xl-3 mb-3';
        $title       = $cfg['title'] ?? '';
        $ok          = !empty($cfg['ok']);
        $problems    = (int) ($cfg['problems'] ?? 0);
        $valueBadge  = $cfg['valueBadge'] ?? null;
        $fixButtonId = $cfg['fixButtonId'] ?? null;
        $modalId     = $cfg['modalId'] ?? null;
        $modalText   = $cfg['modalText'] ?? null;
		$headerItemClass = $cfg['headerItemClass'] ?? 'list-group-item-primary';
		// Si no se especifica un color explícito para el badge de valor, lo atamos al estado
		// (ok=verde, problema=rojo) en vez de forzar siempre rojo, que inducía a error cuando
		// $ok era true (p.ej. la versión de Joomla instalada se veía en rojo estando actualizada).
		$valueBadgeClass = $cfg['valueBadgeClass'] ?? ($ok ? 'bg-success' : 'bg-danger');
		$statusAttr      = $ok ? 'ok' : 'problem';
		$borderClass     = $ok ? 'border-success' : 'border-danger';

        ob_start();
        ?>
        <div class="<?php echo htmlspecialchars($colClass, ENT_QUOTES, 'UTF-8'); ?>" data-status="<?php echo $statusAttr; ?>">
            <ul class="list-group h-100 border-start border-3 <?php echo $borderClass; ?>">
               <li class="list-group-item <?php echo htmlspecialchars($headerItemClass, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo $title; ?>
                </li>
                <li class="list-group-item">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <?php if ($valueBadge !== null): ?>
						  <span class="badge <?php echo htmlspecialchars($valueBadgeClass, ENT_QUOTES, 'UTF-8'); ?>">
							<?php echo htmlspecialchars($valueBadge, ENT_QUOTES, 'UTF-8'); ?>
						  </span>
						<?php endif; ?>

                        <?php if ($ok): ?>
                            <span class="badge bg-success"><i class="fa fa-check" aria-hidden="true"></i> OK</span>
                        <?php else: ?>
                            <span class="badge bg-danger">
                                <i class="fa fa-triangle-exclamation" aria-hidden="true"></i>
                                <?php echo Text::sprintf('COM_SECURITYCHECKPRO_SECURITY_PROBLEM_FOUND', $problems > 0 ? $problems : 1); ?>
                            </span>

                            <?php if ($fixButtonId): ?>
                                <button class="btn btn-outline-primary btn-sm" type="button" id="<?php echo htmlspecialchars($fixButtonId, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="icon-cog" aria-hidden="true"></span>
                                    <span class="visually-hidden"><?php echo Text::_('JTOOLBAR_OPTIONS'); ?></span>
                                </button>
                            <?php endif; ?>

                            <?php if ($modalId && $modalText): ?>
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#<?php echo htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($modalId && $modalText): ?>
                        <div class="modal fade" id="<?php echo htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8'); ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header alert alert-info mb-0">
                                        <h2 class="modal-title h5">
                                            <?php echo Text::_('COM_SECURITYCHECKPRO_WHY_IS_THIS_IMPORTANT'); ?>
                                        </h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
                                    </div>
                                    <div class="modal-body p-3">
                                        <p class="fs-6 mb-0"><?php echo $modalText; ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
        <?php

        return ob_get_clean();
    }
	
	/**
	 * Devuelve el HTML de un �tem de informaci�n.
	 *
	 * @phpstan-param array{
	 *   colClass?: string,
	 *   headerItemClass?: string,
	 *   title?: string,
	 *   value?: string,
	 *   valueClass?: string,
	 *   isHtml?: bool,
	 *   prefix?: string,
	 *   suffix?: string
	 * } $cfg
	 *
	 * @return string
	 */
	public function renderInfoItem(array $cfg): string
	{
		$colClass        = $cfg['colClass']        ?? 'col-12 col-md-6 col-xl-3';
		$headerItemClass = $cfg['headerItemClass'] ?? 'list-group-item-success';
		$title           = $cfg['title']           ?? '';
		$value           = $cfg['value']           ?? '';
		$valueClass      = $cfg['valueClass']      ?? '';
		$isHtml          = !empty($cfg['isHtml']);
		$prefix          = isset($cfg['prefix']) ? (string)$cfg['prefix'] : '';
		$suffix          = isset($cfg['suffix']) ? (string)$cfg['suffix'] : '';

		$valueOut = $isHtml ? (string)$value : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
		$hasValue = ($valueOut !== '');

		ob_start(); ?>
		<div class="<?php echo htmlspecialchars($colClass, ENT_QUOTES, 'UTF-8'); ?>">
			<ul class="list-group h-100">
				<li class="list-group-item <?php echo htmlspecialchars($headerItemClass, ENT_QUOTES, 'UTF-8'); ?>">
					<?php echo $title; ?>
				</li>
				<li class="list-group-item">
					<span class="<?php echo htmlspecialchars($valueClass, ENT_QUOTES, 'UTF-8'); ?>">
						<?php
						echo $hasValue
							? $prefix . $valueOut . $suffix
							: Text::_('JGLOBAL_FIELD_VALUE_UNSPECIFIED');
						?>
					</span>
				</li>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

}
