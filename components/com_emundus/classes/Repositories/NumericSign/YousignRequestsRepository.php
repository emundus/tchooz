<?php
/**
 * @package     Tchooz\Repositories\NumericSign
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\NumericSign;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\NumericSign\Request;
use Tchooz\Entities\NumericSign\YousignRequests;
use Tchooz\Enums\ApiStatusEnum;
use Tchooz\Traits\TraitTable;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

#[TableAttribute(table: '#__emundus_yousign_requests')]
class YousignRequestsRepository
{
	use TraitTable;

	public function __construct(private DatabaseInterface $db)
	{
	}

	public function flush(YousignRequests $yousign_request): int
	{
		try
		{
			$yousign_object = $yousign_request->__serialize();
			$yousign_object = (object) $yousign_object;

			if (empty($yousign_object->id))
			{
				if (empty($yousign_request->getRequest()))
				{
					throw new \Exception('Request not set.', 400);
				}

				if (empty($yousign_request->getApiStatus()))
				{
					throw new \Exception('API Status not set.', 400);
				}

				if ($this->db->insertObject($this->getTableName(self::class), $yousign_object))
				{
					$yousign_request->setId($this->db->insertid());
				}
				else
				{
					throw new \Exception('Failed to insert yousign request.', 500);
				}
			}
			else
			{
				if (empty($yousign_object->expiration_date))
				{
					$yousign_object->expiration_date = $this->db->getNullDate();
				}

				if (!$this->db->updateObject($this->getTableName(self::class), $yousign_object, 'id'))
				{
					throw new \Exception('Failed to update yousign request.', 500);
				}
			}
		}
		catch (\Exception $e)
		{
			throw new \Exception('Error on Yousign request : ' . $e->getMessage(), 500);
		}

		return $yousign_request->getId();
	}

	public function loadYousignRequestByRequestId(Request $request): ?YousignRequests
	{
		$query = $this->db->createQuery();

		$query->select('*')
			->from($this->getTableName(self::class))
			->where('request_id = :request_id')
			->bind(':request_id', $request->getId(), ParameterType::INTEGER);

		$this->db->setQuery($query);

		$yousign_request_object = $this->db->loadObject();
		if ($yousign_request_object)
		{
			$yousign_request = $this->buildObject($yousign_request_object);
			$yousign_request->setRequest($request);

			return $yousign_request;
		}

		return null;
	}

	public function loadYousignRequestByProcedureId(string $procedure_id): ?YousignRequests
	{
		$query = $this->db->createQuery();

		$query->select('*')
			->from($this->getTableName(self::class))
			->where('procedure_id = :procedure_id')
			->bind(':procedure_id', $procedure_id);

		$this->db->setQuery($query);

		$yousign_request_object = $this->db->loadObject();
		if ($yousign_request_object)
		{
			$yousign_request = $this->buildObject($yousign_request_object);
			$request_repository = new RequestRepository($this->db);
			$yousign_request->setRequest($request_repository->loadRequestById($yousign_request_object->request_id));

			return $yousign_request;
		}

		return null;
	}

	public function updateApiStatus(int $requst_id, string|ApiStatusEnum $api_status): bool
	{
		if ($api_status instanceof ApiStatusEnum)
		{
			$api_status = $api_status->value;
		}

		$query = $this->db->createQuery();

		$query->update($this->getTableName(self::class))
			->set('status = :status')
			->where('id = :id')
			->bind(':status', $api_status)
			->bind(':id', $requst_id, ParameterType::INTEGER);
		$this->db->setQuery($query);

		return $this->db->execute();
	}

	public function loadYousignRequestSigners(int $request_id): array
	{
		$query = $this->db->createQuery();

		$query->select('*')
			->from('#__emundus_yousign_requests_signers')
			->where('parent_id = :parent_id')
			->bind(':parent_id', $request_id, ParameterType::INTEGER);

		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function loadYousignRequestSignerBySignerId(string $signer_id): object
	{
		$query = $this->db->createQuery();

		$query->select('*')
			->from($this->db->quoteName('#__emundus_yousign_requests_signers'))
			->where($this->db->quoteName('signer_id') . ' = :signer_id')
			->bind(':signer_id', $signer_id);

		$this->db->setQuery($query);

		return $this->db->loadObject();
	}

	public function addSigner(int $yousign_request_id, string $signer_id, int $request_signer_id, ?string $signature_url = '', ?string $signature_field = ''): bool
	{
		$yousign_signer                    = new \stdClass();
		$yousign_signer->parent_id         = $yousign_request_id;
		$yousign_signer->signer_id         = $signer_id;
		$yousign_signer->request_signer_id = $request_signer_id;
		$yousign_signer->signature_url     = $signature_url;
		$yousign_signer->signature_field   = $signature_field;

		return $this->db->insertObject('#__emundus_yousign_requests_signers', $yousign_signer);
	}

	private function buildObject(object $yousign_request_object): YousignRequests
	{
		$yousignRequest = new YousignRequests($yousign_request_object->created_by);
		$yousignRequest->setId($yousign_request_object->id);
		$yousignRequest->setName($yousign_request_object->name);
		if (!empty($yousign_request_object->procedure_id))
		{
			$yousignRequest->setProcedureId($yousign_request_object->procedure_id);
		}
		if (!empty($yousign_request_object->document_id))
		{
			$yousignRequest->setDocumentId($yousign_request_object->document_id);
		}
		if (!empty($yousign_request_object->signature_field))
		{
			$yousignRequest->setSignatureField($yousign_request_object->signature_field);
		}
		$yousignRequest->setApiStatus($yousign_request_object->status);
		$yousignRequest->setCreatedAt($yousign_request_object->created_at);
		$yousignRequest->setCreatedBy($yousign_request_object->created_by);
		if(!empty($yousign_request_object->expiration_date))
		{
			$yousignRequest->setExpirationDate($yousign_request_object->expiration_date);
		}
		$yousignRequest->setSigners($this->loadYousignRequestSigners($yousign_request_object->id));

		return $yousignRequest;
	}
}