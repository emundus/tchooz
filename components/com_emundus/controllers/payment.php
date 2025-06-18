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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Payment\AlterationEntity;
use Tchooz\Entities\Payment\PaymentMethodEntity;
use \Tchooz\Repositories\Payment\ProductRepository;
use \Tchooz\Repositories\Payment\ProductCategoryRepository;
use \Tchooz\Repositories\Payment\CurrencyRepository;
use \Tchooz\Repositories\Payment\PaymentRepository;
use \Tchooz\Repositories\Payment\DiscountRepository;
use \Tchooz\Repositories\Payment\CartRepository;
use \Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Payment\TransactionRepository;
use Tchooz\Repositories\Payment\AlterationRepository;
use \Tchooz\Entities\Payment\ProductEntity;
use \Tchooz\Entities\Payment\DiscountEntity;
use \Tchooz\Entities\Payment\CurrencyEntity;
use \Tchooz\Entities\Payment\ProductCategoryEntity;
use \Tchooz\Entities\Contacts\ContactEntity;
use \Tchooz\Entities\Contacts\ContactAddressEntity;
use Tchooz\Entities\Payment\DiscountType;
use Tchooz\Entities\Payment\AlterationType;
use Tchooz\Entities\Payment\TransactionStatus;

class EmundusControllerPayment extends BaseController
{
	protected $app;

	private $m_payment;

	private $payment_repository;

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
		$this->payment_repository = new PaymentRepository();

		// Attach logging system.
		jimport('joomla.log.log');
		JLog::addLogger(['text_file' => 'com_emundus.payment.php'], JLog::ALL, array('com_emundus.payment'));
	}

	private function sendJsonResponse($response): void
	{
		if ($response['code'] === 403)
		{
			header('HTTP/1.1 403 Forbidden');
			echo $response['message'];
			exit;
		}
		else
		{
			if ($response['code'] === 500)
			{
				header('HTTP/1.1 500 Internal Server Error');
				echo $response['message'];
				exit;
			}
		}

		echo json_encode($response);
		exit;
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
		$user = $this->app->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id)) {
			try {
				$jinput = $this->app->input;
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

	public function getdiscounts()
	{
		$this->checkToken('get', false);
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id)) {
			$limit = $this->input->get('limit', 0);
			$page = $this->input->get('page', 1);

			$discount_repository = new DiscountRepository();
			$discounts = $discount_repository->getDiscounts($limit, $page);

			$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => [
				'count' => $discount_repository->countDiscounts(),
				'datas' => array_map(function ($discount) {
					return [
						'id'          => $discount->getId(),
						'label'       => ['fr' => $discount->getLabel(), 'en' => $discount->getLabel()],
						'description' => $discount->getDescription(),
						'value'       => $discount->getValue(),
						'type'        => $discount->getType(),
						'published'   => $discount->getPublished(),
						'additional_columns' => [
							[
								'key' => Text::_('COM_EMUNDUS_DISCOUNT_DESCRIPTION'),
								'value' => $discount->getDescription(),
								'classes' => '',
								'display' => 'all'
							],
							[
								'key' => Text::_('COM_EMUNDUS_DISCOUNT_VALUE'),
								'value' => $discount->getDisplayedValue(),
								'classes' => '',
								'display' => 'all'
							]
						]
					];
				}, $discounts),
			]];
		}

		$this->sendJsonResponse($response);
	}

	public function getfilterproductcategory()
	{
		$this->checkToken('get', false);
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id)) {
			$product_category_repository = new ProductCategoryRepository();
			$categories = $product_category_repository->getProductCategories();

			$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => array_map(function ($category) {
				return ['value' => $category->getId(), 'label' => $category->getLabel()];
			}, $categories)];
		}

		$this->sendJsonResponse($response);
	}

	public function getproducts()
	{
		$this->checkToken('get', false);
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$user_id = $this->app->getIdentity()->id;
		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id)) {
			$limit = $this->input->getInt('lim', 0);
			$page = $this->input->getInt('page', 1);
			$search = $this->input->getString('recherche', '');

			$filters = [];
			$category = $this->input->getInt('category', 0);
			if (!empty($category)) {
				$filters['category_id'] = $category;
			}

			$product_repository = new ProductRepository();
			$products = $product_repository->getProducts($limit, $page, $filters, $search);
			$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => [
				'count' => $product_repository->countProducts(),
				'datas' => array_map(function ($product) {
					return [
						'id'          => $product->getId(),
						'label'       => ['fr' => $product->label, 'en' => $product->label],
						'description' => $product->description,
						'price'       => $product->getDisplayedPrice(),
						'currency'    => $product->currency->symbol,
						'category_id' => $product->category ?->getId(),
						'category'    => $product->category ?->getLabel(),
						'additional_columns' => [
							[
								'key' => Text::_('COM_EMUNDUS_PRODUCT_DESCRIPTION'),
								'value' => $product->description,
								'classes' => '',
								'display' => 'all'
							],
							[
								'key' => Text::_('COM_EMUNDUS_PRODUCT_PRICE'),
								'value' => $product->getDisplayedPrice(),
								'classes' => '',
								'display' => 'table'
							],
							[
								'key' => Text::_('COM_EMUNDUS_PRODUCT_PRICE'),
								'value' => '<span class="material-symbols-outlined tw-text-neutral-700">payments</span>' . $product->getDisplayedPrice(),
								'classes' => 'tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-border tw-rounded-full tw-px-2 tw-py-1 tw-bg-neutral-300 tw-border-neutral-700 tw-text-neutral-700 !tw-m-0',
								'display' => 'blocs'
							]
						]
					];
				}, $products),
			]];
		}

		$this->sendJsonResponse($response);
	}

	public function getfiltertransactionstatus()
	{
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if ($this->app->getIdentity()->guest != 1) {
			$response = ['code' => 200, 'status' => true, 'message' => Text::_('SUCCESS')];


			$response['data'] = array_map(
				function ($status) {
					return [
						'value'    => $status->value,
						'label' => $status->getLabel(),
						'badge' => $status->getHtmlBadge()
					];
				},
				TransactionStatus::cases()
			);
		}

		$this->sendJsonResponse($response);
	}

	public function getfiltertransactionmethods()
	{
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if ($this->app->getIdentity()->guest != 1) {
			$response = ['code' => 200, 'status' => true, 'message' => Text::_('SUCCESS')];
			$this->payment_repository->getPaymentMethods();

			$response['data'] = array_map(
				function ($payment_method) {
					return [
						'value'    => $payment_method->getId(),
						'label' => $payment_method->getLabel(),
					];
				},
				$this->payment_repository->getPaymentMethods()
			);
		}

		$this->sendJsonResponse($response);
	}

	public function getfiltertransactionsapplicants()
	{
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asAccessAction($this->payment_repository->getActionId(), 'r', $this->app->getIdentity()->id)) {
			$transaction_repository = new TransactionRepository();
			$applicants = $transaction_repository->getTransactionsApplicants();
			$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => $applicants];
		}

		$this->sendJsonResponse($response);
	}

	public function getfiltertransactionsfiles()
	{
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asAccessAction($this->payment_repository->getActionId(), 'r', $this->app->getIdentity()->id)) {
			$transaction_repository = new TransactionRepository();
			$fnums = $transaction_repository->getTransactionsFileNumbers();
			$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => $fnums];
		}

		$this->sendJsonResponse($response);
	}

	public function gettransactions()
	{
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$user_id = $this->app->getIdentity()->id;
		$fnum = $this->input->getString('fnum', '');
		if (EmundusHelperAccess::asAccessAction($this->payment_repository->getActionId(), 'r', $this->app->getIdentity()->id) || (!empty($fnum) && EmundusHelperAccess::isFnumMine($user_id, $fnum))) {
			$limit = $this->input->getInt('lim', 10);
			$page = $this->input->getInt('page', 1);
			$search = $this->input->getString('recherche', '');

			$filters = [];
			if (!empty($fnum)) {
				$filters['fnum'] = $fnum;
			}

			$status = $this->input->getString('status', '');
			if (!empty($status)) {
				$filters['status'] = $status;
			}
			$applicant_id = $this->input->getInt('applicant_id', 0);
			if (!empty($applicant_id)) {
				$filters['applicant_id'] = $applicant_id;
			}
			$payment_method_id = $this->input->getInt('payment_method_id', 0);
			if (!empty($payment_method_id)) {
				$filters['payment_method_id'] = $payment_method_id;
			}

			$transaction_repository = new TransactionRepository();
			$transactions = $transaction_repository->getTransactions($limit, $page, $filters, '', $search);
			$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => [
				'count' => $transaction_repository->countTransactions($filters, '', $search),
				'datas' => array_map(function ($transaction) use ($transaction_repository) {
					$service_label = $transaction_repository->getServiceLabel($transaction->getSynchronizerId());
					$customer = $transaction_repository->getTransactionCustomer($transaction);

					$array = $transaction->serialize();
					$array['additional_columns'] = [
						[
							'key'     => Text::_('COM_EMUNDUS_TRANSACTION_APPLICANT'),
							'value'   => $customer->getFullName(),
							'classes' => 'tw-font-semibold',
							'display' => 'all'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_TRANSACTION_SYNCHRONIZER'),
							'value'   => $service_label,
							'classes' => 'tw-text-profile-full',
							'display' => 'blocs'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_TRANSACTION_SYNCHRONIZER'),
							'value'   => $service_label,
							'display' => 'table'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_TRANSACTION_DATE'),
							'value'   => $transaction->getCreatedAt(true),
							'classes' => '',
							'display' => 'all'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_CAMPAIGN'),
							'value'   => $transaction_repository->getCampaignLabel($transaction->getFnum()),
							'classes' => '',
							'display' => 'all'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_TRANSACTION_AMOUNT_AND_STATUS'),
							'value'   => '<div class="tw-flex tw-gap-3 tw-items-center"><div class="tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-border tw-rounded-full tw-px-2 tw-py-1 tw-bg-neutral-300 tw-border-neutral-700 tw-text-neutral-700 !tw-m-0 tw-w-fit"><span class="material-symbols-outlined tw-text-neutral-700">payments</span>' . $transaction->getAmount() . ' ' . $transaction->getCurrency()->getSymbol() . '</div>' . $transaction->getStatus()->getHtmlBadge() . "</div>",
							'classes' => '',
							'display' => 'blocs'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_TRANSACTION_AMOUNT'),
							'value'   => $transaction->getAmount() . ' ' . $transaction->getCurrency()->getSymbol(),
							'display' => 'table'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_TRANSACTION_STATUS'),
							'value'   => $transaction->getStatus()->getHtmlBadge(),
							'classes' => '',
							'display' => 'table'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_TRANSACTION_PAYMENT_METHOD'),
							'value' => $transaction->getPaymentMethod()->getLabel(),
							'classes' => '',
							'display' => 'all'
						],
					];

					return $array;
				}, $transactions),
			]];
		}

		$this->sendJsonResponse($response);
	}

	public function exporttransaction()
	{
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asAccessAction($this->payment_repository->getActionId(), 'r', $this->app->getIdentity()->id))
		{
			$transaction_id = $this->input->getInt('id', 0);
			$transaction_ids = $this->input->getString('ids', '');

			if (!empty($transaction_ids)) {
				$transaction_ids = explode(',', $transaction_ids);
			} else {
				$transaction_ids = [$transaction_id];
			}

			$transaction_repository = new TransactionRepository();
			$transactions = $transaction_repository->getTransactions(0, 1, ['id' => $transaction_ids]);

			$lines = $transaction_repository->prepareExport($transactions);

			if (!empty($lines)) {
				$today  = date("MdYHis");
				$name   = md5($today.rand(0,10));
				$name   = 'transactions-' . $name.'.csv';
				$path = JPATH_SITE . '/tmp/' . $name;

				if (!$csv_file = fopen($path, 'w+')) {
					$response = [
						'code' => 500,
						'message' => Text::_('COM_EMUNDUS_EXPORTS_ERROR'),
						'status' => false
					];
				} else {
					fwrite($csv_file, "\xEF\xBB\xBF"); // Ajoute le BOM UTF-8
					foreach ($lines as $line) {
						fputcsv($csv_file, $line, ';');
					}
					fclose($csv_file);
					$export_link = JUri::root() . 'tmp/' . $name;

					$response = [
						'code' => 200,
						'message' => Text::_('COM_EMUNDUS_EXPORTS_SUCCESS'),
						'status' => true,
						'download_file' => $export_link
					];
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getProductById()
	{
		$this->checkToken('get', false);
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id)) {
			$id = $this->input->get('id', 0);

			$product_repository = new ProductRepository();
			$product_entity = $product_repository->getProductById($id);

			if (!empty($product_entity)) {
				$product = $product_entity->serialize();
				$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => $product];
			} else {
				$response = ['code' => 404, 'message' => Text::_('COM_EMUNDUS_PRODUCT_NOT_FOUND'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function saveProduct()
	{
		$this->checkToken();
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id)) {
			$payment_repository = new PaymentRepository();
			$id = $this->input->getInt('id', 0);
			$label = $this->input->getString('label', '');
			$description = $this->input->getString('description', '');
			$price = $this->input->getFloat('price', 0);
			$currency_id = $this->input->getInt('currency_id', '');
			$category_id = $this->input->getInt('category_id', 0);
			$illimited = $this->input->getInt('illimited', 1);
			$quantity = $this->input->getInt('quantity', 0);
			$available_from = $this->input->getString('available_from', '');
			$available_to = $this->input->getString('available_to', '');
			$campaigns = $this->input->getString('campaigns', '');
			$campaigns = !empty($campaigns) ? explode(',', $campaigns) : [];

			$product_entity = new ProductEntity();
			$product_entity->setId($id);
			$product_entity->setLabel($label);
			$product_entity->setDescription($description);
			$product_entity->setPrice($price);
			$product_entity->setIllimited($illimited === 1);
			$product_entity->setQuantity($quantity);

			if (!empty($available_from)) {
				$product_entity->available_from = new \DateTime($available_from);
			} else {
				$product_entity->available_from = null;
			}

			if (!empty($available_to)) {
				$product_entity->available_to = new \DateTime($available_to);
			} else {
				$product_entity->available_to = null;
			}

			$currency_repository = new CurrencyRepository();
			if (empty($currency_id))
			{
				$addon                 = $payment_repository->getAddon();
				$payment_configuration = $addon->getConfiguration();

				if (!empty($payment_configuration['currency_id']))
				{
					$product_entity->currency = $currency_repository->getCurrencyById($payment_configuration['currency_id']);
				}
			} else {
				$product_entity->currency = $currency_repository->getCurrencyById($currency_id);
			}

			if (!empty($category_id)) {
				$product_entity->category = new ProductCategoryEntity($category_id);
			}

			$product_entity->setCampaigns($campaigns);

			$product_repository = new ProductRepository();
			$product_id = $product_repository->flush($product_entity);

			if (!empty($product_id)) {
				$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_PRODUCT_SAVED'), 'status' => true];
			} else {
				$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_PRODUCT_NOT_SAVED'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getDiscountById()
	{
		$this->checkToken('get');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id)) {
			$id = $this->input->get('id', 0);

			$discount_repository = new DiscountRepository();
			$discount_entity = $discount_repository->getDiscountById($id);

			if (!empty($discount_entity)) {
				$discount = $discount_entity->serialize();
				$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => $discount];
			} else {
				$response = ['code' => 404, 'message' => Text::_('COM_EMUNDUS_DISCOUNT_NOT_FOUND'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function saveDiscount()
	{
		$this->checkToken();
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id)) {
			$id = $this->input->getInt('id', 0);
			$label = $this->input->getString('label', '');
			$description = $this->input->getString('description', '');
			$value = $this->input->getFloat('value', 0);
			$type = $this->input->getString('type', '');
			$currency_id = $this->input->getInt('currency_id', '');
			$available_from = $this->input->getString('available_from', '');
			$available_to = $this->input->getString('available_to', '');
			$quantity = $this->input->getInt('quantity', 0);
			$published = $this->input->getInt('published', 1);

			$discount_entity = new DiscountEntity();
			$discount_entity->setId($id);
			$discount_entity->setLabel($label);
			$discount_entity->setDescription($description);
			$discount_entity->setValue($value);
			$discount_entity->setType($type);
			$discount_entity->setPublished($published);
			$discount_entity->setQuantity($quantity);
			$discount_entity->setAvailableFrom(!empty($available_from) ? new \DateTime($available_from) : null);
			$discount_entity->setAvailableTo(!empty($available_to) ? new \DateTime($available_to) : null);

			if (!empty($currency_id)) {
				$discount_entity->setCurrency(new CurrencyEntity($currency_id));
			}

			try {
				$discount_repository = new DiscountRepository();
				$saved = $discount_repository->flush($discount_entity);

				if ($saved) {
					$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_DISCOUNT_SAVED'), 'status' => true];
				} else {
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_DISCOUNT_NOT_SAVED'), 'status' => false];
				}
			} catch (\Exception $e) {
				$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_DISCOUNT_NOT_SAVED') . ': ' . $e->getMessage(), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getCurrencies()
	{
		$this->checkToken('get');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id)) {
			$currency_repository = new CurrencyRepository();
			$currencies = $currency_repository->getCurrencies(1000);

			$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => $currencies];
		}

		$this->sendJsonResponse($response);
	}

	public function getPaymentMethods()
	{
		$this->checkToken('get');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id)) {
			$payment_repository = new PaymentRepository();
			$payment_methods = $payment_repository->getPaymentMethods();

			$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => array_map(function ($payment_method) {
				return $payment_method->serialize();
			}, $payment_methods)];
		}

		$this->sendJsonResponse($response);
	}

	public function getPaymentServices()
	{
		$this->checkToken('get');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id)) {
			$payment_repository = new PaymentRepository();
			$payment_services = $payment_repository->getPaymentServices();

			$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => $payment_services];
		}

		$this->sendJsonResponse($response);
	}

	public function savePaymentStepRules()
	{
		$this->checkToken();
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$step_id = $this->input->getInt('id', 0);

			if (!empty($step_id)) {
				try {
					$adjust_balance = $this->input->getInt('adjustBalance', 0);
					if ($adjust_balance == 0) {
						$adjust_balance_step_id = 0;
					} else {
						$adjust_balance_step_id = $this->input->getInt('adjustBalanceStepId', 0);

						if (empty($adjust_balance_step_id)) {
							throw new \Exception(Text::_('COM_EMUNDUS_PAYMENT_STEP_ADJUST_BALANCE_STEP_ID_REQUIRED'));
						}
					}

					$mandatory_products = $this->input->getString('mandatoryProducts', '');
					$mandatory_products = !empty($mandatory_products) ? explode(',', $mandatory_products) : [];
					$optional_products = $this->input->getString('optionalProducts', '');
					$optional_products = !empty($optional_products) ? explode(',', $optional_products) : [];
					$payment_methods = $this->input->getString('paymentMethods', '');
					$payment_methods = !empty($payment_methods) ? explode(',', $payment_methods) : [];
					$synchronizer_id = $this->input->getInt('synchronizerId', 0);
					if (empty($synchronizer_id)) {
						throw new \Exception(Text::_('COM_EMUNDUS_PAYMENT_STEP_SYNCHRONIZER_ID_REQUIRED'));
					}

					$advance_type = $this->input->getInt('advanceType', 0);
					$advance_amount_editable = $this->input->getInt('advanceAmountEditableByApplicant', 0);
					$advance_amount = $this->input->getFloat('advanceAmount', 0);
					$advance_amount_type = $this->input->getString('advanceAmountType', 'fixed');
					$installment_rules = $this->input->getString('installmentRules', '');
					$installment_rules = !empty($installment_rules) ? json_decode($installment_rules) : [];
					$installment_monthday = $this->input->getInt('installmentMonthday', 0);
					$installment_effect_date = $this->input->getString('installmentEffectDate', '');
					$description = $this->input->getRaw('description', '');

					$payment_repository = new PaymentRepository();
					$payment_step_entity = $payment_repository->getPaymentStepById($step_id);
					$payment_step_entity->setDescription($description);
					$payment_step_entity->setAdjustBalance($adjust_balance);
					$payment_step_entity->setAdjustBalanceStepId($adjust_balance_step_id);
					$payment_step_entity->setAdvanceType($advance_type);
					$payment_step_entity->setIsAdvanceAmountEditableByApplicant($advance_amount_editable);
					$payment_step_entity->setAdvanceAmount($advance_amount);
					$payment_step_entity->setAdvanceAmountType(DiscountType::getInstance($advance_amount_type));
					$payment_step_entity->setInstallmentMonthday($installment_monthday);
					if (!empty($installment_effect_date)) {
						$payment_step_entity->setInstallmentEffectDate($installment_effect_date);
					} else {
						$payment_step_entity->setInstallmentEffectDate(null);
					}

					$products = [];
					$product_repository = new ProductRepository();
					if (!empty($mandatory_products)) {
						foreach ($mandatory_products as $product) {
							$product_entity = $product_repository->getProductById($product);
							$product_entity->setMandatory(1);
							$products[] = $product_entity;
						}
					}
					if (!empty($optional_products)) {
						foreach ($optional_products as $product) {
							$product_entity = $product_repository->getProductById($product);
							$product_entity->setMandatory(0);
							$products[] = $product_entity;
						}
					}
					$payment_step_entity->setProducts($products);

					$payment_method_entities = [];
					foreach($payment_methods as $payment_method_id)
					{
						$payment_method_entities[] = new PaymentMethodEntity($payment_method_id);
					}
					$payment_step_entity->setPaymentMethods($payment_method_entities);

					$found_service = false;
					$services = $payment_repository->getPaymentServices();
					foreach($services as $service)
					{
						if ($service->id == $synchronizer_id) {
							$found_service = true;
							break;
						}
					}
					if (!$found_service)
					{
						throw new \Exception(Text::_('COM_EMUNDUS_PAYMENT_STEP_SYNCHRONIZER_NOT_FOUND'));
					}

					$payment_step_entity->setSynchronizerId($synchronizer_id);
					$payment_step_entity->setInstallmentRules($installment_rules);

					$saved = $payment_repository->flushPaymentStep($payment_step_entity);
					if ($saved) {
						$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_PAYMENT_STEP_SAVED'), 'status' => true];
					} else {
						$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_PAYMENT_STEP_NOT_SAVED'), 'status' => false];
					}
				} catch (\Exception $e) {
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_PAYMENT_STEP_NOT_SAVED') . ': ' . $e->getMessage(), 'status' => false];
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function deleteproduct()
	{
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$id = $this->app->input->getInt('id', 0);
			$ids = $this->app->input->getString('ids', '');

			if (!empty($id)) {
				$product_repository = new ProductRepository();
				$deleted = $product_repository->delete($id);

				if ($deleted) {
					$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_PRODUCT_DELETED'), 'status' => true];
				} else {
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_PRODUCT_NOT_DELETED'), 'status' => false];
				}
			}
			else if (!empty($ids))
			{
				$ids = explode(',', $ids);
				$product_repository = new ProductRepository();

				$deleted = [];
				foreach ($ids as $id) {
					$deleted[] = $product_repository->delete($id);
				}

				if (!in_array(false, $deleted)) {
					$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_PRODUCT_DELETED'), 'status' => true];
				} else {
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_PRODUCT_NOT_DELETED'), 'status' => false];
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function duplicateproduct()
	{
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];
		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$id = $this->app->input->getInt('id', 0);
			if (!empty($id))
			{
				$product_repository = new ProductRepository();
				$product = $product_repository->getProductById($id);

				if (!empty($product)) {
					$product->setId(0);

					$duplicated = $product_repository->flush($product);

					if ($duplicated) {
						$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_PRODUCT_DUPLICATED'), 'status' => true];
					} else {
						$response = ['code' => 250, 'message' => Text::_('COM_EMUNDUS_PRODUCT_NOT_DUPLICATED'), 'status' => true];
					}
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function deletediscount()
	{
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$discount_id = $this->input->getInt('id', 0);

			if (!empty($discount_id)) {
				$discount_repository = new DiscountRepository();
				$deleted = $discount_repository->delete($discount_id);

				if ($deleted) {
					$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_DISCOUNT_DELETED'), 'status' => true];
				} else {
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_DISCOUNT_NOT_DELETED'), 'status' => false];
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function addProductToCart()
	{
		$this->checkToken('post');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$cart_id = $this->input->getInt('cart_id', 0);
		$product_id = $this->input->getInt('product_id', 0);
		$product_ids = $this->input->getString('product_ids', '');

		if (!empty($cart_id) && (!empty($product_id) || !empty($product_ids))) {
			if (!empty($product_ids)) {
				$product_ids = explode(',', $product_ids);
			} else {
				$product_ids = [$product_id];
			}

			$cart_repository = new CartRepository();
			$cart = $cart_repository->getCartById($cart_id);
			$current_user = $this->app->getIdentity();
			if ($cart_repository->canUserUpdateCart($cart, $current_user->id)) {
				foreach($product_ids as $key => $product_id) {
					foreach ($cart->getAvailableProducts() as $available_product) {
						if ($available_product->getId() === $product_id && $available_product->getMandatory() === 1)
						{
							unset($product_ids[$key]);
							break;
						}
					}
				}

				if (!empty($product_ids)) {
					$products_added = [];
					foreach($product_ids as $key => $product_id) {
						$products_added[] = $cart_repository->addProduct($cart, $product_id, $current_user->id);
					}
					$added = !in_array(false, $products_added);

					if ($added) {
						$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_PRODUCT_ADDED'), 'status' => true, 'data' => $cart->serialize()];
					} else {
						$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_PRODUCT_NOT_ADDED'), 'status' => false];
					}
				} else {
					$response['message'] = Text::_('COM_EMUNDUS_CART_OPERATION_NOT_PERMITTED');
				}
			}
		} else {
			$response = ['code' => 400, 'message' => Text::_('COM_EMUNDUS_INVALID_INPUT'), 'status' => false];
		}

		$this->sendJsonResponse($response);
	}

	public function removeProductFromCart()
	{
		$this->checkToken('post');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$cart_id = $this->input->getInt('cart_id', 0);
		$product_id = $this->input->getInt('product_id', 0);

		if (!empty($cart_id) && !empty($product_id)) {
			$cart_repository = new CartRepository();
			$cart = $cart_repository->getCartById($cart_id);

			if ($cart_repository->canUserUpdateCart($cart, $this->app->getIdentity()->id)) {
				// is it a removable product by the applicant ?
				$removable = false;
				foreach($cart->getAvailableProducts() as $available_product) {
					if ($available_product->getId() === $product_id && $available_product->getMandatory() === 0) {
						$removable = true;
						break;
					}
				}

				if ($removable) {
					$removed = $cart_repository->removeProduct($cart, $product_id, $this->app->getIdentity()->id);

					if ($removed) {
						$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_PRODUCT_REMOVED'), 'status' => true, 'data' => $cart->serialize()];
					} else {
						$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_PRODUCT_NOT_REMOVED'), 'status' => false];
					}
				} else {
					$response['message'] = Text::_('COM_EMUNDUS_CART_OPERATION_NOT_PERMITTED');
				}
			}
		} else {
			$response = ['code' => 400, 'message' => Text::_('COM_EMUNDUS_INVALID_INPUT'), 'status' => false];
		}

		$this->sendJsonResponse($response);
	}

	public function addAlterationToCart()
	{
		$this->checkToken('post');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$payment_repository = new PaymentRepository();
		$cart_id = $this->input->getInt('cart_id', 0);
		$cart_repository = new CartRepository();
		$cart = $cart_repository->getCartById($cart_id);

		if (EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'u', $this->app->getIdentity()->id, $cart->getFnum()))
		{
			$product_id = $this->input->getInt('product_id', 0);
			$discount_id = $this->input->getInt('discount_id', 0);
			$type = $this->input->getString('type', 'fixed');
			$amount = $this->input->getFloat('amount', 0);
			$description = $this->input->getString('description', '');

			if (!empty($product_id)) {
				$product = new ProductEntity($product_id);
			} else {
				$product = null;
			}

			if (!empty($discount_id)) {
				$discount_repository = new DiscountRepository();
				$discount = $discount_repository->getDiscountById($discount_id);
			} else {
				$discount = null;
			}

			$alteration = new AlterationEntity(0, $cart->getId(), $product, $discount, $description, $amount, AlterationType::from($type));
			$saved = $cart_repository->addAlteration($cart, $alteration, $this->app->getIdentity()->id);

			if ($saved) {
				$cart = $cart_repository->getCartById($cart_id);
				$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_CART_UPDATED'), 'status' => true, 'data' => $cart->serialize()];
			} else {
				$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_CART_NOT_UPDATED'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function updateCartAlteration()
	{
		//$this->checkToken('post');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$payment_repository = new PaymentRepository();
		$cart_id            = $this->input->getInt('cart_id', 0);
		$cart_repository    = new CartRepository();
		$cart               = $cart_repository->getCartById($cart_id);

		if (EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'u', $this->app->getIdentity()->id, $cart->getFnum()))
		{
			$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_CART_NOT_UPDATED'), 'status' => false];
			$saved = false;
			$alteration_id = $this->input->getInt('id', 0);

			if (!empty($alteration_id)) {
				$alteration_repository = new AlterationRepository();
				$alteration = $alteration_repository->getAlterationById($alteration_id);

				if (empty($alteration)) {
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_CART_ALTERATION_NOT_FOUND'), 'status' => false];
				} else {
					$product_id = $this->input->getInt('product_id', 0);
					$discount_id = $this->input->getInt('discount_id', 0);
					$type = $this->input->getString('type', 'fixed');
					$amount = $this->input->getFloat('amount', 0);
					$description = $this->input->getString('description', '');

					if (!empty($product_id)) {
						$product = new ProductEntity($product_id);
					} else {
						$product = null;
					}

					if (!empty($discount_id)) {
						$discount_repository = new DiscountRepository();
						$discount = $discount_repository->getDiscountById($discount_id);
					} else {
						$discount = null;
					}

					$alteration->setProduct($product);
					$alteration->setDiscount($discount);
					$alteration->setType(AlterationType::from($type));
					$alteration->setAmount($amount);
					$alteration->setDescription($description);
					$saved = $alteration_repository->flush($alteration, $this->app->getIdentity()->id);

					if ($saved) {
						$cart = $cart_repository->getCartById($cart_id);
						$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_CART_UPDATED'), 'status' => true, 'data' => $cart->serialize()];
					}
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function removeAlterationFromCart()
	{
		//$this->checkToken('post');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$payment_repository = new PaymentRepository();
		$cart_id = $this->input->getInt('cart_id', 0);
		$cart_repository = new CartRepository();
		$cart = $cart_repository->getCartById($cart_id);
		$alteration_id = $this->input->getInt('alteration_id', 0);
		if (EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'u', $this->app->getIdentity()->id, $cart->getFnum()))
		{
			$removed = false;

			if (!empty($alteration_id)) {

				$alteration_repository = new AlterationRepository();
				$alteration = $alteration_repository->getAlterationById($alteration_id);

				if (!empty($alteration)) {
					$removed = $cart_repository->removeAlteration($cart, $alteration, $this->app->getIdentity()->id);
				}
			}

			if ($removed) {
				$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_CART_UPDATED'), 'status' => true, 'data' => $cart_repository->getCartById($cart->getId())->serialize()];
			} else {
				$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_CART_NOT_UPDATED'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function updateInstallmentDebitNumber()
	{
		$this->checkToken('post');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$cart_id = $this->input->getInt('cart_id', 0);

		if (!empty($cart_id)) {
			$number = $this->input->getInt('number', 1);
			$cart_repository = new CartRepository();
			$cart = $cart_repository->getCartById($cart_id);

			if ($cart_repository->canUserUpdateCart($cart, $this->app->getIdentity()->id)) {
				try {
					$updated = $cart_repository->updateInstallmentDebitNumber($cart, $number, $this->app->getIdentity()->id);
				} catch (Exception $e) {
					Log::add('Error while updating installment debit number: ' . $e->getMessage(), Log::ERROR, 'emundus');
					$updated = false;
				}

				if ($updated) {
					// reload the cart to get potential custom event handler data
					$cart = $cart_repository->getCartById($cart_id);
					$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_CART_UPDATED'), 'status' => true, 'data' => $cart->serialize()];
				} else {
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_CART_NOT_UPDATED'), 'status' => false];
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function updateInstallmentMonthday()
	{
		$this->checkToken('post');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$cart_id = $this->input->getInt('cart_id', 0);

		if (!empty($cart_id)) {
			$monthday = $this->input->getInt('monthday', 1);
			$cart_repository = new CartRepository();
			$cart = $cart_repository->getCartById($cart_id);

			if ($cart_repository->canUserUpdateCart($cart, $this->app->getIdentity()->id)) {
				try {
					$updated = $cart_repository->updateInstallmentMonthday($cart, $monthday, $this->app->getIdentity()->id);
				} catch (Exception $e) {
					Log::add('Error while updating installment debit number: ' . $e->getMessage(), Log::ERROR, 'emundus');
					$updated = false;
				}

				if ($updated) {
					$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_CART_UPDATED'), 'status' => true, 'data' => $cart->serialize()];
				} else {
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_CART_NOT_UPDATED'), 'status' => false];
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function updatePayAdvance()
	{
		$this->checkToken('post');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$cart_id = $this->input->getInt('cart_id', 0);

		if (!empty($cart_id)) {
			$cart_repository = new CartRepository();
			$cart = $cart_repository->getCartById($cart_id);
			$pay_advance = $this->input->getInt('pay_advance', 0);

			if ($cart_repository->canUserUpdateCart($cart, $this->app->getIdentity()->id)) {
				switch($cart->getPaymentStep()->getAdvanceType()) {
					case 0: // forbidden to pay advance
						$pay_advance = 0;
						break;
					case 1: // free to pay advance
						break;
					case 2: // forced to pay advance only
						$pay_advance = 1;
						break;
				}

				$cart->setPayAdvance($pay_advance);
				$saved = $cart_repository->saveCart($cart);

				if ($saved) {
					$cart = $cart_repository->getCartById($cart_id);
					$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_CART_UPDATED'), 'status' => true, 'data' => $cart->serialize()];
				} else {
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_CART_NOT_UPDATED'), 'status' => true, 'data' => $cart->serialize()];
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getProductCategoryById()
	{
		$this->checkToken('get');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$id = $this->input->getInt('id', 0);

			if (!empty($id)) {
				try {
					$product_category_entity = new ProductCategoryEntity($id);
					$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => $product_category_entity->serialize()];
				} catch (Exception $e) {
					$response = ['code' => 404, 'message' => Text::_('COM_EMUNDUS_PRODUCT_CATEGORY_NOT_FOUND'), 'status' => false];
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getProductCategories()
	{
		$this->checkToken('get');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$product_category_repository = new ProductCategoryRepository();
			$product_categories = $product_category_repository->getProductCategories();

			$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => array_map(function ($category) {
				return $category->serialize();
			}, $product_categories)];
		}

		$this->sendJsonResponse($response);
	}

	public function saveProductCategory()
	{
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$id = $this->input->getInt('id', 0);
			$label = $this->input->getString('label', '');
			$published = $this->input->getInt('published', 1);

			$product_category_entity = new ProductCategoryEntity($id);
			$product_category_entity->setLabel($label);
			$product_category_entity->setPublished($published);

			$product_category_repository = new ProductCategoryRepository();

			if ($product_category_repository->flush($product_category_entity))
			{
				$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_PRODUCT_CATEGORY_SAVED'), 'status' => true];
			}
			else
			{
				$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_PRODUCT_CATEGORY_NOT_SAVED'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function selectPaymentMethod()
	{
		$this->checkToken();
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$cart_id = $this->input->getInt('cart_id', 0);
		$payment_method_id = $this->input->getInt('payment_method_id', 0);

		if (!empty($cart_id) && !empty($payment_method_id)) {
			$cart_repository = new CartRepository();
			$cart = $cart_repository->getCartById($cart_id);

			$current_user = $this->app->getIdentity();
			if ($cart_repository->canUserUpdateCart($cart, $current_user->id)) {
				try {
					$allowed_payment_methods = $cart->getPaymentMethods();

					/**
					 * Managers are allowed to select any payment method
					 */
					if (EmundusHelperAccess::asAccessAction($this->payment_repository->getActionId(), 'u', $current_user->id, $cart->getFnum())) {
						$allowed_payment_methods = $this->payment_repository->getPaymentMethods();
					}
					$selected = $cart_repository->selectPaymentMethod($cart, $payment_method_id, $allowed_payment_methods, $this->app->getIdentity()->id);

					if ($selected) {
						// reload cart to get potential custom events changes
						$cart = $cart_repository->getCartById($cart->getId());
						$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_PAYMENT_METHOD_SELECTED'), 'status' => true, 'data' => $cart->serialize()];
					} else {
						$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_PAYMENT_METHOD_NOT_SELECTED'), 'status' => false];
					}
				} catch (Exception $e) {
					Log::add('Error selecting payment method: ' . $e->getMessage(), Log::ERROR, 'com_emundus.payment');
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_PAYMENT_METHOD_NOT_SELECTED') . ': ' . $e->getMessage(), 'status' => false];
				}
			}
		} else {
			$response = ['code' => 400, 'message' => Text::_('COM_EMUNDUS_INVALID_INPUT'), 'status' => false];
		}

		$this->sendJsonResponse($response);
	}

	public function saveCustomer()
	{
		$this->checkToken();
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$user_id = $this->app->getIdentity()->id;
		$cart_id = $this->input->getInt('cart_id', 0);

		if (!empty($cart_id)) {
			$cart_repository = new CartRepository();
			$cart = $cart_repository->getCartById($cart_id);

			if ($cart_repository->canUserUpdateCart($cart, $user_id)) {

				$db = Factory::getContainer()->get('DatabaseDriver');

				$contact_repository = new ContactRepository($db);
				$contact_entity = $contact_repository->getByUserId($cart->getCustomer()->getUserId());

				if (!empty($contact_entity->getId()) && $contact_entity->getUserId() === $cart->getCustomer()->getUserId())
				{
					$email = $this->input->getString('email', '');
					$firstname = $this->input->getString('firstname', '');
					$lastname = $this->input->getString('lastname', '');
					$phone = $this->input->getString('phone', '');
					$address1 = $this->input->getString('address1', '');
					$address2 = $this->input->getString('address2', '');
					$zip = $this->input->getString('zip', '');
					$city = $this->input->getString('city', '');
					$state = $this->input->getString('state', '');
					$country = $this->input->getInt('country', 0);

					$contact_entity->setEmail($email);
					$contact_entity->setFirstname($firstname);
					$contact_entity->setLastname($lastname);
					$contact_entity->setPhone1($phone);

					$address_entity = $contact_entity->getAddress();
					if (empty($address_entity)) {
						$address_entity = new ContactAddressEntity($contact_entity->getId(), $address1, $address2, $city, $state, $zip, $country);
					} else {
						$address_entity->setContactId($contact_entity->getId());
						$address_entity->setAddress1($address1);
						$address_entity->setAddress2($address2);
						$address_entity->setCity($city);
						$address_entity->setState($state);
						$address_entity->setZip($zip);
						$address_entity->setCountry($country);
					}

					$contact_entity->setAddress($address_entity);
					try {
						$contact_id = $contact_repository->flush($contact_entity);

						if ($contact_id) {
							$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_CUSTOMER_SAVED'), 'status' => true];
						} else {
							$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_CUSTOMER_NOT_SAVED'), 'status' => false];
						}
					} catch (Exception $e) {
						$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_CUSTOMER_NOT_SAVED') . ': ' . $e->getMessage(), 'status' => false];
						Log::add('Error saving customer: ' . $e->getMessage(), Log::ERROR, 'com_emundus.customer');
					}
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getCartByFnum()
	{
		$this->checkToken();
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$fnum = $this->input->getString('fnum', '');
		$payment_repository = new PaymentRepository();
		if (!empty($fnum) && EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'r', $this->app->getIdentity()->id, $fnum)) {
			$cart_repository = new CartRepository();
			if (!class_exists('EmundusModelWorkflow')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			}
			$m_workflow = new \EmundusModelWorkflow();
			$step = $m_workflow->getPaymentStepFromFnum($fnum);

			$cart = $cart_repository->getCartByFnum($fnum, $step->id);

			if (!empty($cart)) {
				$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => $cart->serialize()];
			} else {
				$response = ['code' => 404, 'message' => Text::_('COM_EMUNDUS_CART_NOT_FOUND'), 'status' => false];
			}
		} else {
			$response = ['code' => 400, 'message' => Text::_('COM_EMUNDUS_INVALID_INPUT'), 'status' => false];
		}


		$this->sendJsonResponse($response);
	}

	public function checkoutCart()
	{
		$this->checkToken('post');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$cart_id = $this->input->getInt('cart_id', 0);

		if (!empty($cart_id)) {
			$cart_repository = new CartRepository();
			$cart = $cart_repository->getCartById($cart_id);
			$customer = $cart->getCustomer();
			$current_user = $this->app->getIdentity();

			if ($customer->getUserId() == $current_user->id) {
				try {
					$verified = $cart_repository->verifyCart($cart, $current_user->id);

					if ($verified) {
						$form = $cart_repository->checkoutCart($cart, $current_user->id);

						if (!empty($form)) {
							$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_CART_CHECKED_OUT'), 'status' => true, 'data' => $form];
						} else {
							$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_CART_NOT_CHECKED_OUT'), 'status' => false];
						}
					}
				} catch (Exception $e) {
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_CART_NOT_CHECKED_OUT') . ': ' . Text::_($e->getMessage()), 'status' => false];
					Log::add('Error checking out cart: ' . $e->getMessage(), Log::ERROR, 'com_emundus.payment');
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function confirmCart()
	{
		$this->checkToken('post');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$cart_id = $this->input->getInt('cart_id', 0);

		if (!empty($cart_id)) {
			$cart_repository = new CartRepository();
			$cart = $cart_repository->getCartById($cart_id);

			if (EmundusHelperAccess::asAccessAction($this->payment_repository->getActionId(), 'u', $this->app->getIdentity()->id, $cart->getFnum())) {
				$cart->setPaymentMethods($this->payment_repository->getPaymentMethods());

				try {
					$cart_repository->verifyCart($cart, $this->app->getIdentity()->id);

					$custom_external_reference = $this->input->getString('custom_external_reference', '');
					$transaction = $cart_repository->createTransaction($cart, $custom_external_reference);

					$transaction->setStatus(TransactionStatus::CONFIRMED);
					$transaction_repository = new TransactionRepository();
					$saved = $transaction_repository->saveTransaction($transaction, $this->app->getIdentity()->id);

					if ($saved) {
						$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_CART_CONFIRMED'), 'status' => true];
					} else {
						$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_CART_NOT_CONFIRMED'), 'status' => false];
					}
				} catch (Exception $e) {
					$response = ['code' => 500, 'message' => Text::_($e->getMessage()), 'status' => false];
					Log::add('Error confirming cart: ' . $e->getMessage(), Log::ERROR, 'com_emundus.payment');
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getCountries() {
		$this->checkToken('get');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (!$this->app->getIdentity()->guest) {
			$current_language = Factory::getApplication()->getLanguage()->getTag();
			$short_language = substr($current_language, 0, 2);

			$payment_repository = new PaymentRepository();
			$countries = $payment_repository->getCountries($short_language);

			if (!empty($countries)) {
				$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => $countries];
			} else {
				$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_COUNTRIES_NOT_FOUND'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getTransationsQueueHistory()
	{
		//$this->checkToken('get');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$response = ['code' => 200, 'message' => '', 'status' => true, 'data' => []];
			$synchronizer_id = $this->input->getInt('synchronizer_id', 0);
			$limit = $this->input->getInt('lim', 0);
			$page = $this->input->getInt('page', 1);

			$transaction_repository = new TransactionRepository();
			$count = $transaction_repository->getTransactionsInQueue(['pending', 'updated'], [], [$synchronizer_id], 0, $page);
			$transactions = $transaction_repository->getTransactionsInQueue(['pending', 'updated'], [], [$synchronizer_id], $limit, $page);

			if (!class_exists('EmundusHelperDate')) {
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/date.php');
			}

			$response['data'] = [
				'count' => $count,
				'datas' => array_map(function ($transaction) {
					$json_data = json_decode($transaction->data, true);
					$transaction->data = $json_data;

					$transaction->additional_columns = [
						[
							'key' => Text::_('COM_EMUNDUS_SOGECOMMERCE_HISTORY_TRANSACTION_ID'),
							'value' => $transaction->id,
							'classes' => '',
							'display' => 'all'
						],
						[
							'key' => Text::_('COM_EMUNDUS_SOGECOMMERCE_HISTORY_TRANSACTION_CREATED_AT'),
							'value' => EmundusHelperDate::displayDate($transaction->created_at, 'DATE_FORMAT_LC2', 0),
							'classes' => '',
							'display' => 'all'
						],
						[
							'key' => Text::_('COM_EMUNDUS_SOGECOMMERCE_HISTORY_STATUS'),
							'value' => $json_data['vads_trans_status'],
							'classes' => '',
							'display' => 'all'
						],
					];

					return $transaction;
				}, $transactions)
			];
		}

		$this->sendJsonResponse($response);
	}

	public function confirmtransaction()
	{
		$this->checkToken();
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asAccessAction($this->payment_repository->getActionId(), 'u', $this->app->getIdentity()->id)) {
			$transaction_repository = new TransactionRepository();
			$transaction_id = $this->input->getInt('id', 0);
			$transaction = $transaction_repository->getById($transaction_id);
			$transaction->setStatus(TransactionStatus::CONFIRMED);

			try {
				$saved = $transaction_repository->saveTransaction($transaction, $this->app->getIdentity()->id);
			} catch (Exception $e) {
				Log::add('Error while confirm transaction: ' . $e->getMessage(), Log::ERROR, 'com_emundus.payment');
				$saved = false;
			}

			if ($saved) {
				$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_TRANSACTION_CONFIRMED'), 'status' => true];
			} else {
				$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_TRANSACTION_NOT_CONFIRMED'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function canceltransaction()
	{
		$this->checkToken();
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asAccessAction($this->payment_repository->getActionId(), 'u', $this->app->getIdentity()->id)) {
			$transaction_repository = new TransactionRepository();
			$transaction_id = $this->input->getInt('id', 0);
			$transaction = $transaction_repository->getById($transaction_id);
			$transaction->setStatus(TransactionStatus::CANCELLED);

			try {
				$saved = $transaction_repository->saveTransaction($transaction, $this->app->getIdentity()->id);
			} catch (Exception $e) {
				Log::add('Error while cancelling transaction: ' . $e->getMessage(), Log::ERROR, 'com_emundus.payment');
				$saved = false;
			}

			if ($saved) {
				$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_TRANSACTION_CONFIRMED'), 'status' => true];
			} else {
				$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_TRANSACTION_NOT_CONFIRMED'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function editTransaction()
	{
		$this->checkToken();
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$transaction_id = $this->input->getInt('id', 0);

		if (!empty($transaction_id)) {
			$transaction_repository = new TransactionRepository();
			$transaction = $transaction_repository->getById($transaction_id);
			if (!empty($transaction) && EmundusHelperAccess::asAccessAction($this->payment_repository->getActionId(), 'u', $this->app->getIdentity()->id, $transaction->getFnum())) {
				$transaction_reference = $this->input->getString('reference', '');
				$transaction_status = $this->input->getString('status', '');

				try {
					if (!empty($transaction_reference)) {
						$transaction->setExternalReference($transaction_reference);
					} else {
						throw new Exception(Text::_('COM_EMUNDUS_TRANSACTION_REFERENCE_REQUIRED'));
					}

					if (!empty($transaction_status)) {
						$transaction->setStatus(TransactionStatus::from($transaction_status));
					} else {
						throw new Exception(Text::_('COM_EMUNDUS_TRANSACTION_STATUS_REQUIRED'));
					}

					$saved = $transaction_repository->saveTransaction($transaction, $this->app->getIdentity()->id);
				} catch (Exception $e) {
					Log::add('Error while editing transaction: ' . $e->getMessage(), Log::ERROR, 'com_emundus.payment');
					$saved = false;
				}

				if ($saved) {
					$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_TRANSACTION_UPDATED'), 'status' => true];
				} else {
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_TRANSACTION_NOT_UPDATED'), 'status' => false];
				}
			}
		}

		$this->sendJsonResponse($response);
	}
}