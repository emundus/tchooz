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
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproBaseController;

/**
 * Securitycheckpros  Controller
 */
class SecuritycheckproController extends SecuritycheckproBaseController
{
    
    /**
     Muestra los componentes de la BBDD
     */
    function mostrar()
    {
        $jinput = Factory::getApplication()->input;
        $jinput->set('view', 'vulninfo');
            
        parent::display();
    }

    /**
     * Busca cambios entre los componentes almacenados en la BBDD y la BBDD de vulnerabilidades
     */
    function buscar()
    {
        $model = $this->getModel('securitycheckpros');
		$jinput = Factory::getApplication()->input;
        if (!$model->buscar()) {
            $msg = Text::_('COM_SECURITYCHECKPRO_CHECK_FAILED');
            Factory::getApplication()->enqueueMessage($msg, 'warning');
        } else
        {
            $eliminados = $jinput->get('comp_eliminados', 0, int);
            $core_actualizado = $jinput->get('core_actualizado', 0, int);
            $comps_actualizados = $jinput->get('componentes_actualizados', 0, int);    
            $comp_ok = Text::_('COM_SECURITYCHECKPRO_CHECK_OK ');
            $msg = Text::_($eliminados ."</li><li>" .$core_actualizado ."</li><li>" .$comps_actualizados ."</li><li>" .$comp_ok);
        }
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro', $msg);
    }

    /* Ver los logs almacenados por el plugin */
    function view_logs()
    {
        $jinput = Factory::getApplication()->input;
        $jinput->set('view', 'logs');

        parent::display(); 
    }

    /* Redirecciona las peticiones al componente */
    function redireccion()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona las peticiones al Panel de Control */
    function redireccion_control_panel()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }

    /* Filtra los logs según el término de búsqueda especificado*/
    function search()
    {
        $model = $this->getModel('logs');
        if (!$model->search()) {
            $msg = Text::_('COM_SECURITYCHECKPRO_CHECK_FAILED');
            Factory::getApplication()->enqueueMessage($msg, 'warning');        
        } else
        {
            $this->view_logs();
        }
    
    }

    /**
     * Ver los logs
     */
    function view()
    {
        $jinput->set('view', 'securitycheckpro');
        $jinput->set('layout', 'form');
        parent::display();
    }
    
    
    /**
     * Cancelar una acción
     */
    function cancel()
    {
        $msg = Text::_('Operación cancelada');
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro', $msg);
    }
 
    /**
     * Exportar logs en formato csv
     */
    function csv_export()
    {
        $db = Factory::getDBO();
        $query = 'SELECT * FROM #__securitycheckpro_logs';
        $db->setQuery($query);
        $rows = $db->loadRowList();
        $csv_export = "";
            
        // Cabecera del archivo
        $headers = array('Id','Ip',Text::_('COM_SECURITYCHECKPRO_GEOLOCATION_LABEL'),Text::_('COM_SECURITYCHECKPRO_USER'),Text::_('COM_SECURITYCHECKPRO_LOG_TIME'),Text::_('COM_SECURITYCHECKPRO_LOG_DESCRIPTION'),Text::_('COM_SECURITYCHECKPRO_DETAILED_DESCRIPTION'), Text::_('COM_SECURITYCHECKPRO_LOG_TYPE'), Text::_('COM_SECURITYCHECKPRO_LOG_URI'),Text::_('COM_SECURITYCHECKPRO_TYPE_COMPONENT'),Text::_('COM_SECURITYCHECKPRO_LOG_READ'),Text::_('COM_SECURITYCHECKPRO_ORIGINAL_STRING_CSV'));
        $csv_export .= implode(",", $headers);

        for ($i = 0 , $n = count($rows); $i < $n ; $i++)
        {
            $rows[$i][5] = Text::_('COM_SECURITYCHECKPRO_' .$rows[$i][5]);
            $rows[$i][7] = Text::_('COM_SECURITYCHECKPRO_TITLE_' .$rows[$i][7]);
            //$rows[$i][11] = base64_decode($rows[$i][11]);
            if ($rows[$i][10] == 0) {
                  $rows[$i][10] = Text::_('COM_SECURITYCHECKPRO_NO');
            } else
            {
                $rows[$i][10] = Text::_('COM_SECURITYCHECKPRO_YES');
            }
            $csv_export .= "\n" .implode(",", $rows[$i]);
        }
    
        // Mandamos el contenido al navegador
        $config = Factory::getConfig();
        $sitename = $config->get('sitename');
        // Remove whitespaces of sitename
        $sitename = str_replace(' ', '', $sitename);
        $timestamp = date('mdy_his');
        $filename = "securitycheckpro_logs_" . $sitename . "_" . $timestamp . ".csv";
        @ob_end_clean();    
        ob_start();    
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $filename);
        print $csv_export;
        exit();
    
    }

    /**
     * Marcar log(s) como leídos
     */
    function mark_read()
    {
        $model = $this->getModel('logs');
        $read = $model->mark_read();
        $this->view_logs();
    }

    /**
     * Marcar log(s) como no leídos
     */
    function mark_unread()
    {
        $model = $this->getModel('logs');
        $read = $model->mark_unread();
        $this->view_logs();
    }

    /**
     * Borrar log(s) de la base de datos
     */
    function delete()
    {
        $model = $this->getModel('logs');
        $read = $model->delete();
        $this->view_logs();
    }

    /**
     * Añadir Ip(s)  a la lista negra
     */
    function add_to_blacklist()
    {
        $model = $this->getModel('logs');
        $model->add_to_blacklist();
        $this->view_logs();
    }

    /* Redirecciona las peticiones a System Info */
    function redireccion_system_info()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=sysinfo&'. Session::getFormToken() .'=1');
    }

    /**
     * Borrar todos los log(s) de la base de datos
     */
    function delete_all()
    {
        $model = $this->getModel('logs');
        $read = $model->delete_all();
        $this->view_logs();
    }

    /**
     * Añadir Ip(s) a la lista blanca
     */
    function add_to_whitelist()
    {
        $model = $this->getModel('logs');
        $model->add_to_whitelist();
        $this->view_logs();
    }

    /* Añadir Ip(s) a la lista blanca */
    function filter_vulnerable_extension()
    {
        $jinput = Factory::getApplication()->input;
        $product = $jinput->get('product', '', 'string');		
        $model = $this->getModel('securitycheckpro');
        $vuln_extensions = $model->filter_vulnerable_extension($product);
        
        echo $vuln_extensions;
    }
	
	/**
     * Añadir componente como excepcion
     */
    function add_exception()
    {
        $model = $this->getModel('logs');
        $model->add_exception();
        $this->view_logs();
    }

}
