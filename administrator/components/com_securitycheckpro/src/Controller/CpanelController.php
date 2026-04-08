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
use Joomla\Database\DatabaseInterface;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproBaseController;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\CpanelModel;

class CpanelController extends SecuritycheckproBaseController
{
	/**
     * Acciones al pulsar el botón para establecer 'Easy Config'
     *
     *
     * @return  void
     *     
     */
    function Set_Easy_Config() {
        $model = $this->getModel('Cpanel');
		if (!$model instanceof CpanelModel) {
			Factory::getApplication()->enqueueMessage('Cpanel model not found', 'error');
			return;
		}

		/** @var CpanelModel $model */
		$applied = $model->Set_Easy_Config();
                
        echo $applied;
    }
    
   	/**
     * Acciones al pulsar el botón para establecer 'Default Config'
     *
     *
     * @return  void
     *     
     */
    function Set_Default_Config() {
		$model = $this->getModel('Cpanel');
		if (!$model instanceof CpanelModel) {
			
			Factory::getApplication()->enqueueMessage('Cpanel model not found', 'error');
			return;
		}

		/** @var CpanelModel $model */
		$applied = $model->Set_Default_Config();
        
        echo $applied;
    }
    
   /**
     * Acciones al pulsar el botón 'Disable' del Firewall Web
     *
     *
     * @return  void
     *     
     */
    function disable_firewall() {
		$model = $this->getModel('Cpanel');
		if (!$model instanceof CpanelModel) {
			
			Factory::getApplication()->enqueueMessage('Cpanel model not found', 'error');
			return;
		}        
        $model->toggle_plugin('firewall', false);
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /**
     * Acciones al pulsar el botón 'Enable' del Firewall Web
     *
     *
     * @return  void
     *     
     */
    function enable_firewall() {
        $model = $this->getModel('Cpanel');
		if (!$model instanceof CpanelModel) {
			
			Factory::getApplication()->enqueueMessage('Cpanel model not found', 'error');
			return;
		}
        $model->toggle_plugin('firewall', true);
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /**
     * Acciones al pulsar el botón 'Disable' del Cron
     *
     *
     * @return  void
     *     
     */
    function disable_cron() {
        $model = $this->getModel('Cpanel');
		if (!$model instanceof CpanelModel) {
			
			Factory::getApplication()->enqueueMessage('Cpanel model not found', 'error');
			return;
		}
        $model->toggle_plugin('cron', false);
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /**
     * Acciones al pulsar el botón 'Enable' del Cron
     *
     *
     * @return  void
     *     
     */
    function enable_cron() {
        $model = $this->getModel('Cpanel');
		if (!$model instanceof CpanelModel) {
			
			Factory::getApplication()->enqueueMessage('Cpanel model not found', 'error');
			return;
		}
        $model->toggle_plugin('cron', true);
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /**
     * Acciones al pulsar el botón 'Disable' de Update database
     *
     *
     * @return  void
     *     
     */
    function disable_update_database() {
        $model = $this->getModel('Cpanel');
		if (!$model instanceof CpanelModel) {
			
			Factory::getApplication()->enqueueMessage('Cpanel model not found', 'error');
			return;
		}
        $model->toggle_plugin('update_database', false);
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /**
     * Acciones al pulsar el botón 'Enable' de Update database
     *
     *
     * @return  void
     *     
     */
    function enable_update_database() {
        $model = $this->getModel('Cpanel');
		if (!$model instanceof CpanelModel) {
			
			Factory::getApplication()->enqueueMessage('Cpanel model not found', 'error');
			return;
		}
        $model->toggle_plugin('update_database', true);
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }    
    
	 /**
     * Redirecciona las peticiones a System Info
     *
     *
     * @return  void
     *     
     */
    function Go_system_info() {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=sysinfo&'. Session::getFormToken() .'=1');
    }

    /**
     * Redirecciona las peticiones a las listas del firewall
     *
     *
     * @return  void
     *     
     */
    function manage_lists() {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewallconfig&view=firewallconfig&'. Session::getFormToken() .'=1');
    }

    /**
     * Acciones al pulsar el boton 'Enable' del Spam Protection
     *
     *
     * @return  void
     *     
     */
    function enable_spam_protection() {
        $model = $this->getModel('Cpanel');
		if (!$model instanceof CpanelModel) {
			
			Factory::getApplication()->enqueueMessage('Cpanel model not found', 'error');
			return;
		}
        $model->toggle_plugin('spam_protection', true);
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }
    
    /**
     * Acciones al pulsar el boton 'Disable' del Spam Protection
     *
     *
     * @return  void
     *     
     */
    function disable_spam_protection() {
        $model = $this->getModel('Cpanel');
		if (!$model instanceof CpanelModel) {
			
			Factory::getApplication()->enqueueMessage('Cpanel model not found', 'error');
			return;
		}
        $model->toggle_plugin('spam_protection', false);
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
        
    }

    /**
     *  Función para ir al menú de vulnerabilidades. Usada desde el submenú
     *
     *
     * @return  void
     *     
     */
    function go_to_vulnerabilities() {
        
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro&view=securitycheckpro&'. Session::getFormToken() .'=1');        
    }

    /**
     *  Función para ir al menú de permisos. Usada desde el submenú
     *
     *
     * @return  void
     *     
     */
    function go_to_filemanager() {
        
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&'. Session::getFormToken() .'=1');        
    }

    /**
     *  Función para ir al menú de integridad. Usada desde el submenú
     *
     *
     * @return  void
     *     
     */
    function go_to_fileintegrity() {
        
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&'. Session::getFormToken() .'=1');        
    }

    /**
     *  Función para ir al menú de htaccess. Usada desde el submenú 
     *
     *
     * @return  void
     *     
     */
    function go_to_htaccess() {
        
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=protection&view=protection&'. Session::getFormToken() .'=1');        
    }

    /**
     *  Función para ir al menú de malware. Usada desde el submenú 
     *
     *
     * @return  void
     *     
     */
    function go_to_malware() {        
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=malwarescan&'. Session::getFormToken() .'=1');        
    }
	
	 /**
     *  Función para ir al menú de sustem info. Usada desde el submenú 
     *
     *
     * @return  void
     *     
     */
	function go_to_system_info() {        
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=sysinfo&'. Session::getFormToken() .'=1');        
    }
    
    /**
     *  Función que bloquea las tablas importantes
     *
     *
     * @return  void
     *     
     */
    function lockSelectedTables() {
        $model = $this->getModel('Cpanel');
		if (!$model instanceof CpanelModel) {
			
			Factory::getApplication()->enqueueMessage('Cpanel model not found', 'error');
			return;
		}
        $model->lockSelectedTables();
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
    
    }

   /**
     *  Función que desbloquea las tablas importantes
     *
     *
     * @return  void
     *     
     */
    function unlockAll() {
        $model = $this->getModel('Cpanel');
		if (!$model instanceof CpanelModel) {
			
			Factory::getApplication()->enqueueMessage('Cpanel model not found', 'error');
			return;
		}
        $model->unlockAll();
        
        $this->setRedirect('index.php?option=com_securitycheckpro');
    
    }	
    
}