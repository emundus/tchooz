<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Component\ComponentHelper;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Site\Model\JsonModel;

class DisplayController extends BaseController
{
 	// Definimos las variables
	protected $input = array();

	public function __construct($config = array())
	{
		$this->input = Factory::getApplication()->input;
		parent::__construct($config);
	}
	public function execute($task)
	{
		$task = 'json';
				
		parent::execute($task);
	}

	/**
	 * Manejamos las llamadas al API
	 * @return  void
	 */
	public function json()
	{
		// Get request's headers and change the keys to lowercase
		$headers = getallheaders();
		$headers = array_change_key_case($headers);				
						
		if ( (!empty($headers)) && (is_array($headers)) && (array_key_exists('user-agent',$headers)) && ($headers['user-agent'] == 'Securitycheck Pro Control Center User agent') ) {
			
			$model = new JsonModel();
			$base_model = new BaseModel();
			
			$token = '';
			
			$cc_config = $base_model->getControlCenterConfig();
			if ( (is_array($cc_config)) && (array_key_exists('token',$cc_config)) ) {
				$token = $cc_config['token'];
			}			
			
			//To be added in future versions
			//if ( (!empty($token)) && (array_key_exists('Token',$headers)) && ($headers['Token'] == $token) ) {
						
				// Request comes from the Control Center			
				$referrer = null;
				
				// String json de la petición
				$clientJSON = $this->input->get('json', null, 'raw', 2);
										
				// Decodificamos el string para añadir el referrer, que será usado en caso de fallo (por ejemplo cuando las claves secretas no coinciden)
				$request = json_decode($clientJSON, true);
				
				/* Necesitamos añadir la función urldecode sobre el json recibido porque no funcionaría en casos como este: {%22cipher%22:2,%22body%22:....}} */
				if (empty($request)) {
					$request = json_decode(urldecode($clientJSON), true);
				}
						
				if (array_key_exists('HTTP_REFERER', $_SERVER)) {
					$referrer = $_SERVER['HTTP_REFERER'];
				}		
				
				if ( (!is_null($request)) && (is_array($request)) && (!is_null($referrer)) )
				{
					$request['referrer'] = $referrer;
				}
				
				// Volvemos a codificar el string en formato json
				$clientJSON = json_encode($request);
							
				$json = $model->register_task($clientJSON);
				
				// Devolvemos la respuesta
				echo $json;	
			//To be added in future versions
			/*} else {		
				$model->log_filename = "error.php";
				$message = "Token is empty or doesn't match with Control Center";
				$model->write_log($message,"ERROR");
			}*/
		}
	}
 
}