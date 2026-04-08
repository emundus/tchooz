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
use Joomla\CMS\Mail\MailerFactoryInterface;

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
	 * @var array<string, array{
	 *   langConstPrefix: string,
	 *   form?: string,
	 *   method: string
	 * }>
	 */
    protected const TASKS_MAP = [
        'securitycheckpro.cron' => [
            'langConstPrefix' => 'PLG_TASK_SECURITYCHECKPROCRON_TASK',
            'form'            => 'cron',
            'method'          => 'launchCron',
        ],
    ];
	
	/**
     * Modelo Basel
     *
     * @var BaseModel|null
     */
	protected $base_model = null;
	
	/**
     * Modelo Filemanager
     *
     * @var FilemanagerModel|null
     */
	protected $filemanager_model = null;
		
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
     *
     * @since   4.2.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config)
    {
        parent::__construct($dispatcher, $config);
				
		/** @var \Joomla\CMS\Extension\MVCComponentInterface $component */
		$component = Factory::getApplication()->bootComponent('com_securitycheckpro');
		$mvcFactory = $component->getMVCFactory();		
		/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $globalmodel */
		$globalmodel = $mvcFactory->createModel('Base', 'Administrator');
		$this->base_model = $globalmodel;
		
		/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FilemanagerModel $filemanagermodel */
		$filemanagermodel = $mvcFactory->createModel('Filemanager', 'Administrator');
		$this->filemanager_model = $filemanagermodel;
    }
	
	/**
	 * Acciones para chequear los permisos de los archivos automáticamente
	 *
	 * @return void
	 */
    function acciones()
    {
		$timestamp = $this->base_model->get_Joomla_timestamp();
        $this->filemanager_model->setCampoFilemanager('last_check', $timestamp);   
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS');
        $this->filemanager_model->setCampoFilemanager('estado', 'IN_PROGRESS'); 
        $this->filemanager_model->scan("permissions");
		// Actualizamos el campo 'last_task' de la tabla 'file_manager' para reflejar la última tarea lanzada
        $this->filemanager_model->setCampoFilemanager("last_task", 'PERMISSIONS');
    }
	
	/**
	 * Acciones para chequear la integridad de los archivos automáticamente
	 *
	 * @return void
	 */
    function acciones_integrity()
    {
		$timestamp = $this->base_model->get_Joomla_timestamp();
        $this->filemanager_model->setCampoFilemanager('last_check_integrity', $timestamp);
		$message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS');
		$this->filemanager_model->setCampoFilemanager('estado_integrity', 'IN_PROGRESS'); 
		$this->filemanager_model->scan("integrity");
		$files_with_bad_integrity = $this->filemanager_model->loadStack("fileintegrity_resume", "files_with_bad_integrity");
		$this->filemanager_model->setCampoFilemanager("last_task", 'INTEGRITY');
					
		// ¿Hemos de analizar los ficheros con integridad modificada en busca de malware?
		$params = ComponentHelper::getParams('com_securitycheckpro');
		$look_for_malware = $params->get('look_for_malware', 0);
		if ($look_for_malware) {
			$this->filemanager_model->scan("malwarescan_modified");
		}
				
		// Consultamos si hay que mandar un correo cuando se encuentran archivos con integridad incorrecta
		$number_of_files = $this->consulta_resultado_scan();
		$send_email = $params->get('send_email_on_wrong_integrity', 1);
		$email_subject = $params->get('email_subject_on_wrong_integrity', "");
		
		// Está la opción de mandar correos deshabilitada?
		$config = Factory::getConfig();
		$is_online = $config->get('mailonline', 1);
						
		if ( ($is_online) && ($send_email) && ($number_of_files[0] > 0)) {
			$this-> mandar_correo($number_of_files[0], $number_of_files[1], $look_for_malware, $email_subject);
		}   
    }
		
	/**
	 * Función para mandar correos electrónicos
	 *
	 * @param   int             $with_bad_integrity    		Files with bad integrity
	 * @param   int             $with_suspicious_patterns   Files with suspicious patterns
	 * @param   bool            $look_for_malware   		Look for malware?
	 * @param   string          $subject    				The subject
	 *
	 * @return void
	 */
    protected function mandar_correo($with_bad_integrity, $with_suspicious_patterns,$look_for_malware,$subject)
    {
        
		// Variables del correo electrónico
		$email_active = $this->base_model->getValue('email_active', 0, 'pro_plugin');
        $email_to = $this->base_model->getValue('email_to', '', 'pro_plugin');
        $to = explode(',', $email_to);
        $email_from_domain = $this->base_model->getValue('email_from_domain', '', 'pro_plugin');
        $email_from_name = $this->base_model->getValue('email_from_name', '', 'pro_plugin');
        $from = array($email_from_domain,$email_from_name);
		    
        // Obtenemos el nombre del sitio, que será usado en el asunto del correo
        $config = Factory::getConfig();
        $sitename = $config->get('sitename');
    
        // Chequeamos si se han establecido los valores para mandar el correo
        if ($email_active) {        
        
            /* Cargamos el lenguaje del sitio */
            $lang = Factory::getApplication()->getLanguage();
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
            $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
			// Emisor
            $mailer->setSender($from);
            // Destinatario -- es un array de direcciones
            $mailer->addRecipient($to);
            // Asunto
            $mailer->setSubject($subject);
            // Cuerpo
            $mailer->setBody($body);
            // Opciones del correo
            $mailer->isHtml(true);
            $mailer->Encoding = 'base64';
            // Enviamos el mensaje
            try{
				$send = $mailer->Send();
			} catch (\Throwable $e)
            {
				// The email can not be sent. For example, if the option to send mails is enabled but there is not a valid provider we get a "Could not instantiate mail function" message.
            }
        }
            
    }
	
	/**
	 * Función que devuelve el número de archivos con integridad o permisos incorrectos y con patrones sospechosos
	 *
	 * @return array<mixed,mixed>
	 */
    private function consulta_resultado_scan()
    {
        
        // Inicializamos las variables
        $result = [];
          
        $files_with_bad_integrity = $this->filemanager_model->loadStack("fileintegrity_resume", "files_with_bad_integrity");
        $files_with_suspicious_patterns = $this->filemanager_model->loadStack("malwarescan_resume", "suspicious_files");
    
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
     * @throws \Exception
     */	
    protected function launchCron(ExecuteTaskEvent $event): int
    {
		try{		
			
			$params    = $event->getArgument('params');
			$tasks = $params->task_to_be_launched;						
			$timestamp = $this->base_model->get_Joomla_timestamp();
			
			switch ($tasks)
            {
				case "alternate":
					$last_task = $this->filemanager_model->GetCampoFilemanager('last_task');
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
