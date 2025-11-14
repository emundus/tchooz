<?php
/**
 * Messages controller used for the creation and emission of messages from the platform.
 *
 * @package    Joomla
 * @subpackage Emundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Hugo Moracchini
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Tchooz\Enums\NumericSign\SignConnectorsEnum;
use Tchooz\Enums\NumericSign\SignStatusEnum;
use Tchooz\Repositories\NumericSign\RequestRepository;
use Tchooz\Repositories\NumericSign\RequestSignersRepository;
use Tchooz\Repositories\NumericSign\YousignRequestsRepository;
use Tchooz\Services\NumericSign\YousignService;
use Tchooz\Synchronizers\NumericSign\YousignSynchronizer;
use Tchooz\Traits\TraitDispatcher;
use Tchooz\Traits\TraitResponse;

class EmundusControllerSign extends BaseController
{
	use TraitResponse;

	use TraitDispatcher;

	private ?User $user;

	private DatabaseInterface $db;

	private EmundusModelSign $model;

	private int $sign_action_id = 0;

	public function __construct($config = array(), MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		$this->user = $this->app->getIdentity();

		if (!class_exists('EmundusHelperAccess'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
		}
		if (!class_exists('EmundusModelSign'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/sign.php';
		}
		$this->model          = new EmundusModelSign([], null, $this->user);
		$this->sign_action_id = $this->model->getSignActionId();

		Log::addLogger(['text_file' => 'com_emundus.error.php'], Log::ERROR, array('com_emundus'));
		Log::addLogger(['text_file' => 'com_emundus.sign.php'], Log::ALL, array('com_emundus.sign'));
	}

	public function getrequests(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->sign_action_id, 'r', $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = 'Access denied.';
			$this->sendJsonResponse($response);

			return;
		}

		$order_by  = $this->input->getString('order_by', '');
		$sort      = $this->input->getString('sort', '');
		$recherche = $this->input->getString('recherche', '');
		$lim       = $this->input->getInt('lim', 0);
		$page      = $this->input->getInt('page', 0);

		// Filters
		$attachment  = $this->input->getInt('attachment', 0);
		$applicant   = $this->input->getInt('applicant', 0);
		$status      = $this->input->getString('status', '');
		$signed_date = $this->input->getString('signed_date', '');

		try
		{
			$requests = $this->model->getRequests($order_by, $sort, $recherche, $lim, $page, $status, $attachment, $applicant, $signed_date);
			if (count($requests) > 0)
			{
				// Search for a files or evaluation view
				$menu        = Factory::getApplication()->getMenu();
				$emundusUser = $this->app->getSession()->get('emundusUser');
				$files_menu  = $menu->getItems(['link', 'menutype'], ['index.php?option=com_emundus&view=files', $emundusUser->menutype], 'true');
				if (empty($files_menu))
				{
					$files_menu = $menu->getItems(['link', 'menutype'], ['index.php?option=com_emundus&view=evaluation', $emundusUser->menutype], 'true');
				}

				foreach ($requests['datas'] as $request)
				{
					$applicant_name = !empty($files_menu) ? '<a class="tw-cursor-pointer hover:tw-underline" href="' . $files_menu->route . '#' . $request->fnum . '" target="_blank">' . $request->applicant_name . '</a>' : $request->applicant_name;
					$request->label = ['fr' => $request->attachment_name, 'en' => $request->attachment_name];

					if (!empty($request->signers))
					{

						$tags       = '<div>';
						$short_tags = $tags;
						$tags       .= '<h2 class="tw-mb-8 tw-text-center">' . Text::_('COM_EMUNDUS_ONBOARD_REQUEST_SIGNERS_TITLE') . '</h2>';
						$tags       .= '<div class="tw-flex tw-flex-col tw-gap-4">';

						$signers_signed = 0;
						foreach ($request->signers as $signer)
						{
							$status_tag = SignStatusEnum::from($signer->status)->getHtmlBadge();

							$tags .= '<div class="tw-flex tw-justify-between tw-items-center" title="' . SignStatusEnum::from($request->signers[0]->status)->getLabel() . '"><div><label>' . $signer->lastname . ' ' . $signer->firstname . '</label></br><span style="color: var(--neutral-500);">' . $signer->email . '</span>';
							if (!empty($signer->signed_at))
							{
								$tags .= '</br><span style="color: var(--neutral-500);">' . Text::_('COM_EMUNDUS_ONBOARD_REQUEST_SIGNERS_SIGNED_AT') . ' ' . EmundusHelperDate::displayDate($signer->signed_at, 'd/m/Y', 0) . '</span>';
							}
							elseif($signer->status === SignStatusEnum::REMINDER_SENT->value && !empty($request->last_reminder_at))
							{
								$tags .= '</br><span style="color: var(--neutral-500);">' . Text::_('COM_EMUNDUS_ONBOARD_REQUEST_SIGNERS_REMINDER_AT') . ' ' . EmundusHelperDate::displayDate($request->last_reminder_at, 'd/m/Y', 0) . '</span>';
							}
							$tags .= '</div>' . $status_tag . '</div>';

							if ($signer->status === SignStatusEnum::SIGNED->value)
							{
								$signers_signed++;
							}
						}
						$tags .= '</div>';

						$icon       = $signers_signed === count($request->signers) ? 'check_circle' : 'do_not_disturb_on';
						$bg_color   = $signers_signed === count($request->signers) ? 'tw-bg-main-100' : 'tw-bg-blue-100';
						$text_color = $signers_signed === count($request->signers) ? 'tw-text-main-600' : 'tw-text-blue-600';

						$short_tags .= '<div class="' . $bg_color . ' ' . $text_color . ' tw-rounded-full tw-w-fit tw-cursor-pointer tw-text-profile-full tw-flex tw-items-center tw-justify-center tw-font-semibold tw-px-2 tw-py-1 tw-gap-2"><span class="' . $text_color . ' material-symbols-outlined !tw-text-base">' . $icon . '</span><span class="hover:!tw-underline ' . $text_color . '">' . $signers_signed . '/' . count($request->signers) . '</span></div>';
						$short_tags .= '</div>';
						$tags       .= '</div>';
					}
					else
					{
						$short_tags = Text::_('COM_EMUNDUS_ONBOARD_REQUEST_NO_SIGNER');
					}

					$signers_column = [
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_REQUEST_SIGNERS_TITLE'),
						'value'   => $short_tags,
						'classes' => '',
						'display' => 'all'
					];
					if (isset($tags))
					{
						$signers_column['long_value'] = $tags;
					}

					$state_values = [
						[
							'key'     => Text::_('COM_EMUNDUS_STATE'),
							'value'   => SignStatusEnum::from($request->status)->getLabel(),
							'classes' => 'tw-rounded-status tw-px-3 tw-py-1 tw-font-semibold tw-text-white ' . SignStatusEnum::from($request->status)->getClass(),
						]
					];

					$request->additional_columns = [
						[
							'key'      => Text::_('COM_EMUNDUS_ONBOARD_SIGN_FILTER_APPLICANTS_LABEL'),
							'value'    => $applicant_name,
							'classes'  => '',
							'display'  => 'table',
							'order_by' => 'eu.lastname'
						],
						[
							'key'      => Text::_('COM_EMUNDUS_ONBOARD_APPLICANT_EMAIL'),
							'value'    => $request->applicant_email,
							'classes'  => '',
							'display'  => 'table',
							'order_by' => 'u.email'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_STATE'),
							'type'    => 'tags',
							'values'  => $state_values,
							'display' => 'table'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_CONNECTOR'),
							'value'   => SignConnectorsEnum::from($request->connector)->getLabel(),
							'display' => 'table'
						],
						$signers_column
					];
				}

				$this->dispatchJoomlaEvent('onBeforeDisplaySignRequest', [
					'requests'  => &$requests,
					'order_by'  => $order_by,
					'sort'      => $sort,
					'recherche' => $recherche,
					'lim'       => $lim,
					'page'      => $page
				]);

				$response['code']    = 200;
				$response['message'] = 'Requests loaded successfully.';
				$response['data']    = $requests;
				$response['status']  = true;
			}
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function saverequest(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => 0];

		if (!EmundusHelperAccess::asAccessAction($this->sign_action_id, 'c', $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = 'Access denied.';
			$this->sendJsonResponse($response);

			return;
		}

		$id         = $this->input->getInt('id', 0);
		$status     = $this->input->getString('status', 'to_sign');
		$ccid       = $this->input->getInt('ccid', 0);
		$user_id    = $this->input->getInt('user_id', 0);
		$fnum       = $this->input->getString('fnum', '');
		$attachment = $this->input->getInt('attachment', 0);
		$upload     = $this->input->getInt('upload', 0);
		$connector  = $this->input->getString('connector', 'yousign');
		$signers    = $this->input->getString('signers');
		if (!empty($signers))
		{
			$signers = json_decode($signers, true);
		}

		if (empty($status) || empty($attachment) || (empty($ccid) && empty($user_id)))
		{
			$response['code']    = 400;
			$response['message'] = 'Missing required fields.';
			$this->sendJsonResponse($response);

			return;
		}

		try
		{
			if ($request_id = $this->model->saveRequest($id, $status, $ccid, $user_id, $fnum, $attachment, $connector, $signers, $upload))
			{
				$response['code']    = 200;
				$response['status']  = true;
				$response['message'] = 'Request saved successfully.';
				$response['data']    = $request_id;

				$this->dispatchJoomlaEvent('onAfterRequestSaved', [
					'request_id' => $request_id,
					'status'     => $status,
					'ccid'       => $ccid,
					'user_id'    => $user_id,
					'fnum'       => $fnum,
					'attachment' => $attachment,
					'connector'  => $connector,
					'signers'    => $signers
				]);
			}
			else
			{
				$response['code']    = 500;
				$response['message'] = 'Failed to save request.';
			}
		}
		catch (\Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function addsigner(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => 0];

		if (!EmundusHelperAccess::asAccessAction($this->sign_action_id, 'u', $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = 'Access denied.';
			$this->sendJsonResponse($response);

			return;
		}

		$request_id = $this->input->getInt('request_id', 0);
		$email      = $this->input->getString('email', '');
		$firstname  = $this->input->getString('firstname', '');
		$lastname   = $this->input->getString('lastname', '');
		$status     = $this->input->getString('status', '');

		if (empty($request_id) || empty($email) || empty($firstname) || empty($lastname))
		{
			$response['code']    = 400;
			$response['message'] = 'Missing required fields.';
			$this->sendJsonResponse($response);

			return;
		}

		try
		{
			if ($signer_id = $this->model->addSigner($request_id, $email, $firstname, $lastname, $status))
			{
				$response['code']    = 200;
				$response['status']  = true;
				$response['message'] = 'Signer added successfully.';
				$response['data']    = $signer_id;

				$this->dispatchJoomlaEvent('onAfterAddSignerToRequest', [
					'request_id' => $request_id,
					'signer_id'  => $signer_id,
					'email'      => $email,
					'firstname'  => $firstname,
					'lastname'   => $lastname,
					'status'     => $status
				]);
			}
			else
			{
				$response['code']    = 500;
				$response['message'] = 'Failed to add signer.';
			}
		}
		catch (\Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function cancelrequest(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => 0];

		if (!EmundusHelperAccess::asAccessAction($this->sign_action_id, 'd', $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = 'Access denied.';
			$this->sendJsonResponse($response);

			return;
		}

		$cancel_reason = $this->input->getString('input', '');
		$requests_ids  = $this->input->getString('ids', '');
		if (empty($requests_ids))
		{
			$request_id = $this->input->getInt('id', 0);
			if (!empty($request_id))
			{
				$requests_ids = [$request_id];
			}
		}
		else
		{
			$requests_ids = explode(',', $requests_ids);
		}

		foreach ($requests_ids as $request_id)
		{
			if (empty($request_id))
			{
				$response['code']    = 400;
				$response['message'] = 'Missing required fields.';
				$this->sendJsonResponse($response);

				return;
			}

			try
			{
				if ($this->model->cancelRequest($request_id, $cancel_reason))
				{
					$response['code']    = 200;
					$response['status']  = true;
					$response['message'] = 'Request cancelled successfully.';
					$response['data']    = $request_id;

					$this->dispatchJoomlaEvent('onAfterRequestCancelled', [
						'request_id'    => $request_id,
						'cancel_reason' => $cancel_reason
					]);
				}
				else
				{
					$response['code']    = 500;
					$response['message'] = 'Failed to cancel request.';
				}
			}
			catch (\Exception $e)
			{
				$response['code']    = $e->getCode();
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}

	public function sendreminder(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => 0];

		if (!EmundusHelperAccess::asAccessAction($this->sign_action_id, 'u', $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = 'Access denied.';
			$this->sendJsonResponse($response);

			return;
		}

		$requests_ids = $this->input->getString('ids', '');
		if (empty($requests_ids))
		{
			$request_id = $this->input->getInt('id', 0);
			if (!empty($request_id))
			{
				$requests_ids = [$request_id];
			}
		}
		else
		{
			$requests_ids = explode(',', $requests_ids);
		}

		foreach ($requests_ids as $request_id)
		{
			if (empty($request_id))
			{
				$response['code']    = 400;
				$response['message'] = 'Missing required fields.';
				$this->sendJsonResponse($response);

				return;
			}

			try
			{
				if ($this->model->sendReminder($request_id))
				{
					$response['code']    = 200;
					$response['status']  = true;
					$response['message'] = 'Reminder sent successfully.';
					$response['data']    = $request_id;

					$this->dispatchJoomlaEvent('onAfterRequestReminderSent', [
						'request_id' => $request_id
					]);
				}
				else
				{
					throw new \Exception(Text::_('COM_EMUNDUS_SIGN_ERROR_TO_SEND'), 500);
				}
			}
			catch (\Exception $e)
			{
				$response['code']    = $e->getCode();
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}

	public function downloadsigneddocument(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => 0];

		$request_id = $this->input->getString('id', '');
		if (empty($request_id))
		{
			$response['code']    = 400;
			$response['message'] = 'Missing required fields.';
			$this->sendJsonResponse($response);

			return;
		}

		if (!EmundusHelperAccess::asAccessAction($this->sign_action_id, 'r', $this->user->id) && !EmundusHelperAccess::isRequestMine($request_id, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = 'Access denied.';
			$this->sendJsonResponse($response);

			return;
		}

		try
		{
			if ($signed_file = $this->model->getSignedDocument($request_id))
			{
				$response['code']          = 200;
				$response['status']        = true;
				$response['message']       = 'Documents downloaded successfully.';
				$response['download_file'] = $signed_file;

				$this->dispatchJoomlaEvent('onAfterDocumentsDownloaded', [
					'request_id' => $request_id
				]);
			}
			else
			{
				throw new \Exception(Text::_('COM_EMUNDUS_SIGN_ERROR_TO_DOWNLOAD'), 500);
			}
		}
		catch (\Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function yousigncallback(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => 0];

		try
		{
			$expectedSignature = $_SERVER['HTTP_X_YOUSIGN_SIGNATURE_256'] ?? null;

			if (!class_exists('EmundusModelSync'))
			{
				require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
			}
			$m_sync      = new EmundusModelSync();
			$yousign_api = $m_sync->getApi(0, 'yousign');
			if (!empty($yousign_api->config))
			{
				$yousign_config = json_decode($yousign_api->config);
				$secret     = $yousign_config->secret_key ?? null;

				if (!empty($secret))
				{
					if(!class_exists('EmundusHelperFabrik'))
					{
						require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
					}
					$secret = EmundusHelperFabrik::decryptDatas($secret);

					$payload           = file_get_contents('php://input');
					$digest            = hash_hmac('sha256', $payload, $secret);
					$computedSignature = "sha256=" . $digest;

					$doSignaturesMatch = hash_equals($expectedSignature, $computedSignature);

					if ($doSignaturesMatch)
					{
						$db                         = Factory::getContainer()->get('DatabaseDriver');
						$yousign_repository         = new YousignRequestsRepository($db);
						$request_signers_repository = new RequestSignersRepository($db);
						$request_repository         = new RequestRepository($db);
						if (!class_exists('EmundusModelFiles'))
						{
							require_once JPATH_SITE . '/components/com_emundus/models/files.php';
						}
						$m_files                    = new EmundusModelFiles();
						if (!class_exists('EmundusModelApplication'))
						{
							require_once JPATH_SITE . '/components/com_emundus/models/application.php';
						}
						$m_application              = new EmundusModelApplication();

						$automated_user_id = ComponentHelper::getParams('com_emundus')->get('automated_task_user', 1);
						$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($automated_user_id);

						$yousign_service = new YousignService(
							new YousignSynchronizer(),
							$yousign_repository,
							$request_repository,
							$request_signers_repository,
							$m_files,
							$m_application,
							$user
						);

						$data = json_decode($payload, true);

						if (!empty($data) && !empty($data['data']['signature_request']))
						{
							// Get yousign request
							switch ($data['event_name'])
							{
								case 'signer.done':
									$yousign_signer = $yousign_repository->loadYousignRequestSignerBySignerId($data['data']['signer']['id']);

									if (!empty($yousign_signer))
									{
										$sign_status = SignStatusEnum::from($data['data']['signer']['status']);
										if ($sign_status instanceof SignStatusEnum)
										{
											if ($request_signers_repository->updateStatus($yousign_signer->request_signer_id, $sign_status->value))
											{
												$request_signers_repository->updateSignedAt($yousign_signer->request_signer_id, date('Y-m-d H:i:s', $data['event_time']));
											}
										}
									}
									break;
								case 'signature_request.done':
									$yousign_request = $yousign_repository->loadYousignRequestByProcedureId($data['data']['signature_request']['id']);
									if ($data['data']['signature_request']['status'] === 'done')
									{
										$application_file = $m_files->getFnumInfos($yousign_request->getRequest()->getFnum());

										$yousign_service->downloadDocuments($yousign_request, $yousign_request->getRequest(), $application_file);
									}
									break;
							}
						}

						$response['code']          = 200;
						$response['status']        = true;
						$response['message']       = 'Yousign callback processed successfully.';
					}
					else
					{
						$response['code']    = 400;
						$response['message'] = 'Invalid signature.';
						Log::add('Invalid signature: ' . $expectedSignature . ' != ' . $computedSignature, Log::ERROR, 'com_emundus.sign');
					}
				}
				else
				{
					$response['code']    = 400;
					$response['message'] = 'Missing Yousign API secret key.';
					Log::add('Missing Yousign API secret key.', Log::ERROR, 'com_emundus.sign');
				}
			}
			else
			{
				$response['code']    = 400;
				$response['message'] = 'Missing Yousign API configuration.';
				Log::add('Missing Yousign API configuration.', Log::ERROR, 'com_emundus.sign');
			}
		}
		catch (\Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function getapplicants(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->sign_action_id, 'r', $this->user->id))
		{
			$search_query = $this->input->getString('search_query', '');
			$limit        = $this->input->getInt('limit', 100);
			$properties   = $this->input->getString('properties', '');

			if (!empty($properties))
			{
				$properties = explode(',', $properties);
			}

			try
			{
				$applicants = $this->model->getApplicants($search_query);

				$response['code']    = 200;
				$response['status']  = true;
				$response['message'] = Text::_('APPLICANTS_FOUND');
				$response['data']    = $applicants;
			}
			catch (Exception $e)
			{
				$response['code']    = $e->getCode();
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getfilterapplicants(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->sign_action_id, 'r', $this->user->id))
		{
			try
			{
				$applicants = $this->model->getFilterApplicants();

				$response['code']    = 200;
				$response['status']  = true;
				$response['message'] = Text::_('APPLICANTS_FOUND');
				$response['data']    = $applicants;
			}
			catch (Exception $e)
			{
				$response['code']    = $e->getCode();
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getattachmentstypes(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->sign_action_id, 'r', $this->user->id))
		{
			$search_query = $this->input->getString('search_query', '');
			$limit        = $this->input->getInt('limit', 100);
			$properties   = $this->input->getString('properties', '');

			if (!empty($properties))
			{
				$properties = explode(',', $properties);
			}

			try
			{
				$attachments = $this->model->getAttachmentsTypes($search_query);

				$response['status']  = true;
				$response['code']    = 200;
				$response['message'] = Text::_('ATTACHMENTS_FOUND');
				$response['data']    = $attachments;
			}
			catch (Exception $e)
			{
				$response['code']    = $e->getCode();
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getfilterattachments(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->sign_action_id, 'r', $this->user->id))
		{
			try
			{
				$attachments = $this->model->getFilterAttachments();

				$response['code']    = 200;
				$response['status']  = true;
				$response['message'] = Text::_('ATTACHMENTS_FOUND');
				$response['data']    = $attachments;
			}
			catch (Exception $e)
			{
				$response['code']    = $e->getCode();
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getconnectors(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->sign_action_id, 'r', $this->user->id))
		{
			$search_query = $this->input->getString('search_query', '');
			$limit        = $this->input->getInt('limit', 100);
			$properties   = $this->input->getString('properties', '');

			if (!empty($properties))
			{
				$properties = explode(',', $properties);
			}

			try
			{
				$connectors = $this->model->getConnectors($search_query);

				$response['status']  = true;
				$response['code']    = 200;
				$response['message'] = Text::_('CONNECTORS_FOUND');
				$response['data']    = $connectors;
			}
			catch (Exception $e)
			{
				$response['code']    = $e->getCode();
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getcontacts(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->sign_action_id, 'r', $this->user->id))
		{
			$search_query = $this->input->getString('search_query', '');
			$limit        = $this->input->getInt('limit', 100);
			$properties   = $this->input->getString('properties', '');

			if (!empty($properties))
			{
				$properties = explode(',', $properties);
			}

			try
			{
				$contacts = $this->model->getContacts($search_query);

				$response['status']  = true;
				$response['code']    = 200;
				$response['message'] = Text::_('CONTACTS_FOUND');
				$response['data']    = $contacts;
			}
			catch (Exception $e)
			{
				$response['code']    = $e->getCode();
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getuploads(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->sign_action_id, 'r', $this->user->id))
		{
			$search_query = $this->input->getString('search_query', '');
			$limit        = $this->input->getInt('limit', 100);
			$properties   = $this->input->getString('properties', '');

			if (!empty($properties))
			{
				$properties    = explode(',', $properties);
				$ccid          = $properties[0];
				$attachment_id = $properties[1];
			}

			if (empty($ccid) || empty($attachment_id))
			{
				$response['code']    = 400;
				$response['message'] = 'Missing required fields.';
				$this->sendJsonResponse($response);

				return;
			}

			if (!class_exists('EmundusHelperFiles'))
			{
				require_once JPATH_SITE . '/components/com_emundus/helpers/files.php';
			}

			$fnum = EmundusHelperFiles::getFnumFromId($ccid);
			if (empty($fnum) || !EmundusHelperAccess::asAccessAction($this->sign_action_id, 'r', $this->user->id, $fnum))
			{
				$response['code']    = 403;
				$response['message'] = 'Access denied.';
				$this->sendJsonResponse($response);

				return;
			}

			try
			{
				$uploads = $this->model->getUploads($search_query, $fnum, $attachment_id);

				$response['status']  = true;
				$response['code']    = 200;
				$response['message'] = Text::_('UPLOADS_FOUND');
				$response['data']    = $uploads;
			}
			catch (Exception $e)
			{
				$response['code']    = $e->getCode();
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getfilterstatus(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->sign_action_id, 'r', $this->user->id))
		{
			try
			{
				$statuses = $this->model->getFilterStatus();

				$response['code']    = 200;
				$response['status']  = true;
				$response['message'] = Text::_('STATUS_FOUND');
				$response['data']    = $statuses;
			}
			catch (Exception $e)
			{
				$response['code']    = $e->getCode();
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}
}
