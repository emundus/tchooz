<?php
/**
 * @package     Tchooz\Repositories\NumericSign
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\NumericSign;

use Joomla\CMS\Factory;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\ExternalReference\ExternalReferenceEntity;
use Tchooz\Entities\NumericSign\Request;
use Tchooz\Enums\NumericSign\SignConnectorsEnum;
use Tchooz\Enums\NumericSign\SignStatusEnum;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\ExternalReference\ExternalReferenceRepository;
use Tchooz\Traits\TraitTable;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

#[TableAttribute(table: '#__emundus_sign_requests')]
class RequestRepository
{
	use TraitTable;

	private array $filters = [];

	public function __construct(private ?DatabaseInterface $db = null)
	{
		if ($db === null)
		{
			$this->db = Factory::getContainer()->get(DatabaseInterface::class);
		}
	}

	public function flush(Request $sign_request): int
	{
		$request_object = $sign_request->__serialize();
		unset($request_object['external_reference']);
		$request_object = (object) $request_object;

		if (empty($request_object->id))
		{
			if (empty($sign_request->getAttachment()))
			{
				throw new \Exception('Request attachment not set.', 400);
			}

			if (empty($sign_request->getCcid()) && empty($sign_request->getUserId()))
			{
				throw new \Exception('Request user or ccid not set.', 400);
			}

			if (!empty($sign_request->getCcid()) && empty($sign_request->getFnum()))
			{
				if (!class_exists('EmundusHelperFiles'))
				{
					require_once JPATH_SITE . '/components/com_emundus/helpers/files.php';
				}
				$request_object->fnum = \EmundusHelperFiles::getFnumFromId($sign_request->getCcid());
			}

			if ($this->db->insertObject($this->getTableName(self::class), $request_object))
			{
				$sign_request->setId($this->db->insertid());
			}
			else
			{
				throw new \Exception('Failed to insert request.', 500);
			}
		}
		else
		{
			if (!$this->db->updateObject($this->getTableName(self::class), $request_object, 'id'))
			{
				throw new \Exception('Failed to update request.', 500);
			}
		}

		if (!empty($sign_request->getExternalReference()))
		{
			$external_reference_repository = new ExternalReferenceRepository();
			$external_reference_repository->flush(new ExternalReferenceEntity(
				0,
				$this->getTableName(self::class) . '.id',
				$sign_request->getId(),
				$sign_request->getExternalReference()
			));
		}

		return $sign_request->getId();
	}

	public function loadRequestById(int $id): ?Request
	{
		$query = $this->db->createQuery();

		try
		{
			$query->select('esr.*, eer.reference')
				->from($this->db->quoteName($this->getTableName(self::class), 'esr'))
				->leftJoin($this->db->quoteName('#__emundus_external_reference', 'eer') . ' ON ' . $this->db->quoteName('eer.intern_id') . ' = ' . $this->db->quoteName('esr.id') . ' AND ' . $this->db->quoteName('eer.column') . ' = ' . $this->db->quote($this->getTableName(self::class) . '.id'))
				->where($this->db->quoteName('esr.id') . ' = :id')
				->bind(':id', $id, ParameterType::INTEGER);
			$this->db->setQuery($query);
			$request_db = $this->db->loadAssoc();

			if (!empty($request_db))
			{
				$request = new Request($request_db['created_by']);
				$request->setId($request_db['id']);
				$request->setConnector(SignConnectorsEnum::from($request_db['connector']));
				$attachmentTypeRepository = new AttachmentTypeRepository();
				$request->setAttachment($attachmentTypeRepository->loadAttachmentTypeById($request_db['attachment_id']));
				if (!empty($request_db['upload_id']))
				{
					$request->setUploadId($request_db['upload_id']);
				}
				if (!empty($request_db['signed_upload_id']))
				{
					$request->setSignedUploadId($request_db['signed_upload_id']);
				}
				$request->setStatus($request_db['status']);
				$request->setStepsCount($request_db['steps_count']);
				$request->setUserId($request_db['user_id']);
				$request->setCcid($request_db['ccid']);
				$request->setFnum($request_db['fnum']);
				$request->setCreatedAt($request_db['created_at']);
				if(!empty($request_db['cancel_at']))
				{
					$request->setCancelAt($request_db['cancel_at']);
				}
				$request->setSigners($this->loadRequestSigners($request_db['id']));
				$request->setSendReminder($request_db['send_reminder']);
				if(!empty($request_db['last_reminder_at']))
				{
					$request->setLastReminderAt($request_db['last_reminder_at']);
				}
				$request->setSubject($request_db['subject'] ?? '');
				$ordered = $request_db['ordered'] == 1;
				$request->setOrdered($ordered);
				$request->setExternalReference($request_db['reference'] ?? null);

				return $request;
			}
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to load request ' . $id . ' : ' . $e->getMessage(), $e->getCode());
		}

		return null;
	}

	public function getCountRequests(): int
	{
		try
		{
			$query = $this->buildQuery(['COUNT(DISTINCT esr.id)']);
			$this->db->setQuery($query);

			return (int) $this->db->loadResult();
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to get count requests : ' . $e->getMessage(), $e->getCode());
		}
	}

	public function loadRequests(string $order_by = '', string $sort = 'DESC', int|string $limit = 25, int|string $offset = 0): array
	{
		try
		{
			$columns = [
				'esr.id',
				'CONCAT(eu.lastname," ",eu.firstname) as applicant_name',
				$this->db->quoteName('u.email', 'applicant_email'),
				$this->db->quoteName('esa.value', 'attachment_name'),
				$this->db->quoteName('esr.status'),
				$this->db->quoteName('esr.connector'),
				$this->db->quoteName('esr.fnum'),
				$this->db->quoteName('esr.last_reminder_at'),
				$this->db->quoteName('esr.cancel_reason'),
				$this->db->quoteName('esr.cancel_at'),
			];

			$query = $this->buildQuery($columns);

			if (!empty($order_by) && !empty($sort))
			{
				$query->order($order_by . ' ' . $sort);
			}
			else
			{
				$query->order('esr.created_at DESC');
			}
			$query->group('esr.id');
			$this->db->setQuery($query, $offset, $limit);
			$requests = $this->db->loadObjectList();

			foreach ($requests as $request)
			{
				$request->signers = $this->loadRequestSigners($request->id);
			}

			return $requests;
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to load requests : ' . $e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param   int  $request_id
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function loadRequestSigners(int $request_id): array
	{
		try
		{
			$query = $this->db->createQuery();

			$query->select([
				$this->db->quoteName('esrs.id'),
				$this->db->quoteName('esrs.status'),
				$this->db->quoteName('esrs.signed_at'),
				$this->db->quoteName('esrs.step'),
				$this->db->quoteName('esrs.page'),
				$this->db->quoteName('esrs.position'),
				$this->db->quoteName('esrs.authentication_level'),
				$this->db->quoteName('ec.lastname'),
				$this->db->quoteName('ec.firstname'),
				$this->db->quoteName('ec.email'),
				$this->db->quoteName('ec.phone_1'),
			])
				->from($this->db->quoteName($this->getTableName(RequestSignersRepository::class), 'esrs'))
				->leftJoin($this->db->quoteName($this->getTableName(ContactRepository::class), 'ec') . ' ON ' . $this->db->quoteName('ec.id') . ' = ' . $this->db->quoteName('esrs.contact_id'))
				->where($this->db->quoteName('esrs.request_id') . ' = :request_id')
				->bind(':request_id', $request_id, ParameterType::INTEGER);
			$this->db->setQuery($query);

			// todo: why are not RequestSigners entities returned here?
			return $this->db->loadObjectList();
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to load request signers : ' . $e->getMessage(), $e->getCode());
		}
	}

	/**
	 * TODO: Create a RequestFactory to avoid loading Request entities in the repository
	 * @param   array  $filters
	 * @param   int    $limit
	 * @param   int    $page
	 *
	 * @return array<Request>
	 * @throws \Exception
	 */
	public function getRequests(array $filters = [], int $limit = 0, int $page = 1): array
	{
		$requests = [];

		$query = $this->db->createQuery();
		$query->select('esr.id')
			->from($this->db->quoteName($this->getTableName(self::class), 'esr'));

		if (!empty($filters))
		{
			$this->applyFilters($query, $filters);
		}

		if ($limit > 0)
		{
			$offset = ($page - 1) * $limit;
			$query->setLimit($limit, $offset);
		}

		$this->db->setQuery($query);
		$request_ids = $this->db->loadColumn();

		foreach ($request_ids as $request_id)
		{
			$request = $this->loadRequestById($request_id);
			if ($request !== null)
			{
				$requests[] = $request;
			}
		}

		return $requests;
	}

	private function applyFilters(QueryInterface $query, array $filters): void
	{
		foreach ($filters as $field => $value)
		{
			if (is_array($value))
			{
				$query->whereIn($this->db->quoteName($field), $value, ParameterType::STRING);
			}
			else
			{
				$query->where($this->db->quoteName($field) . ' = :'.str_replace('.','_',$field))
					->bind(':'.str_replace('.','_',$field), $value, ParameterType::STRING);
			}
		}
	}

	public function getNotSignedRequests(string $connector, int $limit = 0): array
	{
		try
		{
			$signed = SignStatusEnum::SIGNED->value;

			$query = $this->buildQuery(['esr.id']);
			$query->where($this->db->quoteName('esr.status') . ' <> ' . $this->db->quote($signed))
				->where($this->db->quoteName('esr.connector') . ' = :connector')
				->bind(':connector', $connector, ParameterType::STRING);

			if ($limit > 0)
			{
				$query->setLimit($limit);
			}

			$this->db->setQuery($query);
			$not_signed_requests = $this->db->loadObjectList();

			foreach ($not_signed_requests as &$request)
			{
				$request = $this->loadRequestById($request->id);
			}

			return $not_signed_requests;
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to get not signed requests : ' . $e->getMessage(), $e->getCode());
		}
	}

	public function getSignedDocument(Request $request): ?string
	{
		$query = $this->db->createQuery();

		try
		{
			if(!empty($request->getSignedUploadId()))
			{
				$query->select('filename')
					->from($this->db->quoteName('#__emundus_uploads'))
					->where($this->db->quoteName('fnum') . ' = :fnum')
					->where($this->db->quoteName('id') . ' = :signed_upload_id')
					->bind(':fnum', $request->getFnum(), ParameterType::STRING)
					->bind(':signed_upload_id', $request->getSignedUploadId(), ParameterType::INTEGER);
				$this->db->setQuery($query);

				return $this->db->loadResult();
			}
			else {
				throw new \Exception('Signed upload id not set.', 400);
			}
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to get signed document : ' . $e->getMessage(), $e->getCode());
		}

	}

	public function updateStatus(int $id, SignStatusEnum|string $status): bool
	{
		if ($status instanceof SignStatusEnum)
		{
			$status = $status->value;
		}

		try
		{
			$query = $this->db->createQuery();

			$query->update($this->getTableName(self::class))
				->set($this->db->quoteName('status') . ' = :status')
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':status', $status, ParameterType::STRING)
				->bind(':id', $id, ParameterType::INTEGER);

			return $this->db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to update request status : ' . $e->getMessage(), $e->getCode());
		}
	}

	public function cancelAt(int $id, string $cancel_at): bool
	{
		try
		{
			$query = $this->db->createQuery();

			$query->update($this->getTableName(self::class))
				->set($this->db->quoteName('cancel_at') . ' = :cancel_at')
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':cancel_at', $cancel_at, ParameterType::STRING)
				->bind(':id', $id, ParameterType::INTEGER);

			return $this->db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to update request cancel at : ' . $e->getMessage(), $e->getCode());
		}
	}

	public function updateCancelReason(int $id, string $reason): bool
	{
		try
		{
			$query = $this->db->createQuery();

			$query->update($this->getTableName(self::class))
				->set($this->db->quoteName('cancel_reason') . ' = :reason')
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':reason', $reason, ParameterType::STRING)
				->bind(':id', $id, ParameterType::INTEGER);

			return $this->db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to update request cancel reason : ' . $e->getMessage(), $e->getCode());
		}
	}

	public function updateSendReminder(int $id, int $reminder): bool
	{
		try
		{
			$query = $this->db->createQuery();

			$query->update($this->getTableName(self::class))
				->set($this->db->quoteName('send_reminder') . ' = :reminder')
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':reminder', $reminder, ParameterType::INTEGER)
				->bind(':id', $id, ParameterType::INTEGER);

			return $this->db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to update request send reminder : ' . $e->getMessage(), $e->getCode());
		}
	}

	public function updateLastReminderAt(int $id, string $last_reminder_at): bool
	{
		try
		{
			$query = $this->db->createQuery();

			$query->update($this->getTableName(self::class))
				->set($this->db->quoteName('last_reminder_at') . ' = :last_reminder_at')
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':last_reminder_at', $last_reminder_at, ParameterType::STRING)
				->bind(':id', $id, ParameterType::INTEGER);

			return $this->db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to update request last reminder at : ' . $e->getMessage(), $e->getCode());
		}
	}

	public function uploadFile(string $filename, Request $request, array $application_file, User $user, int $filesize): int
	{
		$upload_id = 0;

		try
		{
			$upload_object = [
				'timedate'       => date('Y-m-d H:i:s'),
				'user_id'        => $user->id,
				'fnum'           => $request->getFnum(),
				'campaign_id'    => $application_file['campaign_id'],
				'attachment_id'  => $request->getAttachment()->getId(),
				'filename'       => $filename,
				'description'    => $request->getAttachment()->getName() . ' - Signed',
				'can_be_deleted' => 0,
				'can_be_viewed'  => 1,
				'size'           => $filesize,
				'signed_file'    => 1
			];

			$upload_object = (object) $upload_object;
			if ($this->db->insertObject('#__emundus_uploads', $upload_object))
			{
				$upload_id = $this->db->insertId();

				$update_request = [
					'id' => $request->getId(),
					'signed_upload_id' => $upload_id,
				];
				$update_request = (object) $update_request;
				$this->db->updateObject($this->getTableName(self::class), $update_request, 'id');
			}
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to upload file : ' . $e->getMessage(), $e->getCode());
		}

		return $upload_id;
	}

	private function buildQuery(array $columns, bool $buildWhere = true): QueryInterface
	{
		try
		{
			$query = $this->db->createQuery();

			$query->select($columns)
				->from($this->db->quoteName($this->getTableName(self::class), 'esr'))
				->leftJoin($this->db->quoteName($this->getTableName(AttachmentTypeRepository::class), 'esa') . ' ON ' . $this->db->quoteName('esa.id') . ' = ' . $this->db->quoteName('esr.attachment_id'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.id') . ' = ' . $this->db->quoteName('esr.ccid'))
				->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.user_id') . ' = ' . $this->db->quoteName('ecc.applicant_id'))
				->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('eu.user_id'))
				->leftJoin($this->db->quoteName($this->getTableName(RequestSignersRepository::class), 'esrs') . ' ON ' . $this->db->quoteName('esrs.request_id') . ' = ' . $this->db->quoteName('esr.id'));

			if ($buildWhere)
			{
				$query = $this->buildWhere($query);
			}

			return $query;
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to build query : ' . $e->getMessage(), $e->getCode());
		}
	}

	public function setFilters(string $search = '', ?string $status = '', ?int $attachment = 0, ?int $applicant = 0, ?string $signed_date = ''): array
	{
		$this->filters = [
			'search'      => $search,
			'status'      => $status,
			'attachment'  => $attachment,
			'applicant'   => $applicant,
			'signed_date' => $signed_date
		];

		return $this->filters;
	}

	public function buildWhere(QueryInterface $query): QueryInterface
	{
		if (!empty($this->filters['search']))
		{
			$search = '%' . trim($this->filters['search']) . '%';
			$query->where('(' . $this->db->quoteName('esa.value') . ' LIKE :search OR ' . $this->db->quoteName('eu.name') . ' LIKE :search)')
				->bind(':search', $search);
		}

		if (!empty($this->filters['attachment']))
		{
			$query->where($this->db->quoteName('esr.attachment_id') . ' = :attachment')
				->bind(':attachment', $this->filters['attachment'], ParameterType::INTEGER);
		}

		if (!empty($this->filters['applicant']))
		{
			$query->where($this->db->quoteName('esr.ccid') . ' = :applicant')
				->bind(':applicant', $this->filters['applicant'], ParameterType::INTEGER);
		}

		if (!empty($this->filters['status']))
		{
			$status = $this->filters['status'];
			if (is_string($status))
			{
				$status = [$status];
			}

			if (!is_array($status))
			{
				throw new \Exception('Status must be a string or an array of strings.', 400);
			}

			$query->whereIn($this->db->quoteName('esr.status'), $status, ParameterType::STRING);
		}

		if(!empty($this->filters['signed_date']))
		{
			$query->where('DATE('.$this->db->quoteName('esrs.signed_at') . ') = :signed_date')
				->bind(':signed_date', $this->filters['signed_date']);
		}

		return $query;
	}

	/**
	 * @param   string  $external_reference
	 *
	 * @return Request|null
	 * @throws \Exception
	 */
	public function getByExternalReference(string $external_reference): ?Request
	{
		$query = $this->db->createQuery();

		try
		{
			$query->select('esr.id')
				->from($this->db->quoteName($this->getTableName(self::class), 'esr'))
				->leftJoin($this->db->quoteName('#__emundus_external_reference', 'eer') . ' ON ' . $this->db->quoteName('eer.intern_id') . ' = ' . $this->db->quoteName('esr.id') . ' AND ' . $this->db->quoteName('eer.column') . ' = ' . $this->db->quote($this->getTableName(self::class) . '.id'))
				->where($this->db->quoteName('eer.reference') . ' = :external_reference')
				->bind(':external_reference', $external_reference, ParameterType::STRING);
			$this->db->setQuery($query);
			$request_id = $this->db->loadResult();

			if (!empty($request_id))
			{
				return $this->loadRequestById($request_id);
			}
		}
		catch (\Exception $e)
		{
			throw new \Exception('Failed to load request by external reference ' . $external_reference . ' : ' . $e->getMessage(), $e->getCode());
		}

		return null;
	}
}