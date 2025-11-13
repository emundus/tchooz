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
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\NumericSign\Request;
use Tchooz\Entities\NumericSign\RequestSigners;
use Tchooz\Enums\NumericSign\SignStatus;
use Tchooz\Traits\TraitTable;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

#[TableAttribute(table: '#__emundus_sign_requests_signers')]
class RequestSignersRepository
{
	use TraitTable;
	
	public function __construct(private DatabaseInterface $db)
	{}

	public function flush(RequestSigners $signer): int
	{
		$signer_object = $signer->__serialize();
		$signer_object = (object) $signer_object;

		if (empty($signer_object->id))
		{
			if (empty($signer->getRequest()))
			{
				throw new \Exception('Signer request not set.', 400);
			}
			if (empty($signer->getStatus()))
			{
				throw new \Exception('Signer status not set.', 400);
			}
			if(empty($signer->getContact())) {
				throw new \Exception('Signer contact not set.', 400);
			}

			if ($this->db->insertObject($this->getTableName(self::class), $signer_object))
			{
				$signer->setId($this->db->insertid());
			}
			else
			{
				throw new \Exception('Failed to insert signer.', 500);
			}
		}
		else
		{
			if (!$this->db->updateObject($this->getTableName(self::class), $signer_object, 'id'))
			{
				throw new \Exception('Failed to update signer.', 500);
			}
		}

		return $signer->getId();
	}

	public function loadSignerByRequestAndContact(Request $request, ContactEntity $contact): ?RequestSigners
	{
		$request_id = $request->getId();
		$contact_id = $contact->getId();
		if(empty($request_id) || empty($contact_id))
		{
			throw new \Exception('Request or contact not set.', 400);
		}

		$query = $this->db->getQuery(true);
		$query->select('*')
			->from($this->getTableName(self::class))
			->where('request_id = :request_id')
			->where('contact_id = :contact_id')
			->bind(':request_id', $request_id, ParameterType::INTEGER)
			->bind(':contact_id', $contact_id, ParameterType::INTEGER);

		$this->db->setQuery($query);
		$signer_object = $this->db->loadObject();

		if ($signer_object)
		{
			$signer = new RequestSigners(
				$request,
				$contact
			);
			$signer->setId($signer_object->id);
			$signer->setStatus($signer_object->status);
			$signer->setStep($signer_object->step);

			return $signer;
		}

		return null;
	}

	public function updateStatus(int $id, SignStatus|string $status): bool
	{
		if($status instanceof SignStatus)
		{
			$status = $status->value;
		}

		$query = $this->db->getQuery(true);
		$query->update($this->getTableName(self::class))
			->set('status = ' . $this->db->quote($status))
			->where('id = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $this->db->setQuery($query)->execute();
	}

	public function updateSignedAt(int $id, string $signedAt): bool
	{
		$signedAt = Factory::getDate($signedAt)->toSql();

		$query = $this->db->getQuery(true);
		$query->update($this->getTableName(self::class))
			->set('signed_at = :signed_at')
			->where('id = :id')
			->bind(':signed_at', $signedAt)
			->bind(':id', $id, ParameterType::INTEGER);

		return $this->db->setQuery($query)->execute();
	}
}