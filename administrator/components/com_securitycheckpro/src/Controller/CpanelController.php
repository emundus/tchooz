<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproBaseController;

class CpanelController extends SecuritycheckproBaseController
{
 
	/**
     * Displays the Control Panel 
     */
    public function display($cachable = false, $urlparams = Array())
    {
      
    }
	
	/* Acciones al pulsar el botón para establecer 'Easy Config' */
    function Set_Easy_Config()
    {
        $model = $this->getModel("cpanel");    
        $applied = $model->Set_Easy_Config();
                
        echo $applied;
    }
    
    /* Acciones al pulsar el botón para establecer 'Default Config' */
    function Set_Default_Config()
    {
        $model = $this->getModel("cpanel");    
        $applied = $model->Set_Default_Config();
        
        echo $applied;
    }
    
    /* Acciones al pulsar el botón 'Disable' del Firewall Web */
    function disable_firewall()
    {
        $model = $this->getModel("cpanel");
        $model->disable_plugin('firewall');
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /* Acciones al pulsar el botón 'Enable' del Firewall Web */
    function enable_firewall()
    {
        $model = $this->getModel("cpanel");
        $model->enable_plugin('firewall');
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /* Acciones al pulsar el botón 'Disable' del Cron */
    function disable_cron()
    {
        $model = $this->getModel("cpanel");
        $model->disable_plugin('cron');
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /* Acciones al pulsar el botón 'Enable' del Cron */
    function enable_cron()
    {
        $model = $this->getModel("cpanel");
        $model->enable_plugin('cron');
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /* Acciones al pulsar el botón 'Disable' de Update database */
    function disable_update_database()
    {
        $model = $this->getModel("cpanel");
        $model->disable_plugin('update_database');
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /* Acciones al pulsar el botón 'Enable' de Update database */
    function enable_update_database()
    {
        $model = $this->getModel("cpanel");
        $model->enable_plugin('update_database');
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /* Hace una consulta a la tabla especificada como parámetro */
    public function load($key_name)
    {
        $db = Factory::getDBO();
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
    

    /* Redirecciona las peticiones a System Info */
    function Go_system_info()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=sysinfo&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona las peticiones a las listas del firewall */
    function manage_lists()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewallconfig&view=firewallconfig&'. Session::getFormToken() .'=1');
    }

    /* Acciones al pulsar el boton 'Enable' del Spam Protection */
    function enable_spam_protection()
    {
        $model = $this->getModel("cpanel");
        $model->enable_plugin('spam_protection');
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /* Acciones al pulsar el botn 'Disable' de Spam Protection */
    function disable_spam_protection()
    {
        $model = $this->getModel("cpanel");
        $model->disable_plugin('spam_protection');
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }

    /* Función para ir al menú de vulnerabilidades. Usada desde el submenú */
    function go_to_vulnerabilities()
    {
        
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro&view=securitycheckpro&'. Session::getFormToken() .'=1');        
    }

    /* Función para ir al menú de permisos. Usada desde el submenú */
    function go_to_filemanager()
    {
        
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&'. Session::getFormToken() .'=1');        
    }

    /* Función para ir al menú de integridad. Usada desde el submenú */
    function go_to_fileintegrity()
    {
        
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&'. Session::getFormToken() .'=1');        
    }

    /* Función para ir al menú de htaccess. Usada desde el submenú */
    function go_to_htaccess()
    {
        
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=protection&view=protection&'. Session::getFormToken() .'=1');        
    }

    /* Función para ir al menú de malware. Usada desde el submenú */
    function go_to_malware()
    {        
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=malwarescan&'. Session::getFormToken() .'=1');        
    }
	
	 function go_to_system_info()
    {        
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=sysinfo&'. Session::getFormToken() .'=1');        
    }

    
    /* Función que bloquea las tablas importantes */
    function lock_tables()
    {
        $model = $this->getModel("cpanel");
        $model->lock_tables();
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
    
    }

    /* Función que desbloquea las tablas importantes */
    function unlock_tables()
    {
        $model = $this->getModel("cpanel");
        $model->unlock_tables();
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
    
    }
    
}