<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

// Chequeamos si el archivo est� inclu�do en Joomla!
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;

class DbcheckModel extends BaseModel
{
    
    /* Funci�n que comprueba si la base de datos es mysql y existen tablas que optimizar */
    public function getIsSupported() 
    {
        return (strpos(Factory::getApplication()->getCfg('dbtype'), 'mysql') !== false && $this->getTables());
    }
    
    /* Funci�n que obtiene las tablas a optimizar */
    public function getTables() 
    {
        static $cache;
        
        // Extraemos la configuraci�n de qu� tablas mostrar
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $tables_to_check = $params->get('tables_to_check', 'All');
    
        if (is_null($cache)) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery("SHOW TABLE STATUS");
            $tables = $db->loadObjectList();
            // Si s�lo tenemos que mostrar las tablas 'MyISAM', excluimos las dem�s
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
    
    /* Funci�n para optimizar y reparar tablas */
    public function optimizeTables()
    {
        $app     = Factory::getApplication();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query    = $db->getQuery(true);
        $table     = $app->input->getVar('table');
        $engine     = $app->input->getVar('engine');
        $return = array(
        'optimize' => '',
        'repair' => ''
        );
        
        if ($engine == 'MyISAM') {        
            try 
            {
                // Optimize
                $db->setQuery("OPTIMIZE TABLE ".$db->qn($table));
                $result = $db->loadObject();
                $return['optimize'] = $result->Msg_text;
            } catch (Exception $e) 
            {
                $this->setError($e->getMessage());
                return false;
            }
            
            try
            {
                // Repair
                $db->setQuery("REPAIR TABLE ".$db->qn($table));
                $result = $db->loadObject();
                $return['repair'] = $result->Msg_text;
            } catch (Exception $e)
            {
                return false;
            }
        }
        
		$timestamp = $this->get_Joomla_timestamp();
		
        /* Actualizamos el campo que indica la �ltima optimizaci�n de la bbdd */
        $this->set_campo_filemanager('last_check_database', $timestamp);
                
        return $return;
    }
}
