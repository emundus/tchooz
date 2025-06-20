﻿<?php
/**
 * Export  Model for eMundus Component
 *
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

class EmundusModelExport extends JModelList
{

	var $_db = null;
	var $_user = null;

	/**
	 * Constructor
	 *
	 * @since 1.5
	 */
	function __construct()
	{
		parent::__construct();
		$this->_db   = JFactory::getDBO();
		$this->_user = JFactory::getSession()->get('emundusUser');

		JLog::addLogger(['text_file' => 'com_emundus.export.php'], JLog::ERROR);
	}

	/*
	* 	export file to PDF
	*	@param file_src 		path to file source
	*	@param file_dest 		path to file dest
	*	@param file_src_format 	default office, html for other
	*	@param fnum 		    Application file number
	* 	@return Object
	*/
	function toPdf($file_src, $file_dest, $file_src_format = null, $fnum = null)
	{
		$eMConfig             = ComponentHelper::getParams('com_emundus');
		$gotenberg_activation = $eMConfig->get('gotenberg_activation', 0);
		$gotenberg_url        = $eMConfig->get('gotenberg_url', 'http://localhost:3000');

		$client = null;
		$config = Factory::getApplication()->getConfig();
		$proxy_enable = $config->get('proxy_enable', 0);

		// If proxy enabled we have to define a guzzle client with proxy
		if($proxy_enable == 1) {
			$proxy_host = $config->get('proxy_host', '');
			$proxy_port = $config->get('proxy_port', '');
			$proxies = [
				'http'  => 'http://'.$proxy_host.':'.$proxy_port,
				'https' => 'http://'.$proxy_host.':'.$proxy_port
			];

			$client = new Client([
				RequestOptions::PROXY => $proxies,
				RequestOptions::VERIFY => false, # disable SSL certificate validation
				RequestOptions::TIMEOUT => 30, # timeout of 30 seconds
			]);
		}

		$res = new stdClass();

		if ($gotenberg_activation != 1) {
			$res->status = false;
			$res->msg    = Text::_('COM_EMUNDUS_ERROR_EXPORT_API_DESACTIVATED');

			return json_encode($res);
		}

		$user_id = !empty($fnum) ? (int) substr($fnum, -7) : null;
		$em_user = Factory::getApplication()->getSession()->get('emundusUser');

		require JPATH_LIBRARIES . '/emundus/vendor/autoload.php';

		$src  = $file_src;
		$file = explode('/', $file_src);
		$file = end($file);

		$dest      = explode('/', $file_dest);
		$dest_file = array_pop($dest);
		$dest_path = implode('/', $dest);

		try
		{
			if ($file_src_format != 'html')
			{
				$request = Gotenberg::libreOffice($gotenberg_url)
					->outputFilename($dest_file)
					->convert(
						Stream::path($file_src)
					);

				Gotenberg::save($request, $dest_path . '/', $client);
			}
			else
			{
				$request = Gotenberg::chromium($gotenberg_url)
					->pdf()
					->outputFilename($dest_file)
					->html(Stream::path($src));

				Gotenberg::save($request, $dest_path . '/', $client);
			}
			$res->file = $dest_path . '/' . $dest_file . '.pdf';
			$res->status = true;
		}
		catch (\Gotenberg\Exceptions\GotenbergApiErroed $e)
		{
			$res->status = false;
			$res->msg    = Text::_('COM_EMUNDUS_ERROR_EXPORT_MARGIN') . ' GOTEMBERG ERROR (' . $e->getCode() . '): ' . $e->getResponse();
			Log::add($res->msg, Log::ERROR, 'com_emundus.export');

			return json_encode($res);
		}

		return $res;
	}
}
