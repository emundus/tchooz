<?php
/**
 * @package     models
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\User\User;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\NumericSign\Request;
use Tchooz\Entities\NumericSign\RequestSigners;
use Tchooz\Enums\NumericSign\SignAuthenticationLevel;
use Tchooz\Enums\NumericSign\SignStatus;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\NumericSign\RequestRepository;
use Tchooz\Repositories\NumericSign\RequestSignersRepository;

if(!class_exists('EmundusHelperCache'))
{
	require_once JPATH_SITE . '/components/com_emundus/helpers/cache.php';
}

jimport('joomla.application.component.model');

class EmundusModelSign extends ListModel
{
	private ?User $user = null;

	private \EmundusHelperCache $h_cache;

	function __construct($config = [], ?MVCFactoryInterface $factory = null, ?User $user = null)
	{
		parent::__construct();

		$this->app  = Factory::getApplication();
		$this->db   = $this->getDatabase();
		if(empty($user)){
			$this->user = $this->app->getIdentity();
		}
		else {
			$this->user = $user;
		}
		$this->h_cache = new \EmundusHelperCache();

		if(!class_exists('EmundusHelperFiles'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/files.php';
		}

		Log::addLogger(['text_file' => 'com_emundus.error.php'], Log::ERROR, array('com_emundus'));
		Log::addLogger(['text_file' => 'com_emundus.sign.php'], Log::ALL, array('com_emundus.sign'));
	}

	public function getRequests(string $order_by = '', string $sort = 'DESC', string $search = '', int|string $lim = 25, int|string $page = 0, ?string $status = '', ?int $attachment = 0, ?int $applicant = 0, ?string $signed_date = ''): array
	{
		$requests = ['datas' => [], 'count' => 0];

		try
		{
			if (empty($lim) || $lim == 'all')
			{
				$limit = '';
			}
			else
			{
				$limit = $lim;
			}

			if (empty($page) || empty($limit))
			{
				$offset = 0;
			}
			else
			{
				$offset = ($page - 1) * $limit;
			}

			$requestsRepository = new RequestRepository($this->db);
			$requestsRepository->setFilters($search, $status, $attachment, $applicant, $signed_date);

			$requests['count'] = $requestsRepository->getCountRequests();
			$requests['datas'] = $requestsRepository->loadRequests($order_by, $sort, $limit, $offset);
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}

		return $requests;
	}

	public function saveRequest(int $id, string $status, int $ccid, int  $userId, string $fnum, int $attachment, string $connector, array $signers, int $upload = 0, int $current_user_id = 0, bool $ordered = false): int
	{
		$query = $this->db->getQuery(true);
		if(empty($current_user_id))
		{
			$current_user_id = $this->user->id;
		}

		try
		{
			$requestRepository = new RequestRepository($this->db);
			if(!empty($id))
			{
				$requestEntity = $requestRepository->loadRequestById($id);
			}
			else {
				$requestEntity = new Request($current_user_id);
			}

			if(!empty($status))
			{
				$requestEntity->setStatus($status);
			}
			if(!empty($ccid))
			{
				$requestEntity->setCcid($ccid);
			}
			if (!empty($userId))
			{
				$requestEntity->setUserId($userId);
			}
			if(!empty($fnum))
			{
				$requestEntity->setFnum($fnum);
			}
			elseif(!empty($ccid)) {
				$fnum = EmundusHelperFiles::getFnumFromId($ccid);
			}
			if(!empty($attachment)) {
				$attachmentTypeRepository = new AttachmentTypeRepository($this->db);
				$attachmentTypeEntity = $attachmentTypeRepository->loadAttachmentTypeById($attachment);

				$requestEntity->setAttachment($attachmentTypeEntity);
			}
			else {
				throw new \Exception('Attachment ID is required.', 400);
			}
			if(!empty($connector))
			{
				$requestEntity->setConnector($connector);
			}
			else {
				throw new \Exception('Connector is required.', 400);
			}
			if(empty($upload))
			{
				$query->select('id')
					->from($this->db->quoteName('#__emundus_uploads','eu'))
					->where($this->db->quoteName('eu.fnum').' = ' . $this->db->quote($fnum))
					->where($this->db->quoteName('eu.attachment_id').' = ' . $this->db->quote($attachment))
					->order('eu.timedate DESC');
				$this->db->setQuery($query);
				$upload = $this->db->loadResult();
			}

			if(empty($upload))
			{
				throw new \Exception('Upload not found.', 400);
			}

			$requestEntity->setUploadId($upload);
			$requestEntity->setOrdered($ordered);

			if($request_id = $requestRepository->flush($requestEntity))
			{
				if(!empty($signers))
				{
					$contactRepository = new ContactRepository();
					foreach ($signers as $signer)
					{
						if(is_array($signer))
						{
							$signer_id = (int) $signer['signer'];
							if (!empty($signer_id))
							{
								$contact = $contactRepository->getById($signer_id);
							}
						}
						elseif(is_string($signer)) {
							$contact = $contactRepository->getByEmail($signer);
						}

						if(!empty($contact))
						{
							$this->addSigner($request_id, $contact->getEmail(), $contact->getFirstname(), $contact->getLastname(), 'to_sign', 1, $signer['page'] ?? 0, $signer['position'] ?? '', $signer['authentication_level'] ?? SignAuthenticationLevel::STANDARD->value);
						}
					}
				}
			}

			if(!empty($request_id))
			{
				EmundusModelLogs::log($current_user_id, EmundusHelperFiles::getApplicantIdFromFileId($ccid), $fnum, $this->getSignActionId(), 'c', 'COM_EMUNDUS_SIGN_REQUEST_CREATED', json_encode(['created' => ['filename' => $requestEntity->getAttachment()->getName()]]));
			}

			return $request_id;
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}
	}

	public function addSigner(int $request_id, string $email, string $firstname, string $lastname, ?string $status = 'to_sign', ?int $step = 1, ?int $page = 0, ?string $position = '', ?string $authentication_level = SignAuthenticationLevel::STANDARD->value): int
	{
		try
		{
			if(!empty($email))
			{
				$contactRepository = new ContactRepository();
				$contact           = $contactRepository->getByEmail($email);
				if (empty($contact))
				{
					$contact = new ContactEntity($email, $lastname, $firstname, '');
					$contactRepository->flush($contact);
				}

				$requestRepository = new RequestRepository($this->db);
				$request = $requestRepository->loadRequestById($request_id);

				$signerRepository = new RequestSignersRepository($this->db);
				$signer = $signerRepository->loadSignerByRequestAndContact($request, $contact);

				if(empty($signer))
				{
					$signer = new RequestSigners($request, $contact, $status);
				}

				if(!empty($status))
				{
					$signer->setStatus($status);
				}

				if(!empty($step))
				{
					$signer->setStep($step);
				}

				if(!empty($page))
				{
					$signer->setPage($page);
				}

				if(!empty($position))
				{
					$signer->setPosition($position);
				}

				if(!empty($authentication_level))
				{
					$signer->setAuthenticationLevel($authentication_level);
				}

				return $signerRepository->flush($signer);
			}
			else {
				throw new \Exception('Email is required.', 400);
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}
	}

	public function cancelRequest(int $request_id, ?string $cancel_reason = ''): bool
	{
		try
		{
			if(empty($request_id))
			{
				throw new \Exception('Request ID is required.', 400);
			}

			$request_repository = new RequestRepository($this->db);
			$cancel = $request_repository->updateStatus($request_id, 'cancelled') && $request_repository->cancelAt($request_id, date('Y-m-d H:i:s'));
			if(!empty($cancel_reason))
			{
				$request_repository->updateCancelReason($request_id, $cancel_reason);
			}

			$signers = $request_repository->loadRequestSigners($request_id);
			if(!empty($signers))
			{
				$request_signers_repository = new RequestSignersRepository($this->db);
				foreach ($signers as $signer)
				{
					$cancel = $request_signers_repository->updateStatus($signer->id, SignStatus::CANCELLED);
				}
			}

			if($cancel)
			{
				$requestEntity = $request_repository->loadRequestById($request_id);
				EmundusModelLogs::log($this->user->id, EmundusHelperFiles::getApplicantIdFromFileId($requestEntity->getCcid()), $requestEntity->getFnum(), $this->getSignActionId(), 'd', 'COM_EMUNDUS_SIGN_REQUEST_CANCELLED');
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}

		return $cancel;
	}

	public function sendReminder(int $request_id): bool
	{
		try
		{
			if(empty($request_id))
			{
				throw new \Exception('Request ID is required.', 400);
			}

			$request_repository = new RequestRepository($this->db);

			return $request_repository->updateSendReminder($request_id, 1);
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}
	}

	public function getSignedDocument(int $request_id): ?string
	{
		try
		{
			if(empty($request_id))
			{
				throw new \Exception('Request ID is required.', 400);
			}

			$request_repository = new RequestRepository($this->db);
			$request = $request_repository->loadRequestById($request_id);

			if(empty($request))
			{
				throw new \Exception('Request not found.', 404);
			}

			$signed_file = $request_repository->getSignedDocument($request);
			if(!empty($signed_file))
			{
				$query = $this->db->createQuery();
				$query->select('applicant_id')
					->from($this->db->quoteName('#__emundus_campaign_candidature'))
					->where($this->db->quoteName('id').' = ' . $this->db->quote($request->getCcid()));
				$this->db->setQuery($query);
				$applicant_id = $this->db->loadResult();

				return EMUNDUS_PATH_REL.$applicant_id.'/'.$signed_file;
			}
			else {
				throw new \Exception(Text::_('COM_EMUNDUS_SIGNED_ATTACHMENT_NOT_FOUND'), 404);
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}
	}

	public function getSignActionId(): int
	{
		$action_id = (int)$this->h_cache->get('sign_action_id');

		if(empty($action_id))
		{
			$query = $this->db->getQuery(true);

			$query->select('id')
				->from('#__emundus_setup_actions')
				->where('name LIKE ' . $this->db->quote('sign_request'));

			try
			{
				$this->db->setQuery($query);
				$action_id = (int) $this->db->loadResult();
			}
			catch (\Exception $e)
			{
				Log::add('Error on get sign action id : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sign');
			}
		}

		return $action_id;
	}

	public function getApplicants($search_query, int $user_id = 0): array
	{
		$sign_action_id = $this->getSignActionId();
		if(empty($user_id))
		{
			$user_id = $this->user->id;
		}

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('ecc.id as value, ecc.fnum, CONCAT(eu.firstname, " ", eu.lastname, " - ", esc.label, " (", esc.year,")") as name')
				->from($this->db->quoteName('#__emundus_campaign_candidature','ecc'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns','esc').' ON '.$this->db->quoteName('esc.id').' = '.$this->db->quoteName('ecc.campaign_id'))
				->leftJoin($this->db->quoteName('#__emundus_users','eu').' ON '.$this->db->quoteName('eu.user_id').' = '.$this->db->quoteName('ecc.applicant_id'))
				->where($this->db->quoteName('ecc.published').' = 1');
			if(!empty($search_query))
			{
				$query->where('CONCAT(eu.firstname, " ", eu.lastname) LIKE ' . $this->db->quote('%' . $search_query . '%'));
			}
			$this->db->setQuery($query);
			$applicants = $this->db->loadObjectList();

			// Check sign access
			foreach ($applicants as $key => $applicant)
			{
				if(!EmundusHelperAccess::asAccessAction($sign_action_id, 'c', $user_id, $applicant->fnum))
				{
					unset($applicants[$key]);
				}
			}

			return array_values($applicants);
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}
	}

	public function getFilterApplicants(): array
	{
		try
		{
			$query = $this->db->getQuery(true);

			$query->select([$this->db->quoteName('esr.ccid', 'value'), 'CONCAT(eu.lastname," ",eu.firstname) as label'])
				->from($this->db->quoteName('#__emundus_sign_requests','esr'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature','ecc').' ON '.$this->db->quoteName('ecc.id').' = '.$this->db->quoteName('esr.ccid'))
				->leftJoin($this->db->quoteName('#__emundus_users','eu').' ON '.$this->db->quoteName('eu.user_id').' = '.$this->db->quoteName('ecc.applicant_id'))
				->where($this->db->quoteName('ecc.published').' = 1')
				->group('ecc.id')
				->order('eu.lastname ASC');
			$this->db->setQuery($query);
			return $this->db->loadObjectList();
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}
	}

	public function getAttachmentsTypes($search_query): array
	{
		try
		{
			$query = $this->db->getQuery(true);

			$query->select('id as value,value as name')
				->from($this->db->quoteName('#__emundus_setup_attachments'))
				->where($this->db->quoteName('published').' = 1')
				->where($this->db->quoteName('allowed_types').' LIKE ' . $this->db->quote('%pdf%'));
			if(!empty($search_query))
			{
				$query->where($this->db->quoteName('value').' LIKE ' . $this->db->quote('%' . $search_query . '%'));
			}
			$this->db->setQuery($query);
			return $this->db->loadObjectList();
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}
	}

	public function getFilterAttachments(): array
	{
		try
		{
			$query = $this->db->getQuery(true);

			$query->select([$this->db->quoteName('esr.attachment_id', 'value'), 'value as label'])
				->from($this->db->quoteName('#__emundus_sign_requests','esr'))
				->leftJoin($this->db->quoteName('#__emundus_setup_attachments','esa').' ON '.$this->db->quoteName('esa.id').' = '.$this->db->quoteName('esr.attachment_id'))
				->where($this->db->quoteName('esa.published').' = 1')
				->group('esr.attachment_id')
				->order('esa.value ASC');
			$this->db->setQuery($query);
			return $this->db->loadObjectList();
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}
	}

	public function getConnectors($search_query): array
	{
		try
		{
			$query = $this->db->getQuery(true);

			$query->select('type as value,name')
				->from($this->db->quoteName('#__emundus_setup_sync'))
				->where($this->db->quoteName('published').' = 1')
				->where($this->db->quoteName('enabled').' = 1')
				->where($this->db->quoteName('context').' = ' . $this->db->quote('numeric_sign'));
			if(!empty($search_query))
			{
				$query->where('name LIKE ' . $this->db->quote('%' . $search_query . '%'));
			}
			$this->db->setQuery($query);
			return $this->db->loadObjectList();
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}
	}

	public function getContacts($search_query): array
	{
		try
		{
			$query = $this->db->getQuery(true);

			$query->select('id as value, CONCAT(firstname, " ", lastname, " - ", email) as name')
				->from($this->db->quoteName('#__emundus_contacts'));
			if(!empty($search_query))
			{
				$query->where('lastname LIKE ' . $this->db->quote('%' . $search_query . '%'))
					->orWhere('firstname LIKE ' . $this->db->quote('%' . $search_query . '%'))
					->orWhere('email LIKE ' . $this->db->quote('%' . $search_query . '%'));
			}
			$this->db->setQuery($query);
			return $this->db->loadObjectList();
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}
	}

	public function getUploads($search_query, string $fnum, int $attachment_id): array
	{
		$uploads = [];
		try
		{
			if(empty($fnum) || empty($attachment_id))
			{
				throw new \Exception('FNUM and Attachment ID are required.', 400);
			}

			$query = $this->db->getQuery(true);

			$query->select('id as value, eu.filename as name, eu.timedate')
				->from($this->db->quoteName('#__emundus_uploads','eu'))
				->where($this->db->quoteName('eu.fnum').' = ' . $this->db->quote($fnum))
				->where($this->db->quoteName('eu.attachment_id').' = ' . $this->db->quote($attachment_id));
			$this->db->setQuery($query);
			$uploads = $this->db->loadObjectList();

			foreach ($uploads as $upload)
			{
				$upload->name = $upload->name . ' - ' . EmundusHelperDate::displayDate($upload->timedate, 'DATE_FORMAT_LC2', 0);
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}

		return $uploads;
	}

	public function getFilterStatus(): array
	{
		try
		{
			$query = $this->db->getQuery(true);

			$query->select([$this->db->quoteName('esr.status', 'value'), 'status as label'])
				->from($this->db->quoteName('#__emundus_sign_requests','esr'))
				->group('esr.status')
				->order('esr.status ASC');
			$this->db->setQuery($query);
			$status = $this->db->loadObjectList();

			foreach ($status as $statu)
			{
				$statu->label = SignStatus::from($statu->label)->getLabel();
			}

			return $status;
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
			throw $e;
		}
	}
}