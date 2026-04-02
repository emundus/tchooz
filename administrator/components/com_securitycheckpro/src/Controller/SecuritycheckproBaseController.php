<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;

// No Permission
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\DisplayController;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;

class SecuritycheckproBaseController extends DisplayController
{
	/**
     Configuración aplicada
     *
     @var \Joomla\Registry\Registry
     */
    private ?Registry $config = null;
	
    /**
	 * Redirecciona las peticiones al Panel de Control
	 * @return  void
	*/
    function redireccion_control_panel() {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }

    /**
	 * Redirecciona las peticiones a System Info
	 * @return  void
	*/
    function redireccion_system_info() {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=sysinfo&'. Session::getFormToken() .'=1');
    }

    /**
	 * Acciones a ejecutar cuando se pulsa el botón 'Purge sessions'
	 * @return  void
	*/
    function purgeSessions() {		
		
        $model = $this->getModel('Base');

        if (!$model) {
            $this->setMessage(Text::_('COM_SECURITYCHECKPRO_ERROR_BASE_MODEL_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_securitycheckpro', false));
            return;
        }

        try {
            $model->purgeSessions();
            $this->setMessage(Text::_('COM_SECURITYCHECKPRO_SESSIONS_PURGED'));
        } catch (\Throwable $e) {
            $this->setMessage(Text::sprintf('COM_SECURITYCHECKPRO_ERROR_PURGING_SESSIONS', $e->getMessage()), 'error');
        }
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }

    /**
	 * Hace una consulta a la tabla especificada como parámetro
	 * @param   string             $key_name    The value of the storage_key table to search in
	 *
	 * @return  void
	*/
    public function load($key_name) {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query 
            ->select($db->quoteName('storage_value'))
            ->from($db->quoteName('#__securitycheckpro_storage'))
            ->where($db->quoteName('storage_key').' = '.$db->quote($key_name));
        $db->setQuery($query);
        $res = $db->loadResult();
            
        $this->config = new Registry();
                
        if (!empty($res)) {
            $res = json_decode($res, true);
            $this->config->loadArray($res);
        }
    }

	/**
	 * Exporta la configuración en JSON evitando exponer secretos.
	  * @return  void
	 */
	public function Export_config(): void
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();

		try {
			/** @var DatabaseInterface $db */
			$db = Factory::getContainer()->get(DatabaseInterface::class);

			// Claves permitidas
			$allowedKeys = ['controlcenter','cparams','easy_config','locked','pro_plugin'];

			// 1) Leer SOLO esas claves del storage (key => decodedValue)
			$query = $db->getQuery(true)
				->select([$db->quoteName('storage_key'), $db->quoteName('storage_value')])
				->from($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key') . ' IN (' . implode(',', array_map([$db,'quote'], $allowedKeys)) . ')');
			$db->setQuery($query);
			$rows = (array) $db->loadAssocList();

			$storage = [];
			foreach ($rows as $r) {
				$k = (string)$r['storage_key'];
				$v = (string)$r['storage_value'];
				$decoded = json_decode($v, true);
				// Si es JSON válido, exportamos el valor decodificado; si no, lo dejamos como string
				$storage[$k] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $v;
			}

			// 2) (Opcional) params del componente
			$query = $db->getQuery(true)
				->select($db->quoteName('params'))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('element') . ' = ' . $db->quote('com_securitycheckpro'))
				->where($db->quoteName('type') . ' = ' . $db->quote('component'))
				->setLimit(1);
			$db->setQuery($query);
			$componentParamsRaw = (string) $db->loadResult();
			$componentParams = [];
			if ($componentParamsRaw !== '') {
				$tmp = json_decode($componentParamsRaw, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
					$componentParams = $tmp;
				}
			}

			// 3) Construir payload
			$payload = [
				'meta' => [
					'component'   => 'com_securitycheckpro',
					'exported_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
					'only_keys'   => $allowedKeys,
				],
				'storage'          => $storage,           
				'component_params' => $componentParams,   
			];

			// 4) Purgar posibles secretos (por nombre de campo)
			$secretFieldRegex = '/(?i)(password|passwd|secret|token|apikey|api_key|license|private[_-]?key|client[_-]?secret)$/';
			$sanitizeRec = static function (&$value, $key) use ($secretFieldRegex) {
				if (preg_match($secretFieldRegex, (string)$key)) {
					$value = null;
				}
			};
			array_walk_recursive($payload['storage'], $sanitizeRec);
			array_walk_recursive($payload['component_params'], $sanitizeRec);

			// 5) JSON final
			$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			if ($json === false) {
				throw new \RuntimeException('JSON encoding failed: ' . json_last_error_msg());
			}

			// 6) Descargar
			while (ob_get_level() > 0) { ob_end_clean(); }

			$sitename  = preg_replace('/[^a-zA-Z0-9_-]+/', '', (string)$app->getConfig()->get('sitename','site')) ?: 'site';
			$timestamp = (new \DateTimeImmutable())->format('Ymd_His');
			$filename  = "securitycheckpro_export_{$sitename}_{$timestamp}.json";

			header('Content-Type: application/json; charset=utf-8');
			header('X-Content-Type-Options: nosniff');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
			header('Pragma: no-cache');

			echo $json;
			$app->close();

		} catch (\Throwable $e) {
			while (ob_get_level() > 0) { ob_end_clean(); }
			header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
			header('Content-Type: text/plain; charset=utf-8');
			echo 'Export error';
			Factory::getApplication()->close();
		}
	}

}
