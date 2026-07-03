<?php

/**
 * @Securitycheckpro package
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

class Pkg_SecuritycheckproInstallerScript
{

	/**
     * Se ejecuta antes de install/update/uninstall del paquete
     *
     * @param  string  $type   install|update|uninstall|discover_install
     * @param  object  $parent InstallerAdapter
     */
    public function preflight(string $type, $parent): bool
    {
        if ($type === 'uninstall') {
            if (!\defined('SCP_PKG_UNINSTALLING')) {
                \define('SCP_PKG_UNINSTALLING', true);
            }
        }

		// Only allow to install on PHP 8.1.0 or later
        if (!version_compare(PHP_VERSION, '8.1.0', 'ge')) {
            Factory::getApplication()->enqueueMessage('Securitycheck Pro requires, at least, PHP 8.1.0', 'error');
            return false;
		// @phpstan-ignore-next-line
        }  else if (version_compare(JVERSION, '5.0.0', 'lt')) {
            Factory::getApplication()->enqueueMessage("This version only works in Joomla! 5 or higher", 'error');
            return false;
        }

        return true;
    }

    /**
	 * Runs on uninstall
	 * @param  object  $parent InstallerAdapter
	 */
	public function uninstall($parent): void
	{
        Factory::getApplication()->enqueueMessage('The Securitycheck Pro Package has been uninstalled correctly.');
    }

	 /**
     * Runs after install, update or discover_update
     *
     * @param string     $type   install, update or discover_update
     * @param Installer $parent
	 *
	 * @return  boolean  True on success
     *
     */
	public function postflight($type, $parent)
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

        // Enable and configure module
        $query = "UPDATE #__modules SET position='icon', ordering = '1', published = '1' WHERE module='mod_scpadmin_quickicons'";
        $db->setQuery($query);
        $db->execute();

        // Update the URL inspector plugin ordering; it must be published the last
        $query = "UPDATE #__extensions SET ordering = '-100' WHERE name='System - url inspector'";
        $db->setQuery($query);
        $db->execute();

        // Check if url plugin is enabled
        $query = "SELECT enabled from #__extensions WHERE name='System - url inspector'";
        $db->setQuery($query);
        $url_plugin_enabled = $db->loadResult();

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $tableExtensions = $db->quoteName("#__extensions");
        $columnElement   = $db->quoteName("element");
        $columnType      = $db->quoteName("type");
        $columnEnabled   = $db->quoteName("enabled");

        // Enable Securitycheck Pro plugin
        $db->setQuery(
            "UPDATE
				$tableExtensions
			SET
				$columnEnabled=1
			WHERE
				$columnElement='securitycheckpro'
			AND
				$columnType='plugin'"
        );

        $db->execute();

        // Enable Securitycheck Pro Cron Task plugin
        $db->setQuery(
            "UPDATE
			$tableExtensions
				SET
				$columnEnabled=1
			WHERE
				$columnElement='securitycheckprocron'
			AND
				$columnType='plugin'"
        );

        $db->execute();

		// Enable Securitycheck Pro Task Checker
        $db->setQuery(
            "UPDATE
				$tableExtensions
			SET
				$columnEnabled=1
			WHERE
				$columnElement='securitycheckpro_task_checker'
			AND
				$columnType='plugin'"
        );

        $db->execute();

		if ($type === 'update' || $type === 'discover_update') {
			$this->migrateLegacyDownloadIdToPackageUpdateSite('pkg_securitycheckpro');
			$this->migrateExtensionsBlacklist510();
		}

		return true;
	}

	/**
	 * 5.1.0: añade phtml, phar, shtml, htaccess a extensions_blacklist si no están ya presentes
	 */
	private function migrateExtensionsBlacklist510(): void
	{
		try {
			$db = Factory::getContainer()->get(DatabaseInterface::class);

			$query = $db->getQuery(true)
				->select($db->quoteName('storage_value'))
				->from($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote('pro_plugin'));
			$db->setQuery($query);
			$raw = $db->loadResult();

			if (empty($raw)) {
				return;
			}

			$config = json_decode($raw, true);
			if (!is_array($config)) {
				return;
			}

			$current  = isset($config['extensions_blacklist']) ? (string) $config['extensions_blacklist'] : 'php,js,exe,xml';
			$existing = array_map('trim', explode(',', $current));
			$existingLower = array_map('strtolower', $existing);

			$added = false;
			foreach (['phtml', 'phar', 'shtml', 'htaccess'] as $ext) {
				if (!in_array($ext, $existingLower, true)) {
					$existing[] = $ext;
					$added = true;
				}
			}

			if (!$added) {
				return;
			}

			$config['extensions_blacklist'] = implode(',', $existing);
			$newRaw = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

			$update = $db->getQuery(true)
				->update($db->quoteName('#__securitycheckpro_storage'))
				->set($db->quoteName('storage_value') . ' = ' . $db->quote($newRaw))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote('pro_plugin'));
			$db->setQuery($update);
			$db->execute();

		} catch (\Throwable $e) {
			// No interrumpir la actualización por este motivo
		}
	}

	// =========================================================
	// Download ID migration: component/plugin -> package
	// =========================================================

	private function migrateLegacyDownloadIdToPackageUpdateSite(string $packageElement): void
	{
		try {
			$currentDlid = $this->getPackageDownloadIdFromUpdateSite($packageElement);

			if ($currentDlid !== '') {
				return;
			}

			$legacyDownloadId = $this->getLegacyDownloadId();

			if ($legacyDownloadId === '') {
				return;
			}

			$updateSiteId = $this->getPackageUpdateSiteId($packageElement);

			if ($updateSiteId === null) {
				return;
			}

			$this->updatePackageExtraQuery($updateSiteId, $legacyDownloadId);
			$this->refreshCachedUpdatesExtraQuery($updateSiteId, $legacyDownloadId);
		} catch (\Throwable $e) {
			// No debe romper la instalacion/actualizacion
		}
	}

	private function getPackageUpdateSiteId(string $packageElement): ?int
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$typeVal = 'package';
		$query = $db->getQuery(true)
			->select($db->quoteName('us.update_site_id'))
			->from($db->quoteName('#__update_sites', 'us'))
			->innerJoin(
				$db->quoteName('#__update_sites_extensions', 'usext')
				. ' ON ' . $db->quoteName('usext.update_site_id')
				. ' = ' . $db->quoteName('us.update_site_id')
			)
			->innerJoin(
				$db->quoteName('#__extensions', 'e')
				. ' ON ' . $db->quoteName('e.extension_id')
				. ' = ' . $db->quoteName('usext.extension_id')
			)
			->where($db->quoteName('e.type') . ' = :type')
			->where($db->quoteName('e.element') . ' = :element')
			->bind(':type', $typeVal)
			->bind(':element', $packageElement);

		$db->setQuery($query);
		$result = $db->loadResult();

		if ($result === null || $result === false || $result === '') {
			return null;
		}

		return (int) $result;
	}

	private function getPackageDownloadIdFromUpdateSite(string $packageElement): string
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$typeVal = 'package';
		$query = $db->getQuery(true)
			->select($db->quoteName('us.extra_query'))
			->from($db->quoteName('#__update_sites', 'us'))
			->innerJoin(
				$db->quoteName('#__update_sites_extensions', 'usext')
				. ' ON ' . $db->quoteName('usext.update_site_id')
				. ' = ' . $db->quoteName('us.update_site_id')
			)
			->innerJoin(
				$db->quoteName('#__extensions', 'e')
				. ' ON ' . $db->quoteName('e.extension_id')
				. ' = ' . $db->quoteName('usext.extension_id')
			)
			->where($db->quoteName('e.type') . ' = :type')
			->where($db->quoteName('e.element') . ' = :element')
			->bind(':type', $typeVal)
			->bind(':element', $packageElement);

		$db->setQuery($query);
		$extraQuery = (string) $db->loadResult();

		if ($extraQuery === '') {
			return '';
		}

		return trim($extraQuery);
	}

	private function getLegacyDownloadId(): string
	{
		$downloadId = $this->getComponentDownloadId('com_securitycheckpro');

		if ($downloadId !== '') {
			return $downloadId;
		}

		return $this->getPluginDownloadId('system', 'securitycheckpro_update_database');
	}

	private function getComponentDownloadId(string $component): string
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$typeVal = 'component';
		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = :type')
			->where($db->quoteName('element') . ' = :element')
			->bind(':type', $typeVal)
			->bind(':element', $component);

		$db->setQuery($query);
		$paramsJson = $db->loadResult();

		if (!is_string($paramsJson) || $paramsJson === '') {
			return '';
		}

		$registry = new Registry($paramsJson);
		$downloadId = $registry->get('downloadid', '');

		return is_string($downloadId) ? trim($downloadId) : '';
	}

	private function getPluginDownloadId(string $folder, string $element): string
	{
		$plugin = PluginHelper::getPlugin($folder, $element);

		if (empty($plugin) || !isset($plugin->params) || !is_string($plugin->params)) {
			return '';
		}

		$registry = new Registry($plugin->params);
		$downloadId = $registry->get('downloadid', '');

		return is_string($downloadId) ? trim($downloadId) : '';
	}

	private function updatePackageExtraQuery(int $updateSiteId, string $downloadId): void
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$newExtraQuery = $downloadId;

		$query = $db->getQuery(true)
			->update($db->quoteName('#__update_sites'))
			->set($db->quoteName('extra_query') . ' = :extra_query')
			->where($db->quoteName('update_site_id') . ' = :update_site_id')
			->bind(':extra_query', $newExtraQuery)
			->bind(':update_site_id', $updateSiteId);

		$db->setQuery($query);
		$db->execute();
	}

	private function refreshCachedUpdatesExtraQuery(int $updateSiteId, string $downloadId): void
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$query = $db->getQuery(true)
			->update($db->quoteName('#__updates'))
			->set($db->quoteName('extra_query') . ' = :extra_query')
			->where($db->quoteName('update_site_id') . ' = :update_site_id')
			->bind(':extra_query', $downloadId)
			->bind(':update_site_id', $updateSiteId);

		$db->setQuery($query);
		$db->execute();
	}


}
