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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Input\Input;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;

class DbcheckModel extends BaseModel
{    
	protected function getComponentParams(): Registry
	{
		// Punto único para leer params del componente
		return ComponentHelper::getParams('com_securitycheckpro');
	}

    /**
     * Función que comprueba si la base de datos es mysql y existen tablas que optimizar
     *
     * @return  bool
     *     
     */
    public function getIsSupported() 
    {
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app     = Factory::getApplication();

        return (strpos($app->getCfg('dbtype'), 'mysql') !== false && $this->getTables());
    }
    
    /**
     * Función que obtiene las tablas a optimizar
     *
     * @return  bool
     *     
     */
    public function getTables() 
    {
        static $cache;
        
        // Extraemos la configuración de qué tablas mostrar
        $params = $this->getComponentParams();
        $tables_to_check = $params->get('tables_to_check', 'All');
    
        if (is_null($cache)) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery("SHOW TABLE STATUS");
            $tables = $db->loadObjectList();
            // Si sólo tenemos que mostrar las tablas 'MyISAM', excluimos las demás
            if ($tables_to_check == 'Myisam') {
                foreach ($tables as $i => $table)
                {
                    if (isset($table->Engine) && $table->Engine != 'MyISAM') {
                        unset($tables[$i]);
                    }
                }
            }
            
            $cache = array_values($tables);
        }
        
        return $cache;
    }
    
    /**
	 * @return array{optimize:string,repair:string,engine:string,message:string}
	 */
	public function optimizeTable(string $table): array
	{
		$app = Factory::getApplication();
		$db  = $this->getDatabase();

		// Validación estricta
		if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
			throw new \InvalidArgumentException('Invalid table');
		}

		$prefix = (string) $app->get('dbprefix');
		if ($prefix !== '' && strpos($table, $prefix) !== 0) {
			throw new \InvalidArgumentException('Out-of-scope table');
		}

		$db->setQuery('SHOW TABLE STATUS LIKE ' . $db->quote($table));
		$info = $db->loadObject();
		if (!$info) {
			throw new \RuntimeException('Table not found');
		}

		$engine = (string) ($info->Engine ?? '');

		$return = [
			'optimize' => '',
			'repair'   => '',
			'engine'   => $engine,
			'message'  => '',
		];

		// InnoDB (u otros): no hacer nada, pero NO es error
		if (strcasecmp($engine, 'MyISAM') !== 0) {
			$return['message'] = Text::sprintf(
				'COM_SECURITYCHECKPRO_DB_OPTIMIZE_NOT_NEEDED: %s (%s)',
				$table,
				$engine !== '' ? $engine : 'unknown'
			);

			$timestamp = $this->get_Joomla_timestamp();
			$this->setCampoFilemanager('last_check_database', $timestamp);

			return $return;
		}

		// MyISAM: optimiza y repara
		try {
			$db->setQuery('OPTIMIZE TABLE ' . $db->quoteName($table))->execute();
			$return['optimize'] = 'OK';
		} catch (\Throwable $e) {
			$this->setError('Optimize failed: ' . $e->getMessage());
			throw $e;
		}

		try {
			$db->setQuery('REPAIR TABLE ' . $db->quoteName($table))->execute();
			$return['repair'] = 'OK';
		} catch (\Throwable $e) {
			$this->setError('Repair failed: ' . $e->getMessage());
			throw $e;
		}

		$return['message'] = Text::sprintf('COM_SECURITYCHECKPRO_DB_OPTIMIZE_RESULT', 'OK', 'OK');

		$timestamp = $this->get_Joomla_timestamp();
		$this->setCampoFilemanager('last_check_database', $timestamp);

		return $return;
	}

}
