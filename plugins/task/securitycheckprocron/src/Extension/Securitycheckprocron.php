<?php

/**
 * @package     SecuritycheckPro.Plugins
 * @subpackage  Task.Cron
 *
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Task\Securitycheckprocron\Extension;

use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FilemanagerModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use LogicException;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;


// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Task plugin with routines that offer checks on files.
 * At the moment, offers a single routine to check and resize image files in a directory.
 *
 * @since  4.1.0
 */
 
final class Securitycheckprocron extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    /**
     * @var string[]
     *
     * @since 4.1.0
     */
    protected const TASKS_MAP = [
        'securitycheckpro.cron' => [
            'langConstPrefix' => 'PLG_TASK_SECURITYCHECKPROCRON_TASK',
            'form'            => 'cron',
            'method'          => 'launchCron',
        ],
    ];
	
	var $global_model = null;

    /**
     * @inheritDoc
     *
     * @return string[]
     *
     * @since 4.1.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList'    => 'advertiseRoutines',
            'onExecuteTask'        => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    /**
     * @var boolean
     * @since 4.1.0
     */
    protected $autoloadLanguage = true;

    
    /**
     * Constructor.
     *
     * @param   DispatcherInterface  $dispatcher     The dispatcher
     * @param   array                $config         An optional associative array of configuration settings
     * @param   string               $rootDirectory  The root directory to look for images
     *
     * @since   4.2.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config)
    {
        parent::__construct($dispatcher, $config);
		
		$this->global_model = new BaseModel();
		
    }
	
	/* Acciones para chequear los permisos de los archivos automáticamente*/
    function acciones()
    {
        
        // Import Filemanager model
        $model = new FilemanagerModel();
    
		$timestamp = $this->global_model->get_Joomla_timestamp();
        $model->set_campo_filemanager('last_check', $timestamp);   
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS');
        $model->set_campo_filemanager('estado', 'IN_PROGRESS'); 
        $model->scan("permissions");
		// Actualizamos el campo 'last_task' de la tabla 'file_manager' para reflejar la última tarea lanzada
        $model->set_campo_filemanager("last_task", 'PERMISSIONS');
    }
	
	/* Acciones para chequear la integridad de los archivos automáticamente*/
    function acciones_integrity()
    {
		// Import Filemanager model
        $model = new FilemanagerModel();
		
		$timestamp = $this->global_model->get_Joomla_timestamp();
        $model->set_campo_filemanager('last_check_integrity', $timestamp);
		$message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS');
		$model->set_campo_filemanager('estado_integrity', 'IN_PROGRESS'); 
		$model->scan("integrity");
		$files_with_bad_integrity = $model->loadStack("fileintegrity_resume", "files_with_bad_integrity");
		$model->set_campo_filemanager("last_task", 'INTEGRITY');
					
		// ¿Hemos de analizar los ficheros con integridad modificada en busca de malware?
		$params = ComponentHelper::getParams('com_securitycheckpro');
		$look_for_malware = $params->get('look_for_malware', 0);
		if ($look_for_malware) {
			$model->scan("malwarescan_modified");
		}
				
		// Consultamos si hay que mandar un correo cuando se encuentran archivos con integridad incorrecta
		$number_of_files = $this->consulta_resultado_scan();
		$send_email = $params->get('send_email_on_wrong_integrity', 1);
		$email_subject = $params->get('email_subject_on_wrong_integrity', "");
		if ($send_email && ($number_of_files[0] > 0)) {
			$this-> mandar_correo($number_of_files[0], $number_of_files[1], $look_for_malware, $email_subject);
		}   
    }
		
	/*  Función para mandar correos electrónicos */
    protected function mandar_correo($with_bad_integrity, $with_suspicious_patterns,$look_for_malware,$subject)
    {
        
		$this->pro_plugin = new BaseModel();
        
		// Variables del correo electrónico
        $email_to = $this->pro_plugin->getValue('email_to', '', 'pro_plugin');
        $to = explode(',', $email_to);
        $email_from_domain = $this->pro_plugin->getValue('email_from_domain', '', 'pro_plugin');
        $email_from_name = $this->pro_plugin->getValue('email_from_name', '', 'pro_plugin');
        $from = array($email_from_domain,$email_from_name);
		    
        // Obtenemos el nombre del sitio, que será usado en el asunto del correo
        $config = Factory::getConfig();
        $sitename = $config->get('sitename');
    
        // Chequeamos si se han establecido los valores para mandar el correo
        if (!empty($email_to)) {        
        
            /* Cargamos el lenguaje del sitio */
            $lang = Factory::getLanguage();
            $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
                                
            // Creamos el asunto y el cuerpo del mensaje
            if (empty($subject)) {
                $subject = Text::sprintf($lang->_('COM_SECURITYCHECKPRO_EMAIL_SITENAME'), $sitename);
            }        
            if ($look_for_malware) {
                $body = Text::sprintf($lang->_('COM_SECURITYCHECKPRO_EMAIL_ALERT_BODY'), $with_bad_integrity, $with_suspicious_patterns);
            } else
            {
                $body = Text::sprintf($lang->_('COM_SECURITYCHECKPRO_EMAIL_ALERT_BODY_NO_MALWARE_SCAN'), $with_bad_integrity);            
            }
        
            $body .= '</br>' . '</br>' . Text::_($lang->_('COM_SECURITYCHECKPRO_EMAIL_ALERT_BODY_ALERT'));
                    
            // Invocamos la clase Mail
            $mailer = Factory::getMailer();
            // Emisor
            $mailer->setSender($from);
            // Destinatario -- es un array de direcciones
            $mailer->addRecipient($to);
            // Asunto
            $mailer->setSubject($subject);
            // Cuerpo
            $mailer->setBody($body);
            // Opciones del correo
            $mailer->isHTML(true);
            $mailer->Encoding = 'base64';
            // Enviamos el mensaje
            try{
				$send = $mailer->Send();
			} catch (Exception $e)
            {
               
            }
        }
            
    }
	
	/* Función que devuelve el número de archivos con integridad o permisos incorrectos y con patrones sospechosos */
    private function consulta_resultado_scan()
    {
        
        // Inicializamos las variables
        $result = array();
    
        // Cargamos los parámetros del componente
        // Import Securitycheckpros model
        $model = new FilemanagerModel();
      
        $files_with_bad_integrity = $model->loadStack("fileintegrity_resume", "files_with_bad_integrity");
        $files_with_suspicious_patterns = $model->loadStack("malwarescan_resume", "suspicious_files");
    
        // Añadimos los resultados a la variable que será devuelta
        array_push($result, $files_with_bad_integrity);
        array_push($result, $files_with_suspicious_patterns);
        
        return $result;        
    
    }
		
	
    /**
     * @param   ExecuteTaskEvent  $event  The onExecuteTask event
     *
     * @return integer  The exit code
     *
     * @since 4.1.0
     * @throws RuntimeException
     * @throws LogicException
     */
	
    protected function launchCron(ExecuteTaskEvent $event): int
    {
		try{		
			
			$params    = $event->getArgument('params');
			$tasks = $params->task_to_be_launched;
			
			$model = new FilemanagerModel();
			
			$timestamp = $this->global_model->get_Joomla_timestamp();
			
			switch ($tasks)
            {
				case "alternate":
					$last_task = $model->get_campo_filemanager('last_task');
                    if ($last_task == "INTEGRITY") {
						$this->acciones();
					} else if ($last_task == "PERMISSIONS") {
						$this->acciones_integrity();						
					}
					break;
				case "permissions":						
					$this->acciones();
					break;				
				case "integrity":
					$this->acciones_integrity();
					break;
				case "both":
					$this->acciones();
					$this->acciones_integrity();
					break;
			}				
		
		} catch (\Throwable $e)
        {
			$error = $e->getMessage();
			
            $this->logTask($error, 'error');	

            return TaskStatus::KNOCKOUT;  
        }

        return TaskStatus::OK;
    }
}
