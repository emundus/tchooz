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
use Joomla\CMS\Pagination\Pagination;
use Joomla\Utilities\ArrayHelper;
use Joomla\Filesystem\File;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Session\Session;

class OnlinechecksModel extends ListModel
{

    /**
     * @var int Total number of files of Pagination 
     */
    var $total = 0;

    /**
     * Carga estado de filtros/paginación/orden.
     *
     * @param string|null $ordering
     * @param string|null $direction
     */
    protected function populateState($ordering = null, $direction = null): void
    {
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app = Factory::getApplication();

        // Filtro de búsqueda (persiste entre peticiones)
        $search = $app->getUserStateFromRequest(
            $this->context . '.filter.onlinechecks_search',
            'filter_onlinechecks_search',
            '',
            'string'
        );
        $this->setState('filter.onlinechecks_search', trim((string) $search));

        // Límite por página (usa list_limit global como valor por defecto)
        $limit = $app->getUserStateFromRequest(
            $this->context . '.list.limit',
            'limit',
            '',
            'int'
        );
        $this->setState('list.limit', $limit);

        // Offset (start)
        $start = $app->input->getInt('start', 0);
        $this->setState('list.start', $start);

        // Orden por defecto
        $this->setState('list.ordering', $ordering ?? 'scan_date');
        $this->setState('list.direction', $direction ?? 'DESC');

        parent::populateState($ordering, $direction);
    }

    /**
     * Construye la consulta base que `ListModel` paginará automáticamente.
     */
    protected function getListQuery()
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__securitycheckpro_online_checks'));

        // Filtro de búsqueda (case-insensitive)
        $search = (string) $this->getState('filter.onlinechecks_search', '');
        if ($search !== '') {
            $needle = '%' . $db->escape($search, true) . '%';
            $or = [
                $db->quoteName('files_checked') . ' LIKE ' . $db->quote($needle, false),
                $db->quoteName('filename')  . ' LIKE ' . $db->quote($needle, false),
                $db->quoteName('scan_date')    . ' LIKE ' . $db->quote($needle, false),
				$db->quoteName('infected_files')    . ' LIKE ' . $db->quote($needle, false),
            ];
            $query->where('(' . implode(' OR ', $or) . ')');
        }

        // Orden
        $ordering  = $this->state->get('list.ordering', 'scan_date');
        $direction = strtoupper((string) $this->state->get('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $query->order($db->quoteName($ordering) . ' ' . $direction);
		
        return $query;
    }


    
   /**
	 * Borra ficheros de logs seleccionados de forma segura.
	 */
	public function delete_files(): void
	{
		// Requiere POST + token CSRF válido
		if (!Session::checkToken('post')) {
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}

		$app = Factory::getApplication();
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Carpeta base donde residen los scans (ruta canónica)
		$baseFolder = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components'
			. DIRECTORY_SEPARATOR . 'com_securitycheckpro' . DIRECTORY_SEPARATOR . 'scans' . DIRECTORY_SEPARATOR;

		$baseReal = realpath($baseFolder) ?: $baseFolder; // si no existe, usamos la declarada (pero luego verificamos por fichero)
		$baseReal = rtrim($baseReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		// Entrada desde el formulario (forzamos array de strings)
		$input = $app->getInput();
		$raw = (array) $input->get('onlinechecks_logs_table', [], 'array');

		// Normaliza: quita vacíos, duplica, castea y limita tamaño razonable
		/** @var list<string> $filenames */
		$filenames = array_values(array_unique(array_filter(
			array_map(
				static fn($v): string => trim((string) $v),
				$raw
			),
			static fn(string $v): bool => $v !== ''
		)));

		if ($filenames === []) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_FILES_SELECTED'), 'error');
			return;
		}

		$deleted = 0;

		foreach ($filenames as $name) {
			// 1) Whitelist de nombre de fichero (sin barras, ni rutas relativas)
			//    Permitimos letras, números, punto, guion y guion bajo.
			if (!preg_match('/\A[0-9A-Za-z._-]+\z/u', $name)) {
				// Nombre sospechoso: lo ignoramos y avisamos
				$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_DELETE_FILE_ERROR', $baseFolder . $name), 'error');
				continue;
			}

			// 2) Construye ruta y comprueba que permanece dentro del directorio base
			$fullPath = $baseFolder . $name;

			// Debe existir y resolverse a una ruta dentro de $baseReal (previene path traversal y symlinks)
			$resolved = @realpath($fullPath);
			if ($resolved === false || strncmp($resolved, $baseReal, strlen($baseReal)) !== 0) {
				$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_DELETE_FILE_ERROR', $fullPath), 'error');
				continue;
			}

			// 3) Elimina primero el fichero del disco
			if (!is_file($resolved)) {
				// Si no existe, intentamos limpiar igualmente el registro para no dejar huérfanos
				try {
					$query = $db->getQuery(true)
						->delete($db->quoteName('#__securitycheckpro_online_checks'))
						->where($db->quoteName('filename') . ' = ' . $db->quote($name));
					$db->setQuery($query)->execute();
				} catch (\Throwable $e) {
					// silencio: no elevamos, sólo informamos
				}

				$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_DELETE_FILE_ERROR', $resolved), 'error');
				continue;
			}

			try {
				if (!File::delete($resolved)) {
					$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_DELETE_FILE_ERROR', $resolved), 'error');
					continue;
				}
			} catch (\Throwable $e) {
				$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_DELETE_FILE_ERROR', $resolved), 'error');
				continue;
			}

			// 4) Si el fichero se borró, elimina su registro asociado en BD
			try {
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__securitycheckpro_online_checks'))
					->where($db->quoteName('filename') . ' = ' . $db->quote($name));
				$db->setQuery($query)->execute();
				$deleted++;
			} catch (\Throwable $e) {
				// El fichero ya está borrado; reportamos el error de BD pero no revertimos
				$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_DELETE_DB_ERROR', $name), 'error');
			}
		}

		if ($deleted > 0) {
			$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ELEMENTS_DELETED_FROM_LIST', $deleted));
		}
	}

	/**
	 * Extrae filas de '#__securitycheckpro_online_checks' con paginación/filtrado seguros.
	 *
	 * @param  string|null $key_name  (no usado; mantenido por compatibilidad)
	 * @return array<int, array<int, string|null>>
	 */
	public function load(?string $key_name = null): array
	{
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Carga ordenada por fecha DESC
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__securitycheckpro_online_checks'))
			->order($db->quoteName('scan_date') . ' DESC');

		$db->setQuery($query);

		/** @var array<int, array<int, string|null>> $rows */
		$rows = (array) $db->loadRowList();

		// Filtrado por búsqueda en memoria (si la tabla crece convendría moverlo a SQL)
		$searchRaw = (string) $this->state->get('filter.onlinechecks_search', '');
		$search = trim($searchRaw);

		if ($search !== '') {
			$needle = mb_strtolower($search);

			$rows = array_values(array_filter(
				$rows,
				static function (array $row) use ($needle): bool {
					// Evita notices si faltan columnas; normaliza a string
					$c1 = isset($row[1]) ? mb_strtolower((string) $row[1]) : '';
					$c2 = isset($row[2]) ? mb_strtolower((string) $row[2]) : '';
					$c3 = isset($row[3]) ? mb_strtolower((string) $row[3]) : '';

					return (mb_stripos($c1, $needle) !== false)
						|| (mb_stripos($c2, $needle) !== false)
						|| (mb_stripos($c3, $needle) !== false);
				}
			));
		}

		// Total antes de paginar
		$this->total = count($rows);

		// Paginación estándar Joomla (usa list.start / list.limit)
		$start = (int) $this->getState('list.start', 0);
		$limit = (int) $this->getState('list.limit', 0);

		if ($start < 0) {
			$start = 0;
		}
		if ($limit < 0) {
			$limit = 0;
		}

		// Si limit == 0 -> sin límite
		if ($limit > 0) {
			$rows = array_slice($rows, $start, $limit);
		} elseif ($start > 0) {
			// Si no hay límite pero sí desplazamiento, aplicamos desde start al final
			$rows = array_slice($rows, $start);
		}

		return $rows;
	}

    /**
     * Función para descargar el fichero de logs de archivos sospechosos
     *
	 *
     * @return  void
     */
    function download_log_file()
    {
		$app   = Factory::getApplication();
		$input = $app->getInput();

		// 1) Selección (array) desde el formulario
		$selection = (array) $input->get('onlinechecks_logs_table', [], 'array');
		if (empty($selection) || count($selection) !== 1) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_SELECT_ONLY_A_FILE'), 'error');
			return;
		}

		// 2) Carpeta de escaneos
		$folderPath = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components'. DIRECTORY_SEPARATOR . 'com_securitycheckpro' . DIRECTORY_SEPARATOR . 'scans' . DIRECTORY_SEPARATOR;

		// 3) Sanitiza y valida ruta (evita path traversal)
		$requested  = (string) $selection[0];
		$baseName   = basename($requested);
		$target     = $folderPath . $baseName;

		$rootReal   = realpath($folderPath) ?: $folderPath;
		$targetReal = realpath($target);

		if ($targetReal === false
			|| strncmp($targetReal, $rootReal, strlen($rootReal)) !== 0
			|| !is_file($targetReal) || !is_readable($targetReal)) {
			$app->enqueueMessage(Text::sprintf('JERROR_LOADFILE_FAILED', $baseName), 'error');
			return;
		}

		// 4) Preparar salida: *nada* puede imprimirse antes de las cabeceras
		//    - limpia buffers
		while (ob_get_level()) { @ob_end_clean(); }
		//    - desactiva compresión
		if (ini_get('zlib.output_compression')) { @ini_set('zlib.output_compression', 'Off'); }
		//    - cierra la sesión para liberar el handler
		if (session_status() === PHP_SESSION_ACTIVE) { @session_write_close(); }
		//    - elimina cabeceras que pudo añadir Joomla/plugins
		if (function_exists('header_remove')) { @header_remove(); }

		// 5) Cabeceras de descarga (sólo header() nativo)
		$size     = (int) filesize($targetReal);
		$filename = $baseName;
		$fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
		$dispo    = 'attachment; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($filename);

		// Asegura código de estado correcto antes de enviar cabeceras
		http_response_code(200);

		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('X-Content-Type-Options: nosniff');
		header('Content-Disposition: ' . $dispo);
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header('Expires: 0');
		header('Content-Length: ' . $size);

		// 6) Stream del archivo en bloques
		$fp = fopen($targetReal, 'rb');
		if ($fp === false) {
			// Si fallase de improviso, devolvemos 404 limpio
			if (function_exists('header_remove')) { @header_remove(); }
			http_response_code(404);
			header('Content-Type: text/plain; charset=UTF-8');
			echo 'File not found';
			$app->close();
			return;
		}

		// Evita limitaciones de tiempo en descargas grandes
		@set_time_limit(0);
		@ignore_user_abort(true);

		$chunk = 1048576; // 1 MB
		while (!feof($fp)) {
			$buf = fread($fp, $chunk);
			if ($buf === false) { break; }
			echo $buf;
			// Envía al cliente de inmediato
			if (function_exists('fastcgi_finish_request')) {
				// No llamar aquí; lo usamos al final. Mantenemos flush clásico:
			}
			flush();
		}
		fclose($fp);

		// En FPM podrías terminar la respuesta aquí:
		if (function_exists('fastcgi_finish_request')) {
			@fastcgi_finish_request();
		}

		// Cierra "a lo Joomla"
		$app->close();

    }
}
