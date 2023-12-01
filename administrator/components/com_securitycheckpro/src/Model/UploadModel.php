<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

// Chequeamos si el archivo está incluído en Joomla!
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Modelo Securitycheck
 */
class UploadModel extends BaseDatabaseModel
{

    /* Función que sube un fichero de configuración de la extensión Securitycheck Pro (previamente exportado) y establece esa configuración sobreescribiendo la actual */
    function read_file()
    {
        $res = true;
        $secret_key = "";
    
        $jinput = Factory::getApplication()->input;
    
        // Get the uploaded file information
        $userfile = $jinput->files->get('file_to_import');
    
    
        // Make sure that file uploads are enabled in php
        if (!(bool) ini_get('file_uploads')) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_WARNINSTALLFILE'), 'warning');
            return false;
        }

        // If there is no uploaded file, we have a problem...
        if (!is_array($userfile)) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_NO_FILE_SELECTED'), 'warning');
            return false;
        }
    
        //First check if the file has the right extension, we need txt only
        if (!(strtolower(pathinfo($userfile['name'], PATHINFO_EXTENSION)) == 'txt')) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_FILE_EXTENSION'), 'warning');
            return false;
        }

        // Check if there was a problem uploading the file.
        if ($userfile['error'] || $userfile['size'] < 1 || !($userfile['type'] == "text/plain")) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_WARNINSTALLUPLOADERROR'), 'warning');
            return false;
        }

        // Build the appropriate paths
        $config        = Factory::getConfig();
        $tmp_dest    = $config->get('tmp_path') . '/' . $userfile['name'];
        $tmp_src    = $userfile['tmp_name'];

        // Move uploaded file
        $upload_res = File::upload($tmp_src, $tmp_dest);
    
        // El fichero se ha subido correctamente
        if ($upload_res) {
            // Leemos el contenido del fichero, que ha de estar en formato json
            $file_content = file_get_contents($tmp_dest);
            $file_content_json = json_decode($file_content, true);
        
            $db = Factory::getDBO();
        
            // Si hay contenido...
            if (!empty($file_content_json)) {
                // ... y lo recorremos y extraemos los pares 'storage_key' y 'storage_value'
                foreach ($file_content_json as $entry) 
                {            
                    // Configuración del firewall web
                    if (array_key_exists("storage_key", $entry)) {                                        
                           // Instanciamos un objeto para almacenar los datos que serán sobreescritos
                           $object = new \StdClass();                    
                           $object->storage_key = $entry["storage_key"];
                           $object->storage_value = $entry['storage_value'];
                    
                           // Comprobamos si hay algún dato añadido o la tabla es null; dependiendo del resultado haremos un 'update' o un 'insert'
                           $query = $db->getQuery(true)
                               ->select(array('storage_key'))
                               ->from($db->quoteName('#__securitycheckpro_storage'))
                               ->where($db->quoteName('storage_key').' = '.$db->quote($entry["storage_key"]));
                           $db->setQuery($query);
                           $exists = $db->loadResult();
                                                                    
                        try
                           {
                            // Añadimos los datos a la BBDD    
                            if (is_null($exists)) {
                                       $res = $db->insertObject('#__securitycheckpro_storage', $object);
                            } else 
                            {
                                $res = $db->updateObject('#__securitycheckpro_storage', $object, 'storage_key');
                            }
                            
                            if (!$res) {
                                Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_ERROR_IMPORTING_DATA'), 'warning');
                                return false;
                            }
                        } catch (Exception $e)
                           {    
                            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_ERROR_IMPORTING_DATA'), 'warning');
                            return false;
                        }
                        // Configuración del componente
                    } else if (array_key_exists("params", $entry)) {
                    
                                         // Obtenemos el extension_id de la extensión, necesario para actualizar la información
                                         $query = 'SELECT extension_id FROM #__extensions WHERE name="Securitycheck Pro" and type="component"';
                                         $db->setQuery($query);
                                         $db->execute();
                                         $id = $db->loadResult();
                    
                                         // Instanciamos un objeto para almacenar los datos que serán sobreescritos
                                         $object = new \StdClass();                    
                                         $object->extension_id = $id;
                                         $object->params = $entry['params'];
                                                                            
                        try 
                                         {                    
                            // Añadimos los datos a la BBDD
                            $res = $db->updateObject('#__extensions', $object, 'extension_id');        
                        
                            if (!$res) {
                                  Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_ERROR_IMPORTING_DATA'), 'warning');
                                  return false;
                            }
                        } catch (Exception $e) 
                        {    
                            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_ERROR_IMPORTING_DATA'), 'warning');
                            return false;
                        }
                    }
                }
                // Borramos el archivo subido...
				try{		
					if (file_exists($tmp_dest)) {
						File::delete($tmp_dest);
					}					
				} catch (Exception $e)
				{
				}
                
                // ... y mostramos un mensaje de éxito
                Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_IMPORT_SUCCESSFULLY'));
        
            } else 
            {
                Factory::getApplication()->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_WARNINSTALLUPLOADERROR'), 'warning');
                return false;            
            }        
        }
    
        return $res;
    }

}
