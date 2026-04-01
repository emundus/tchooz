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
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomla\CMS\Application\CMSApplication;

class UploadModel extends BaseDatabaseModel
{    
	/**
     * Función que sube un fichero de configuración de la extensión Securitycheck Pro (previamente exportado) y establece esa configuración sobreescribiendo la actual
     *
     * @return  bool
     *     
     */
    public function read_file(): bool
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();
		
		// CSRF en acciones POST de admin
		if (!\Joomla\CMS\Session\Session::checkToken('post')) {
		    $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
		    return false;
		}

		if (!(bool)ini_get('file_uploads')) {
			$app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_WARNINSTALLFILE'), 'warning');
			return false;
		}

		$input = $app->input;
		$userfile = $input->files->get('file_to_import');

		if (!is_array($userfile) || !isset($userfile['name'],$userfile['tmp_name'],$userfile['error'],$userfile['size'])) {
			$app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_NO_FILE_SELECTED'), 'warning');
			return false;
		}
		if ((int)$userfile['error'] !== UPLOAD_ERR_OK || (int)$userfile['size'] < 1) {
			$app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_WARNINSTALLUPLOADERROR'), 'warning');
			return false;
		}

		$safeName = File::makeSafe((string)$userfile['name']);
		if (strtolower(pathinfo($safeName, PATHINFO_EXTENSION)) !== 'json') {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_FILE_EXTENSION'), 'warning');
			return false;
		}
		if ((int)$userfile['size'] > 5 * 1024 * 1024) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_FILE_TOO_LARGE'), 'warning');
			return false;
		}

		$tmpDir = rtrim((string)$app->getConfig()->get('tmp_path'), DIRECTORY_SEPARATOR);
		$dest   = $tmpDir . DIRECTORY_SEPARATOR . (uniqid('scpro_import_', true) . '_' . $safeName);

		try {
			if (!File::upload($userfile['tmp_name'], $dest)) {
				$app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_WARNINSTALLUPLOADERROR'), 'warning');
				return false;
			}
		} catch (\Throwable) {
			$app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_WARNINSTALLUPLOADERROR'), 'warning');
			return false;
		}

		$raw = @file_get_contents($dest);
		if ($raw === false) {
			File::delete($dest);
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_ERROR_IMPORTING_DATA'), 'warning');
			return false;
		}

		$json = json_decode($raw, true, 512, JSON_BIGINT_AS_STRING);
		if (!is_array($json) || json_last_error() !== JSON_ERROR_NONE) {
			File::delete($dest);
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_JSON'), 'warning');
			return false;
		}

		// Validación básica
		$storage         = $json['storage']          ?? null;
		$componentParams = $json['component_params'] ?? null;
		$meta            = $json['meta']             ?? [];

		if (!is_array($storage)) {
			File::delete($dest);
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_JSON_SCHEMA'), 'warning');
			return false;
		}
		if (isset($meta['component']) && $meta['component'] !== 'com_securitycheckpro') {
			File::delete($dest);
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_WRONG_COMPONENT_IN_IMPORT'), 'warning');
			return false;
		}

		// Solo estas claves
		$allowedKeys = ['controlcenter','cparams','easy_config','locked','pro_plugin'];

		// Omite/anonimiza campos sensibles por si vinieran con valor
		$secretFieldRegex = '/(?i)(password|passwd|secret|token|apikey|api_key|license|private[_-]?key|client[_-]?secret)$/';
		$stripSecrets = static function (&$value, $key) use ($secretFieldRegex) {
			if (preg_match($secretFieldRegex, (string)$key)) {
				if ($value !== null && $value !== '') { $value = null; }
			}
		};
		array_walk_recursive($storage, $stripSecrets);
		if (is_array($componentParams)) {
			array_walk_recursive($componentParams, $stripSecrets);
		}

		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		try {
			$db->transactionStart();

			// 1) Upsert SOLO las claves permitidas
			foreach ($storage as $key => $value) {
				if (!in_array($key, $allowedKeys, true)) {
					continue; // ignorar otras
				}

				// DB guarda JSON; aseguramos string JSON:
				if (is_string($value)) {
					// ¿ya viene como JSON válido?
					$test = json_decode($value, true);
					if (json_last_error() === JSON_ERROR_NONE) {
						$jsonValue = $value; // ya es JSON
					} else {
						$jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
					}
				} else {
					$jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				}

				if ($jsonValue === false) {
					throw new \RuntimeException('Invalid JSON for key ' . $key);
				}

				// Existe?
				$query = $db->getQuery(true)
					->select($db->quoteName('storage_key'))
					->from($db->quoteName('#__securitycheckpro_storage'))
					->where($db->quoteName('storage_key') . ' = ' . $db->quote($key));
				$db->setQuery($query);
				$exists = $db->loadResult();

				$row = (object)[
					'storage_key'   => $key,
					'storage_value' => $jsonValue,
				];

				if ($exists === null) {
					$db->insertObject('#__securitycheckpro_storage', $row);
				} else {
					$db->updateObject('#__securitycheckpro_storage', $row, 'storage_key');
				}
			}

			// 2) (Opcional) fusionar params del componente si existen en el archivo
			if (is_array($componentParams)) {
				$query = $db->getQuery(true)
					->select([$db->quoteName('extension_id'), $db->quoteName('params')])
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('element') . ' = ' . $db->quote('com_securitycheckpro'))
					->where($db->quoteName('type') . ' = ' . $db->quote('component'))
					->setLimit(1);
				$db->setQuery($query);
				$current = $db->loadAssoc();
				if ($current) {
					$regCurrent = new Registry($current['params'] ?? '{}');
					$regImport  = new Registry($componentParams);

					// merge seguro (sin sobreescribir secretos)
					foreach ($regImport->toArray() as $k => $v) {
						if (preg_match($secretFieldRegex, (string)$k) && $v !== null && $v !== '') {
							continue;
						}
						$regCurrent->set($k, $v);
					}

					$row = (object)[
						'extension_id' => (int)$current['extension_id'],
						'params'       => (string)$regCurrent->toString('JSON'),
					];
					$db->updateObject('#__extensions', $row, 'extension_id');
				}
			}

			$db->transactionCommit();

		} catch (\Throwable $e) {
			$app->enqueueMessage($e->getMessage(), 'error');			
			try { $db->transactionRollback(); } catch (\Throwable) {}
			File::delete($dest);
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_ERROR_IMPORTING_DATA'), 'warning');
			return false;
		}

		try { File::delete($dest); } catch (\Throwable) {}

		$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_IMPORT_SUCCESSFULLY'), 'message');
		return true;
	}

}
