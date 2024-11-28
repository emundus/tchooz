<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2016 eMundus. All rights reserved.
 * @license     GNU/GPL
 * @author      Benjamin Rivalland
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

class EmundusControllerPayment extends BaseController
{
	protected $app;

	private $m_payment;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		parent::__construct();

		$this->app = Factory::getApplication();
		$this->m_payment = $this->getModel('payment');

		// Attach logging system.
		jimport('joomla.log.log');
		JLog::addLogger(['text_file' => 'com_emundus.payment.php'], JLog::ALL, array('com_emundus.payment'));
	}

	/**
	 * called from post method
	 */
	public function getFlywireConfig()
	{
		$emundusUser = JFactory::getSession()->get('emundusUser');

		$format = $this->input->get('format', '');
		$fnum   = $emundusUser->fnum;
		$body   = file_get_contents('php://input');
		$body   = json_decode($body, true);

		if (!empty($fnum)) {
			$params = JComponentHelper::getParams('com_emundus');
			$this->m_payment->createPaymentOrder($fnum, 'flywire');

			$response = array(
				'success' => true,
				'message' => '',
				'data'    => array(
					'locale'       => 'fr-FR',
					'provider'     => 'embed2.0',
					'currency'     => 'EUR',
					'recipient'    => $params->get('flywire_recipient'),
					'env'          => $params->get('flywire_mode'),
					'fnum'         => $fnum,
					'callback_url' => JUri::base() . 'index.php?option=com_emundus&controller=webhook&task=updateFlywirePaymentInfos&token=' . JFactory::getConfig()->get('secret') . '&guest=1&format=raw',
					'callback_id'  => $this->m_payment->setPaymentUniqid($fnum),
					'amount'       => $this->m_payment->getPrice($fnum) * 100,
				)
			);

			$response['data'] = array_merge($response['data'], $body);
			$response['data'] = $this->m_payment->getFlywireExtendedConfig($response['data']);


			$config              = $response['data'];
			$config['initiator'] = 'emundus';
			$this->m_payment->saveConfig($fnum, $config);

			require_once(JPATH_ROOT . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
			require_once(JPATH_ROOT . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
			$m_files   = $this->getModel('Files');
			$fnumInfos = $m_files->getFnumInfos($fnum);

			EmundusModelLogs::log(95, $fnumInfos['applicant_id'], $fnum, 38, 'u', 'COM_EMUNDUS_PAYMENT_INITIALISATION', json_encode($response['data']));
		}

		if ($format == 'json') {
			echo json_encode($response);
			exit;
		}
		else {
			return $response;
		}
	}

	public function updateFlywirePaymentInfos()
	{
		$data = [];

		$data['status']      = $this->input->get('status', '');
		$data['amount']      = $this->input->get('amount', '');
		$data['at']          = $this->input->get('at', '');
		$data['id']          = $this->input->get('id', '');
		$data['callback_id'] = $this->input->get('callback_id', '');
		$fnum                = $this->input->get('fnum', '');

		if (!empty($fnum) && !empty($data['callback_id'])) {
			$this->m_payment->updateFlywirePaymentInfos($fnum, $data['callback_id'], $data);
		}
		else {
			JLog::add('Can not update payment infos : fnum or callback_id is empty, received : ' . json_encode($data), JLog::WARNING, 'com_emundus.payment');
		}
	}

	public function updateFileTransferPayment()
	{
		$emundusUser = JFactory::getSession()->get('emundusUser');

		$updated = $this->m_payment->updateFileTransferPayment($emundusUser);

		echo json_encode(array('status' => $updated));
		exit;
	}

	public function resetpaymentsession()
	{
		$redirect = $this->input->get('redirect', false);
		$this->m_payment->resetPaymentSession();

		if ($redirect) {
			$this->app->redirect('/');
		}
	}


	public function checkpaymentsession()
	{
		$is_valid = true;

		$fnum = $this->input->get('fnum', false);

		if (!empty($fnum)) {
			$is_valid = $this->m_payment->checkPaymentSession();
		}

		echo json_encode(array('response' => $is_valid));
		exit;
	}

	// Ajoute les données du virement aux différents versements sélectionnés (use case FHDP)
	public function insertvirementdata()
	{
		$results = array('status' => false, 'msg' => '', 'data' => []);
		$user = JFactory::getUser();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id)) {
			try {
				$jinput = JFactory::getApplication()->input;
				$rowids = $jinput->getString('repeat_ids','');
				$virement_date = $jinput->getString('virement_date','');
				$virement_number = $jinput->getString('virement_number','');
				$table_name = $jinput->getString('table_name','');

				if(!empty($rowids) && !empty($virement_date) && !empty($virement_number) && !empty($table_name))
				{
					$virement_date   = DateTime::createFromFormat('Y-m-d', $virement_date);
					$results['data'] = date_format($virement_date,'d/m/Y');
					$virement_date   = date_format($virement_date,'Y-m-d');

					$model = $this->getModel('payment');
					$updated = $model->insertVirementData($rowids, $table_name, $virement_date, $virement_number);
					if (!$updated)
					{
						throw new Exception('Une erreur est survenue', 401);
					}

					$result['msg'] = 'Virement sauvegardé';
					$results['status'] = true;
				}
			}
			catch (Exception $e)
			{
				$results = array('status' => false, 'msg' => JText::_($e->getMessage()), 'data' => [], 'code' => $e->getCode());
			}
		}

		echo json_encode((object)$results);
		exit;
	}
}