<?php
declare(strict_types=1);

/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

// @codeCoverageIgnoreStart
defined('_JEXEC') or die;
// @codeCoverageIgnoreEnd

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Table\Extension as ExtensionTable;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;

final class CpanelModel extends BaseModel
{
    /**
     * Configuración por defecto
     *
     * @var array<string, int|list<string>|string>
     */
    private array $defaultConfig = [
        'blacklist'                         => '',
        'whitelist'                         => '',
        'dynamic_blacklist'                 => 1,
        'dynamic_blacklist_time'            => 60000,
        'dynamic_blacklist_counter'         => 2,
        'blacklist_email'                   => 0,
        'priority1'                         => 'Whitelist',
        'priority2'                         => 'DynamicBlacklist',
        'priority3'                         => 'Blacklist',
        'methods'                           => 'GET,POST,REQUEST',
        'logs_attacks'                      => 1,
        'log_limits_per_ip_and_day'         => 0,
        'redirect_after_attack'             => 0,
        'redirect_options'                  => 1,
        'second_level'                      => 1,
        'second_level_redirect'             => 1,
        'second_level_limit_words'          => 3,
        'second_level_words'                => 'ZHJvcCx1cGRhdGUsc2V0LGFkbWluLHNlbGVjdCx1c2VyLHBhc3N3b3JkLGNvbmNhdCxsb2dpbixsb2FkX2ZpbGUsYXNjaWksY2hhcix1bmlvbixmcm9tLGdyb3VwIGJ5LG9yZGVyIGJ5LGluc2VydCx2YWx1ZXMscGFzcyx3aGVyZSxzdWJzdHJpbmcsYmVuY2htYXJrLG1kNSxzaGExLHNjaGVtYSx2ZXJzaW9uLHJvd19jb3VudCxjb21wcmVzcyxlbmNvZGUsaW5mb3JtYXRpb25fc2NoZW1hLHNjcmlwdCxqYXZhc2NyaXB0LGltZyxzcmMsaW5wdXQsYm9keSxpZnJhbWUsZnJhbWUsJF9QT1NULGV2YWwsJF9SRVFVRVNULGJhc2U2NF9kZWNvZGUsZ3ppbmZsYXRlLGd6dW5jb21wcmVzcyxnemluZmxhdGUsc3RydHJleGVjLHBhc3N0aHJ1LHNoZWxsX2V4ZWMsY3JlYXRlRWxlbWVudA==',
        'email_active'                      => 0,
        'email_subject'                     => 'Securitycheck Pro alert!',
        'email_body'                        => 'Securitycheck Pro has generated a new alert. Please, check your logs.',
        'email_add_applied_rule'            => 1,
        'email_to'                          => '',
        'email_from_domain'                 => 'me@mydomain.com',
        'email_from_name'                   => 'Your name',
        'email_max_number'                  => 20,
        'check_header_referer'              => 1,
        'check_base_64'                     => 1,
        'base64_exceptions'                 => 'com_hikashop',
        'strip_tags_exceptions'             => 'com_jdownloads,com_hikashop,com_phocaguestbook',
        'duplicate_backslashes_exceptions'  => 'com_kunena,com_securitycheckprocontrolcenter',
        'line_comments_exceptions'          => 'com_comprofiler',
        'sql_pattern_exceptions'            => '',
        'if_statement_exceptions'           => '',
        'using_integers_exceptions'         => 'com_dms,com_comprofiler,com_jce,com_contactenhanced,com_securitycheckprocontrolcenter',
        'escape_strings_exceptions'         => 'com_kunena,com_jce',
        'lfi_exceptions'                    => '',
        'second_level_exceptions'           => 'com_securitycheckprocontrolcenter',
        'session_protection_active'         => 1,
        'session_hijack_protection'         => 1,
    ];

    // Joomla 6.0: DB no disponible en constructor -> init diferido (lazy)
    private ?DatabaseInterface $db = null;
    private string $driver = '';
    private string $prefix = '';
    private bool $inited = false;

  
    private function ensureInit(): void
    {
        if ($this->inited) {
            return;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $this->db     = $db;
        $this->driver = $this->detectDriver($db);
        $this->prefix = $db->getPrefix();

        $this->inited = true;
    }

    private function ensureWebStateInit(): void
    {
        $app = Factory::getApplication();

        if (!($app instanceof CMSWebApplicationInterface)) {
            return;
        }

        $serverSoftware = strtolower((string) ($_SERVER['SERVER_SOFTWARE'] ?? ''));

        if (
            str_contains($serverSoftware, 'apache')
            || str_contains($serverSoftware, 'litespeed')
            || str_contains($serverSoftware, 'wisepanel')
        ) {
            $server = 'apache';
        } elseif (str_contains($serverSoftware, 'nginx')) {
            $server = 'nginx';
        } elseif (str_contains($serverSoftware, 'microsoft-iis')) {
            $server = 'iis';
        } else {
            $server = 'unknown';
        }

        $app->setUserState('server', $server);

        // Chequeamos si existe el fichero filemanager (flag ya calculado en otro sitio)
        $existsFilemanager = (bool) $app->getUserState('exists_filemanager', true);

        // Si no existe, deshabilitamos el Cron para evitar pantallas en blanco en flujos que dependan de ello
        if (!$existsFilemanager) {
            $this->toggle_plugin('cron', false);
        }
    }

    /**
     * Busca las extensiones (componentes, plugins y módulos) instaladas en el equipo sin comprobar el estado del plugin ni las actualizaciones.
     * Esta función es usada por el módulo 'Securitycheck Pro Info Module'
     *
     * @return void
     */
    public function buscarQuickIcons(): void
    {
        $this->ensureWebStateInit();

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName(['element', 'manifest_cache']))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'), 'OR')
            ->where($db->quoteName('type') . ' = ' . $db->quote('module'), 'OR')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));

        $db->setQuery($query);
        $db->loadObjectList();

        // $logs_pending = $this->LogsPending();  // (si existe en tu clase; no lo toco)
    }

    /**
     * Obtiene el extension_id de un plugin Securitycheck según la opción indicada.
     *
     * @param  int $opcion  1=Firewall(System), 2=Cron(Task), 3=DB Update(System), 4=Spam Protection(System), 5=URL Inspector(System)
     * @return int|null
     */
    public function get_plugin_id(int $opcion): ?int
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $nombre = match ($opcion) {
            1 => 'System - Securitycheck Pro',
            2 => 'plg_task_securitycheckprocron',
            3 => 'System - Securitycheck Pro Update Database',
            4 => 'System - Securitycheck Spam Protection',
            5 => 'System - url Inspector',
            default => null,
        };

        if ($nombre === null) {
            return null;
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('name') . ' = ' . $db->quote($nombre));

        $db->setQuery($query);

        /** @var int|string|null $id */
        $id = $db->loadResult();

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * @param string $option
     * @param \DateTimeImmutable|null $now
     */
    public function logsByDate(string $option, ?\DateTimeImmutable $now = null): int
    {
        $app = Factory::getApplication();
        $tz  = new \DateTimeZone((string) $app->get('offset', 'UTC'));
        $now ??= new \DateTimeImmutable('now', $tz);

        $start = null;
        $end   = null;

        $startOfDay   = static fn(\DateTimeImmutable $d): \DateTimeImmutable => $d->setTime(0, 0, 0);
        $startOfMonth = static fn(\DateTimeImmutable $d): \DateTimeImmutable => $d->setDate((int) $d->format('Y'), (int) $d->format('m'), 1)->setTime(0, 0, 0);
        $startOfYear  = static fn(\DateTimeImmutable $d): \DateTimeImmutable => $d->setDate((int) $d->format('Y'), 1, 1)->setTime(0, 0, 0);

        switch ($option) {
            case 'last_year':
                $start = $startOfYear($now->modify('-1 year'));
                $end   = $startOfYear($now);
                break;
            case 'this_year':
                $start = $startOfYear($now);
                $end   = $startOfYear($now->modify('+1 year'));
                break;
            case 'last_month':
                $start = $startOfMonth($now->modify('-1 month'));
                $end   = $startOfMonth($now);
                break;
            case 'this_month':
                $start = $startOfMonth($now);
                $end   = $startOfMonth($now->modify('+1 month'));
                break;
            case 'last_7_days':
                $start = $now->modify('-7 days');
                $end   = $now;
                break;
            case 'yesterday':
                $end   = $startOfDay($now);
                $start = $end->modify('-1 day');
                break;
            case 'today':
                $start = $startOfDay($now);
                $end   = $now;
                break;
            default:
                return 0;
        }

        if ($start >= $end) {
            return 0;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__securitycheckpro_logs'))
            ->where($db->quoteName('time') . ' >= :start')
            ->where($db->quoteName('time') . ' < :end');

        $startSql = $start->format('Y-m-d H:i:s');
        $endSql   = $end->format('Y-m-d H:i:s');

        $query->bind(':start', $startSql, ParameterType::STRING);
        $query->bind(':end',   $endSql,   ParameterType::STRING);

        try {
            $db->setQuery($query);
            $result = $db->loadResult();
            return is_numeric($result) ? (int) $result : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    public function logsByType(string $option): int
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__securitycheckpro_logs'));

        switch ($option) {
            case 'total_firewall_rules': {
                $types  = ['XSS', 'SQL_INJECTION', 'LFI', 'SECOND_LEVEL'];
                $quoted = array_map(static fn(string $v): string => $db->quote($v), $types);
                $in     = implode(',', $quoted);

                $suffixCheck = 'RIGHT(' . $db->quoteName('type') . ', 7) = ' . $db->quote('_BASE64');

                $query->where(
                    '(' . $db->quoteName('type') . " IN ($in) OR " . $suffixCheck . ')'
                );
                break;
            }
            case 'total_blocked_access': {
                $types  = ['IP_BLOCKED', 'IP_BLOCKED_DINAMIC'];
                $quoted = array_map(static fn(string $v): string => $db->quote($v), $types);
                $in     = implode(',', $quoted);
                $query->where($db->quoteName('type') . " IN ($in)");
                break;
            }
            case 'total_user_session_protection': {
                $types  = ['USER_AGENT_MODIFICATION', 'REFERER_MODIFICATION', 'SESSION_PROTECTION', 'SESSION_HIJACK_ATTEMPT'];
                $quoted = array_map(static fn(string $v): string => $db->quote($v), $types);
                $in     = implode(',', $quoted);
                $query->where($db->quoteName('type') . " IN ($in)");
                break;
            }
            default:
                return 0;
        }

        try {
            $db->setQuery($query);
            $result = $db->loadResult();
            return is_numeric($result) ? (int) $result : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    public function Set_Easy_Config(): bool
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $applied = true;
        $previousParams = [];

        try {
            $query = $db->getQuery(true)
                ->select($db->quoteName('storage_value'))
                ->from($db->quoteName('#__securitycheckpro_storage'))
                ->where($db->quoteName('storage_key') . ' = ' . $db->quote('pro_plugin'));

            $db->setQuery($query);
            $raw = $db->loadResult();

            if (is_string($raw) && $raw !== '') {
                /** @var array<string,mixed>|null $decoded */
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                $previousParams = is_array($decoded) ? $decoded : [];
            }
        } catch (\Throwable) {
            $applied = false;
            $previousParams = [];
        }

        if ($previousParams === []) {
            /** @var array<string,mixed> $tmp */
            $tmp = $this->defaultConfig;
            $previousParams = $tmp;
        }

        /** @var array<string,mixed> $params */
        $params = $previousParams;

        $params['check_header_referer']                    = '0';
        $params['duplicate_backslashes_exceptions']        = '*';
        $params['line_comments_exceptions']                = '*';
        $params['using_integers_exceptions']               = '*';
        $params['escape_strings_exceptions']               = '*';
        $params['session_protection_active']               = 0;
        $params['session_hijack_protection']               = 0;
        $params['session_hijack_protection_what_to_check'] = 0;
        $params['strip_all_tags']                          = 0;

        try {
            $paramsJson = json_encode($params, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $easyJson   = json_encode(
                ['applied' => true, 'previous_config' => $previousParams],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (\JsonException) {
            return false;
        }

        try {
            $db->transactionStart();

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__securitycheckpro_storage'))
                ->where($db->quoteName('storage_key') . ' = ' . $db->quote('pro_plugin'));
            $db->setQuery($query);
            $db->execute();

            $proObject = (object) [
                'storage_key'   => 'pro_plugin',
                'storage_value' => $paramsJson,
            ];
            $db->insertObject('#__securitycheckpro_storage', $proObject);

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__securitycheckpro_storage'))
                ->where($db->quoteName('storage_key') . ' = ' . $db->quote('easy_config'));
            $db->setQuery($query);
            $db->execute();

            $easyObject = (object) [
                'storage_key'   => 'easy_config',
                'storage_value' => $easyJson,
            ];
            $db->insertObject('#__securitycheckpro_storage', $easyObject);

            $db->transactionCommit();
        } catch (\Throwable) {
            try {
                $db->transactionRollback();
            } catch (\Throwable) {
                // ignore
            }
            return false;
        }

        return $applied;
    }

    public function Get_Easy_Config(): bool
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $query = $db->getQuery(true)
                ->select($db->quoteName('storage_value'))
                ->from($db->quoteName('#__securitycheckpro_storage'))
                ->where($db->quoteName('storage_key') . ' = ' . $db->quote('easy_config'));

            $db->setQuery($query);

            /** @var string|null $raw */
            $raw = $db->loadResult();

            if ($raw === null || $raw === '') {
                return false;
            }

            /** @var array<string,mixed> $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            $applied = $decoded['applied'] ?? false;

            if (is_bool($applied)) {
                return $applied;
            }
            if (is_string($applied)) {
                $norm = strtolower(trim($applied));
                return $norm === '1' || $norm === 'true' || $norm === 'yes';
            }
            if (is_int($applied)) {
                return $applied === 1;
            }

            return false;
        } catch (\Throwable) {
            return false;
        }
    }
	
	/**
	 * Restaura en el Firewall web la configuración previa guardada por "Easy config".
	 *
	 */
	function Set_Default_Config(): bool
	{
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$applied = true;

		// 1) Cargar configuración actual del firewall (pro_plugin)
		/** @var array<string,mixed> $currentParams */
		$currentParams = [];
		try {
			$query = $db->getQuery(true)
				->select($db->quoteName('storage_value'))
				->from($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote('pro_plugin'));

			$db->setQuery($query);
			/** @var string|null $rawPro */
			$rawPro = $db->loadResult();

			if (is_string($rawPro) && $rawPro !== '') {
				/** @var array<string,mixed>|null $decodedPro */
				$decodedPro = json_decode($rawPro, true, 512, JSON_THROW_ON_ERROR);
				$currentParams = is_array($decodedPro) ? $decodedPro : [];
			}
		} catch (\Throwable $e) {
			// No abortamos aún; intentaremos continuar si tenemos previous_config válido.
			$currentParams = [];
		}

		// 2) Cargar configuración previa guardada por Easy config (easy_config.previous_config)
		/** @var array<string,mixed>|null $previousConfig */
		$previousConfig = null;
		try {
			$query = $db->getQuery(true)
				->select($db->quoteName('storage_value'))
				->from($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote('easy_config'));

			$db->setQuery($query);
			/** @var string|null $rawEasy */
			$rawEasy = $db->loadResult();

			if (!is_string($rawEasy) || $rawEasy === '') {
				return false; // No hay registro easy_config
			}

			/** @var array<string,mixed> $decodedEasy */
			$decodedEasy = json_decode($rawEasy, true, 512, JSON_THROW_ON_ERROR);

			// Esperamos: ['applied' => bool, 'previous_config' => array]
			if (!isset($decodedEasy['previous_config']) || !is_array($decodedEasy['previous_config'])) {
				return false;
			}

			/** @var array<string,mixed> $prev */
			$prev = $decodedEasy['previous_config'];
			$previousConfig = $prev;
		} catch (\Throwable $e) {
			return false; // No podemos restaurar sin previous_config válido
		}

		// 3) Si no había config actual, al menos partimos de previous_config para persistir algo coherente
		if ($currentParams === []) {
			$currentParams = $previousConfig;
		}

		// 4) Lista blanca de claves a restaurar (evita notices y mantiene seguridad)
		$keysToRestore = [
			'check_header_referer',
			'duplicate_backslashes_exceptions',
			'line_comments_exceptions',
			'using_integers_exceptions',
			'escape_strings_exceptions',
			'session_protection_active',
			'session_hijack_protection',
			'session_hijack_protection_what_to_check',
			'strip_all_tags',
		];

		// 5) Aplicar los valores previos sobre la configuración actual, sólo para las claves permitidas
		foreach ($keysToRestore as $k) {
			if (array_key_exists($k, $previousConfig)) {
				$currentParams[$k] = $previousConfig[$k];
			}
		}

		// 6) Serializar JSON de forma robusta
		try {
			$paramsJson = json_encode(
				$currentParams,
				JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

			$easyJson = json_encode(
				['applied' => false, 'previous_config' => null],
				JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);
		} catch (\JsonException $e) {
			return false;
		}

		// 7) Persistencia atómica (DELETE + INSERT)
		try {
			$db->transactionStart();

			// pro_plugin -> DELETE
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote('pro_plugin'));
			$db->setQuery($query);
			$db->execute();

			// pro_plugin -> INSERT
			$proObject                 = new \stdClass();
			$proObject->storage_key    = 'pro_plugin';
			$proObject->storage_value  = $paramsJson;
			$db->insertObject('#__securitycheckpro_storage', $proObject);

			// easy_config -> DELETE
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote('easy_config'));
			$db->setQuery($query);
			$db->execute();

			// easy_config -> INSERT (marcamos como no aplicada y sin previous_config)
			$easyObject                 = new \stdClass();
			$easyObject->storage_key    = 'easy_config';
			$easyObject->storage_value  = $easyJson;
			$db->insertObject('#__securitycheckpro_storage', $easyObject);

			$db->transactionCommit();
		} catch (\Throwable $e) {
			$applied = false;
			try {
				$db->transactionRollback();
			} catch (\Throwable $ignored) {
				// Si no se puede hacer rollback, no escalamos más
			}
		}

		return $applied;
	}

    // -------------------------
    // Plugins enable/disable
    // -------------------------

    public function toggle_plugin(string $plugin, bool $enable): void
    {
        $plugin = strtolower(trim($plugin));

        $indexMap = [
            'firewall'        => 1,
            'cron'            => 2,
            'update_database' => 3,
            'spam_protection' => 4,
            'url_inspector'   => 5,
        ];

        if (!array_key_exists($plugin, $indexMap)) {
            return;
        }

        $pluginId = (int) ($this->get_plugin_id($indexMap[$plugin]) ?? 0);
        if ($pluginId <= 0) {
            return;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = ' . (int) $enable)
                ->where($db->quoteName('extension_id') . ' = ' . (int) $pluginId);

            $db->setQuery($query);
            $db->execute();
        } catch (\Throwable) {
            return;
        }
    }

    // -------------------------
    // Version read
    // -------------------------

    public function get_version(string $extension): string
    {
        $extension = strtolower(trim($extension));
        $defaultVersion = '0.0.0';

        $map = [
            'securitycheckpro' => [
                'name' => 'Securitycheck Pro',
                'type' => 'component',
            ],
            'databaseupdate' => [
                'name' => 'System - Securitycheck Pro Update Database',
                'type' => 'plugin',
            ],
            'trackactions' => [
                'name' => 'Track Actions Package',
                'type' => 'package',
            ],
        ];

        if (!isset($map[$extension])) {
            return $defaultVersion;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('name') . ' = ' . $db->quote($map[$extension]['name']))
                ->where($db->quoteName('type') . ' = ' . $db->quote($map[$extension]['type']))
                ->order($db->quoteName('extension_id') . ' DESC');

            $db->setQuery($query, 0, 1);

            /** @var string|null $raw */
            $raw = $db->loadResult();

            if ($raw === null || $raw === '') {
                return $defaultVersion;
            }

            /** @var \stdClass|null $decoded */
            $decoded = json_decode($raw, false, 512, JSON_THROW_ON_ERROR);

            $version = $decoded?->version;

            return (is_string($version) && $version !== '') ? $version : $defaultVersion;
        } catch (\Throwable) {
            return $defaultVersion;
        }
    }

    // -------------------------
    // Lock status
    // -------------------------

    public function lockStatus(): int
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $query = $db->getQuery(true)
                ->select($db->quoteName('storage_value'))
                ->from($db->quoteName('#__securitycheckpro_storage'))
                ->where($db->quoteName('storage_key') . ' = ' . $db->quote('locked'));

            $db->setQuery($query, 0, 1);

            /** @var string|int|null $raw */
            $raw = $db->loadResult();

            if ($raw === null || $raw === '') {
                return 0;
            }

            return (int) $raw;
        } catch (\Throwable) {
            return 0;
        }
    }

    // -------------------------
    // Analytics enable/disable
    // -------------------------

    public function enable_analytics(string $website_code, string $control_center_url): int|bool
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
			/** @var \Joomla\CMS\Application\CMSApplication $app */
			$app = Factory::getApplication();
			$app->allowCache(false);
            $params = ComponentHelper::getParams('com_securitycheckpro');
            $params->set('enable_analytics', 1);
            $params->set('website_code', $website_code);

            $componentid = ComponentHelper::getComponent('com_securitycheckpro')->id;

            $table = new ExtensionTable($db);
            $table->load($componentid);
            $table->bind(['params' => $params->toString()]);

            if (!$table->check() || !$table->store()) {
                Factory::getApplication()->enqueueMessage($table->getError(), 'error');
                return false;
            }

            $this->setDispatcher(Factory::getApplication()->getDispatcher());
            parent::cleanCache('com_securitycheckpro');

            return 1;
        } catch (\Throwable) {
            return 0;
        }
    }

    public function disable_analytics(string $website_code, string $control_center_url): int|bool
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
			/** @var \Joomla\CMS\Application\CMSApplication $app */
			$app = Factory::getApplication();
			$app->allowCache(false);
            $params = ComponentHelper::getParams('com_securitycheckpro');
            $params->set('enable_analytics', 0);

            $componentid = ComponentHelper::getComponent('com_securitycheckpro')->id;

            $table = new ExtensionTable($db);
            $table->load($componentid);
            $table->bind(['params' => $params->toString()]);

            if (!$table->check() || !$table->store()) {
                Factory::getApplication()->enqueueMessage($table->getError(), 'error');
                return false;
            }

            $this->setDispatcher(Factory::getApplication()->getDispatcher());
            parent::cleanCache('com_securitycheckpro');

            return 1;
        } catch (\Throwable) {
            return 0;
        }
    }

    // -------------------------
    // Update sites extra_query
    // -------------------------

    public function update_extra_query_update_sites_table(int $siteId, string $dlid): bool
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $extraQuery = 'dlid=' . trim($dlid);

        try {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__update_sites'))
                ->set($db->quoteName('extra_query') . ' = ' . $db->quote($extraQuery))
                ->where($db->quoteName('update_site_id') . ' = ' . (int) $siteId);

            $db->setQuery($query);
            $db->execute();

            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_DOWNLOAD_ID_UPDATED'), 'message');

            return true;
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    // =========================================================
    // Triggers / Lock tables (aquí SÍ usamos $this->db/driver/prefix)
    // =========================================================

    public function lockSelectedTables(): void
    {
        $this->ensureInit();
        $this->ensureWebStateInit();
        \assert($this->db !== null);

        $params = ComponentHelper::getParams('com_securitycheckpro');
        $easy   = (bool) ($params->get('lock_tables_easy') ?? true);

        $tables = $easy
            ? ['users', 'content']
            : array_values(array_filter(array_map('trim', explode(',', (string) ($params->get('block_tables_plus') ?? '')))));

        $message = Text::_('COM_SECURITYCHECKPRO_LOCKED_MESSAGE');

        foreach ($tables as $t) {
            $t = strtolower($t);

            if ($t === 'users') {
                $this->createStandardTriplet('users', $message, ['update']);
                $this->createUsersUpdate($message);
                $this->createStandardTriplet('user_usergroup_map', $message);
                continue;
            }

            if ($t === 'content') {
                $this->createStandardTriplet('content', $message, ['update']);
                $this->createContentUpdate($message);
                $this->createStandardTriplet('redirect_links', $message);
                $this->createStandardTriplet('extensions', $message);
                continue;
            }

            $this->createStandardTriplet($t, $message);
        }

        $this->db->setQuery(
            "UPDATE #__securitycheckpro_storage SET storage_value = '1' WHERE storage_key = 'locked'"
        )->execute();
    }

    public function unlockAll(): void
    {
        $this->ensureInit();
        $this->ensureWebStateInit();

        $triggers = $this->listManagedTriggers();

        foreach ($triggers as $trg) {
            $this->dropTrigger(
                $trg['name'],
                $trg['table'] ?? null,
                $trg['function'] ?? null
            );
        }

        \assert($this->db !== null);

        $this->db->setQuery(
            "UPDATE #__securitycheckpro_storage SET storage_value = '0' WHERE storage_key = 'locked'"
        )->execute();
    }

    /**
     * @param array<int,string> $excludeOps
     */
    private function createStandardTriplet(string $table, string $message, array $excludeOps = []): void
    {
        $this->ensureInit();

        $this->assertSafeIdent($table);
        $excludeOps = array_map('strtolower', $excludeOps);

        foreach (['insert', 'update', 'delete'] as $op) {
            if (in_array($op, $excludeOps, true)) {
                continue;
            }
            $this->createLockTrigger($table, $op, $message);
        }
    }

    private function createUsersUpdate(string $message): void
    {
        $this->ensureInit();
        \assert($this->db !== null);

        $table = 'users';
        $op    = 'update';

        $this->dropIfExists($table, $op);

        $trg = $this->safeTriggerName($table, $op);

        if ($this->driver === 'mysql') {
            $sql = <<<SQL
CREATE TRIGGER {$trg}
BEFORE UPDATE ON {$this->qnTable($table)}
FOR EACH ROW
BEGIN
  DECLARE locked INT;
  SELECT storage_value INTO locked
    FROM {$this->qnTable('securitycheckpro_storage')}
   WHERE storage_key = 'locked';
  IF locked = 1 AND (
      OLD.name      <> NEW.name
   OR OLD.username  <> NEW.username
   OR OLD.email     <> NEW.email
   OR OLD.password  <> NEW.password
   OR OLD.block     <> NEW.block
   OR OLD.otpKey    <> NEW.otpKey
   OR OLD.otep      <> NEW.otep
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = {$this->db->quote($message)};
  END IF;
END
SQL;
            $this->rawExec($sql);
            return;
        }

        $fn = $this->safeFunctionName($table, $op);
        $sqlFn = <<<SQL
CREATE FUNCTION {$fn}() RETURNS trigger
LANGUAGE plpgsql AS $$
DECLARE locked INT;
BEGIN
  SELECT storage_value INTO locked
    FROM "{$this->prefix}securitycheckpro_storage"
   WHERE storage_key = 'locked';
  IF locked = 1 AND (
      OLD.name      IS DISTINCT FROM NEW.name
   OR OLD.username  IS DISTINCT FROM NEW.username
   OR OLD.email     IS DISTINCT FROM NEW.email
   OR OLD.password  IS DISTINCT FROM NEW.password
   OR OLD.block     IS DISTINCT FROM NEW.block
  ) THEN
    RAISE EXCEPTION '%', {$this->db->quote($message)};
  END IF;
  RETURN NEW;
END$$
SQL;
        $this->rawExec($sqlFn);

        $sqlTrg = <<<SQL
CREATE TRIGGER {$trg}
BEFORE UPDATE ON "{$this->prefix}{$table}"
FOR EACH ROW EXECUTE FUNCTION {$fn}()
SQL;
        $this->rawExec($sqlTrg);
    }

    private function createContentUpdate(string $message): void
    {
        $this->ensureInit();
        \assert($this->db !== null);

        $table = 'content';
        $op    = 'update';

        $this->dropIfExists($table, $op);

        $trg = $this->safeTriggerName($table, $op);

        if ($this->driver === 'mysql') {
            $sql = <<<SQL
CREATE TRIGGER {$trg}
BEFORE UPDATE ON {$this->qnTable($table)}
FOR EACH ROW
BEGIN
  DECLARE locked INT;
  SELECT storage_value INTO locked
    FROM {$this->qnTable('securitycheckpro_storage')}
   WHERE storage_key = 'locked';
  IF locked = 1 AND (
      OLD.introtext <> NEW.introtext OR OLD.fulltext <> NEW.fulltext
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = {$this->db->quote($message)};
  END IF;
END
SQL;
            $this->rawExec($sql);
            return;
        }

        $fn = $this->safeFunctionName($table, $op);
        $sqlFn = <<<SQL
CREATE FUNCTION {$fn}() RETURNS trigger
LANGUAGE plpgsql AS $$
DECLARE locked INT;
BEGIN
  SELECT storage_value INTO locked
    FROM "{$this->prefix}securitycheckpro_storage"
   WHERE storage_key = 'locked';
  IF locked = 1 AND (
      OLD.introtext IS DISTINCT FROM NEW.introtext OR
      OLD.fulltext  IS DISTINCT FROM NEW.fulltext
  ) THEN
    RAISE EXCEPTION '%', {$this->db->quote($message)};
  END IF;
  RETURN NEW;
END$$
SQL;
        $this->rawExec($sqlFn);

        $sqlTrg = <<<SQL
CREATE TRIGGER {$trg}
BEFORE UPDATE ON "{$this->prefix}{$table}"
FOR EACH ROW EXECUTE FUNCTION {$fn}()
SQL;
        $this->rawExec($sqlTrg);
    }

    private function createLockTrigger(string $table, string $operation, string $message): void
    {
        $this->ensureInit();
        \assert($this->db !== null);

        $op = strtolower($operation);
        if (!in_array($op, ['insert', 'update', 'delete'], true)) {
            throw new RuntimeException('Operación no soportada: ' . $operation);
        }

        $this->dropIfExists($table, $op);

        $trg = $this->safeTriggerName($table, $op);

        if ($this->driver === 'mysql') {
            $sql = <<<SQL
CREATE TRIGGER {$trg}
BEFORE {$op} ON {$this->qnTable($table)}
FOR EACH ROW
BEGIN
  DECLARE locked INT;
  SELECT storage_value INTO locked
    FROM {$this->qnTable('securitycheckpro_storage')}
   WHERE storage_key = 'locked';
  IF locked = 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = {$this->db->quote($message)};
  END IF;
END
SQL;
            $this->rawExec($sql);
            return;
        }

        $fn = $this->safeFunctionName($table, $op);
        $sqlFn = <<<SQL
CREATE FUNCTION {$fn}() RETURNS trigger
LANGUAGE plpgsql AS $$
DECLARE locked INT;
BEGIN
  SELECT storage_value INTO locked
    FROM "{$this->prefix}securitycheckpro_storage"
   WHERE storage_key = 'locked';
  IF locked = 1 THEN
    RAISE EXCEPTION '%', {$this->db->quote($message)};
  END IF;
  IF TG_OP = 'DELETE' THEN
    RETURN OLD;
  ELSE
    RETURN NEW;
  END IF;
END$$
SQL;
        $this->rawExec($sqlFn);

        $sqlTrg = <<<SQL
CREATE TRIGGER {$trg}
BEFORE {$op} ON "{$this->prefix}{$table}"
FOR EACH ROW EXECUTE FUNCTION {$fn}()
SQL;
        $this->rawExec($sqlTrg);
    }

    private function dropTrigger(string $triggerName, ?string $table = null, ?string $fn = null): void
    {
        $this->ensureInit();

        $this->assertSafeIdent($triggerName);
        if ($table !== null) {
            $this->assertSafeIdent($table);
        }
        if ($fn !== null) {
            $this->assertSafeIdent($fn);
        }

        if ($this->driver === 'mysql') {
            $this->rawExec('DROP TRIGGER IF EXISTS ' . $this->bt($triggerName));
            return;
        }

        if ($table) {
            $this->rawExec(
                'DROP TRIGGER IF EXISTS ' . $this->qt($triggerName) .
                ' ON ' . $this->qt($this->prefix . $table)
            );
        }
        if ($fn) {
            $this->rawExec('DROP FUNCTION IF EXISTS ' . $this->qt($fn) . '() CASCADE');
        }
    }

    /**
     * @return array<int, array{name:string, table?:string, function?:string}>
     */
    private function listManagedTriggers(): array
    {
        $this->ensureInit();
        \assert($this->db !== null);

        if ($this->driver === 'mysql') {
            $this->db->setQuery('SHOW TRIGGERS');
            $rows = (array) $this->db->loadAssocList();

            $out = [];
            foreach ($rows as $r) {
                $name = (string) ($r['Trigger'] ?? '');
                if ($name !== '' && str_ends_with($name, '_trigger')) {
                    $out[] = ['name' => $name];
                }
            }
            return $out;
        }

        $sql = <<<SQL
SELECT t.tgname AS name, c.relname AS table, p.proname AS function
FROM pg_trigger t
JOIN pg_class c ON c.oid = t.tgrelid
JOIN pg_proc  p ON p.oid = t.tgfoid
WHERE NOT t.tgisinternal
  AND (t.tgname LIKE '%\_trigger' ESCAPE '\'
    OR p.proname LIKE 'scp\_%\_fn')
SQL;
        $this->db->setQuery($sql);
        $rows = (array) $this->db->loadAssocList();

        $out = [];
        foreach ($rows as $r) {
            $name = (string) ($r['name'] ?? '');
            if ($name !== '') {
                $item = ['name' => $name];
                if (!empty($r['table'])) {
                    $item['table'] = (string) $r['table'];
                }
                if (!empty($r['function'])) {
                    $item['function'] = (string) $r['function'];
                }
                $out[] = $item;
            }
        }
        return $out;
    }

    private function dropIfExists(string $table, string $op): void
    {
        $this->ensureInit();

        $this->assertSafeIdent($table);
        $this->assertSafeIdent($op);

        $triggerNameRaw = "{$table}_{$op}_trigger";

        if ($this->driver === 'mysql') {
            $this->rawExec('DROP TRIGGER IF EXISTS ' . $this->bt($triggerNameRaw));
            return;
        }

        $fnRaw = "scp_{$table}_{$op}_fn";
        $this->rawExec(
            'DROP TRIGGER IF EXISTS ' . $this->qt($triggerNameRaw) .
            ' ON ' . $this->qt($this->prefix . $table)
        );
        $this->rawExec('DROP FUNCTION IF EXISTS ' . $this->qt($fnRaw) . '() CASCADE');
    }

    private function detectDriver(DatabaseInterface $db): string
    {
        return (string) $db->getServerType(); // 'mysql' | 'postgresql' | ...
    }

    private function qnTable(string $bare): string
    {
        $this->ensureInit();
        \assert($this->db !== null);

        if ($this->driver === 'mysql') {
            $withPrefix = $this->expandPrefix('#__' . $bare);
            return $this->db->quoteName($withPrefix);
        }

        return '"' . $this->prefix . $bare . '"';
    }

    private function bt(string $ident): string
    {
        return '`' . str_replace('`', '``', $ident) . '`';
    }

    private function qt(string $ident): string
    {
        return '"' . str_replace('"', '""', $ident) . '"';
    }

    private function safeTriggerName(string $table, string $op): string
    {
        $raw = preg_replace('/[^a-z0-9_]/i', '_', "{$table}_{$op}_trigger") ?? "{$table}_{$op}_trigger";
        return $this->driver === 'mysql' ? $this->bt($raw) : $this->qt($raw);
    }

    private function safeFunctionName(string $table, string $op): string
    {
        $raw = preg_replace('/[^a-z0-9_]/i', '_', "scp_{$table}_{$op}_fn") ?? "scp_{$table}_{$op}_fn";
        return $this->qt($raw);
    }

    private function expandPrefix(string $sql): string
    {
        $this->ensureInit();
        \assert($this->db !== null);

        /** @var mixed $replaced */
        $replaced = $this->db->replacePrefix($sql, '#__');
        return (string) $replaced;
    }

    private function rawExec(string $sql): void
    {
        $this->ensureInit();
		\assert($this->db !== null);

		$sql  = $this->expandPrefix($sql);
		/** @var mixed $conn */
		$conn = $this->db->getConnection();

		// --- PDO (pdomysql) ---
		if ($conn instanceof \PDO) {
			// Si el driver real es MySQL, evitamos el protocolo de prepared statements nativo
			// emulando prepares sólo para esta ejecución.
			$driverName = (string) $conn->getAttribute(\PDO::ATTR_DRIVER_NAME);

			if ($driverName === 'mysql') {
				$prev = $conn->getAttribute(\PDO::ATTR_EMULATE_PREPARES);

				// Algunos entornos devuelven int/bool; lo tratamos como mixed.
				$prevBool = (bool) $prev;

				$conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

				try {
					$conn->exec($sql);
				} finally {
					$conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, $prevBool);
				}

				return;
			}

			// Otros PDO: exec normal
			$conn->exec($sql);
			return;
		}

		// --- MySQLi ---
		if ($conn instanceof \mysqli) {
			if ($conn->query($sql) === false) {
				throw new RuntimeException('MySQLi error: ' . $conn->error);
			}
			return;
		}

		throw new RuntimeException('Unsupported DB connection type: ' . \get_debug_type($conn));
    }

    private function assertSafeIdent(string $ident): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $ident)) {
            throw new RuntimeException('Identificador no seguro: ' . $ident);
        }
    }
}