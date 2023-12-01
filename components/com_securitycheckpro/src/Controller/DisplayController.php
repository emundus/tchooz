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
								
		$model = new JsonModel();
		$json = $model->register_task($clientJSON);
		
		// Devolvemos la respuesta
		echo $json;		
	}
 
}