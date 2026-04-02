<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Input\Input;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproBaseController;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\SecuritycheckproModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\LogsModel;

class SecuritycheckproController extends SecuritycheckproBaseController
{
    
	public function __construct(
        array $config = [],
        ?MVCFactoryInterface $factory = null,
        ?CMSApplicationInterface $app = null,
        ?Input $input = null
    ) {
        parent::__construct($config, $factory, $app, $input);

        // Registramos las tareas
        $this->registerTask('mark_read', 'markLogsTask');
        $this->registerTask('mark_unread', 'markLogsTask');
    }

    /**
     * Handler único para mark_read / mark_unread.
     */
    public function markLogsTask(): void
    {
        
        $task    = $this->getTask();               // 'mark_read' | 'mark_unread'
        $setRead = ($task === 'mark_read');        // true -> leído; false -> no leído

        /** @var LogsModel $model */
        $model = $this->getModel('Logs');         
        $model->markLogs($setRead, null);          // null => toma 'cid' del input

        // Redirección estándar a la vista de logs 
        $this->view_logs();
    }
	
	
    /**
     Muestra los componentes de la BBDD
	 * @return  void
     */
    function mostrar() {
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app   = Factory::getApplication();
		/** @var Input $jinput */
		$jinput = $app->getInput();
        $jinput->set('view', 'vulninfo');
            
        parent::display();
    }

    
    /**
     * Ver los logs almacenados por el plugin
	 * @return  void
    */
    function view_logs() {
        $app   = Factory::getApplication();
		/** @var Input $jinput */
		$jinput = $app->getInput();

        $jinput->set('view', 'logs');

        parent::display(); 
    }

    /**
     * Redirecciona las peticiones al componente
	 * @return  void
     */
    function redireccion() {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro&'. Session::getFormToken() .'=1');
    }

    /**
	* Redirecciona las peticiones al Panel de Control
	* @return  void
	*/
    function redireccion_control_panel() {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }

    /**
     * Ver los logs
	 * @return  void
     */
    function view() {
		$app   = Factory::getApplication();
		/** @var Input $jinput */
		$jinput = $app->getInput();

        $jinput->set('view', 'securitycheckpro');
        $jinput->set('layout', 'form');
        parent::display();
    }
    
    
    /**
     * Cancelar una acción
	 * @return  void
     */
    function cancel() {
        $msg = Text::_('Operación cancelada');
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro', $msg);
    }
 
    /**
     * Exportar logs en formato csv
	 * @return  void
     */
    function csv_export(): void
	{
		$app = Factory::getApplication();
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		try {
			$query = 'SELECT * FROM ' . $db->replacePrefix('#__securitycheckpro_logs');
			$db->setQuery($query);
			$rows = $db->loadRowList(); // array<array<int, mixed>>
		} catch (\Throwable $e) {
			$app->enqueueMessage($e->getMessage(), 'error');
			parent::display();
			return;
		}

		if (empty($rows)) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_DATA_TO_EXPORT'), 'warning');
			parent::display();
			return;
		}

		// Cabeceras
		$headers = [
			'Id',
			'Ip',
			Text::_('COM_SECURITYCHECKPRO_GEOLOCATION_LABEL'),
			Text::_('COM_SECURITYCHECKPRO_USER'),
			Text::_('COM_SECURITYCHECKPRO_LOG_TIME'),
			Text::_('COM_SECURITYCHECKPRO_LOG_DESCRIPTION'),
			Text::_('COM_SECURITYCHECKPRO_DETAILED_DESCRIPTION'),
			Text::_('COM_SECURITYCHECKPRO_LOG_TYPE'),
			Text::_('COM_SECURITYCHECKPRO_LOG_URI'),
			Text::_('COM_SECURITYCHECKPRO_TYPE_COMPONENT'),
			Text::_('COM_SECURITYCHECKPRO_LOG_READ'),
			Text::_('COM_SECURITYCHECKPRO_ORIGINAL_STRING_CSV'),
		];

		// Prepara nombre de archivo
		$config   = $app->getConfig();
		$sitename = (string) $config->get('sitename', 'site');
		$sitename = preg_replace('/\s+/', '', $sitename); // sin espacios
		$timestamp = date('Ymd_His');
		$filename  = "securitycheckpro_logs_{$sitename}_{$timestamp}.csv";

		// Limpia todos los buffers de salida ANTES de las cabeceras HTTP
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}

		// Cabeceras HTTP
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('X-Content-Type-Options: nosniff');
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');

		// Opcional: BOM UTF-8 para que Excel detecte bien acentos
		echo "\xEF\xBB\xBF";

		// Volcamos el CSV directamente al output
		$out = fopen('php://output', 'w');
		if ($out === false) {
			// Si fallara (muy raro), volvemos al flujo de la página
			$app->enqueueMessage('Unable to open output stream for CSV export', 'error');
			parent::display();
			return;
		}

		// Escribe cabecera
		fputcsv($out, $headers);

		// Escribe filas		
		foreach ($rows as $row) {
			// Traducciones/normalizaciones
			// $row[5] -> LOG_DESCRIPTION (prefijo COM_SECURITYCHECKPRO_)
			if (isset($row[5])) {
				$row[5] = Text::_('COM_SECURITYCHECKPRO_' . $row[5]);
			}

			// $row[7] -> LOG_TYPE (prefijo COM_SECURITYCHECKPRO_TITLE_)
			if (isset($row[7])) {
				$row[7] = Text::_('COM_SECURITYCHECKPRO_TITLE_' . $row[7]);
			}

			// $row[10] -> LOG_READ (0/1 a NO/SÍ)
			if (isset($row[10])) {
				$row[10] = ((string)$row[10] === '0')
					? Text::_('COM_SECURITYCHECKPRO_NO')
					: Text::_('COM_SECURITYCHECKPRO_YES');
			}

			// Escribir la fila; fputcsv se encarga de escapado (comillas, comas, saltos de línea)
			fputcsv($out, $row);
		}

		fclose($out);
		// Terminamos aquí para que Joomla no añada HTML después del CSV
		$app->close();
	}    

    /**
     * Borrar log(s) de la base de datos
	 * @return  void
     */
    function delete() {
        $model = $this->getModel('Logs');
		if (!$model instanceof LogsModel) {
			Factory::getApplication()->enqueueMessage('Logs model not found', 'error');
			return;
		}
        $model->delete();
        $this->view_logs();
    }

    /**
     * Añadir Ip(s)  a la lista negra
	 * @return  void
     */
    function add_to_blacklist() {
        $model = $this->getModel('Logs');
		if (!$model instanceof LogsModel) {
			Factory::getApplication()->enqueueMessage('Logs model not found', 'error');
			return;
		}
        $model->add_to_blacklist();
        $this->view_logs();
    }

    /**
     * Redirecciona las peticiones a System Info
	 * @return  void
     */
    function redireccion_system_info() {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=sysinfo&'. Session::getFormToken() .'=1');
    }

    /**
     * Borrar todos los log(s) de la base de datos
	 * @return  void
     */
    function delete_all() {
        $model = $this->getModel('Logs');
		if (!$model instanceof LogsModel) {
			Factory::getApplication()->enqueueMessage('Logs model not found', 'error');
			return;
		}
        $model->delete_all();
        $this->view_logs();
    }

    /**
     * Añadir Ip(s) a la lista blanca
	 * @return  void
     */
    function add_to_whitelist() {
        $model = $this->getModel('Logs');
		if (!$model instanceof LogsModel) {
			Factory::getApplication()->enqueueMessage('Logs model not found', 'error');
			return;
		}
        $model->add_to_whitelist();
        $this->view_logs();
    }

    /**
     * Filtra las vulnerabilidades para un producto
	 * @return  void
     */
    function filter_vulnerable_extension() {
        $app   = Factory::getApplication();
		/** @var Input $jinput */
		$jinput = $app->getInput();

        $product = $jinput->get('product', '', 'string');		
        $model = $this->getModel('Securitycheckpro');
		if (!$model instanceof SecuritycheckproModel) {
			Factory::getApplication()->enqueueMessage('Securitycheckpro model not found', 'error');
			return;
		}
        $vuln_extensions = $model->filter_vulnerable_extension($product);
        
        echo $vuln_extensions;
    }
	
	/**
     * Añadir componente como excepcion
	 * @return  void
     */
    function add_exception() {
        $model = $this->getModel('Logs');
		if (!$model instanceof LogsModel) {
			Factory::getApplication()->enqueueMessage('Logs model not found', 'error');
			return;
		}
        $model->add_exception();
        $this->view_logs();
    }

}
