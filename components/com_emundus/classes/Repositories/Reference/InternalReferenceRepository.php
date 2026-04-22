<?php
/**
 * @package     Tchooz\Repositories\Reference
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Reference;

use Joomla\CMS\Cache\CacheController;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Reference\InternalReferenceEntity;
use Tchooz\Factories\Reference\InternalReferenceFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: 'jos_emundus_internal_reference',
	alias: 'eir',
	columns: [
		'id',
		'created_at',
		'reference',
		'sequence',
		'campaign',
		'program',
		'year',
		'applicant_name',
		'ccid',
		'active'
	]
)]
class InternalReferenceRepository extends EmundusRepository implements RepositoryInterface
{
	private InternalReferenceFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'internal_reference', self::class);

		$this->factory = new InternalReferenceFactory();
	}

	public function flush(InternalReferenceEntity $reference): bool
	{
		if (empty($reference->getReference()))
		{
			throw new \InvalidArgumentException('Reference cannot be empty');
		}

		if (empty($reference->getApplicationFile()))
		{
			throw new \InvalidArgumentException('Application file cannot be empty');
		}

		$sequenceInt = $reference->getSequence();
		// Remove 0s from the sequence for integer comparison
		$sequenceInt = ltrim($sequenceInt, '0');
		$reference->setSequenceInt((int) $sequenceInt);

		$data = (object) [
			'reference'      => $reference->getReference(),
			'sequence'       => $reference->getSequence(),
			'sequence_int'   => $reference->getSequenceInt(),
			'campaign'       => $reference->getCampaign()?->getId(),
			'program'        => $reference->getProgram()?->getId(),
			'year'           => $reference->getYear(),
			'applicant_name' => $reference->getApplicantName(),
			'ccid'           => $reference->getApplicationFile()?->getId(),
			'active'         => $reference->isActive() ? 1 : 0,
		];

		if (empty($reference->getId()))
		{
			$data->created_at = Factory::getDate()->toSql();

			// First check if a active reference with the same ccid already exists
			$activeReference = $this->getActiveReference($reference->getApplicationFile()->getId());
			if (!empty($activeReference))
			{
				// Set inactive if an active reference with the same ccid already exists
				$activeReference->setActive(false);

				if (!$this->flush($activeReference))
				{
					throw new \RuntimeException('Failed to set existing reference inactive: ' . $this->db->getErrorMsg());
				}
			}

			if (!$this->db->insertObject($this->tableName, $data))
			{
				throw new \RuntimeException('Failed to insert internal reference: ' . $this->db->getErrorMsg());
			}

			$reference->setId($this->db->insertid());
		}
		else
		{
			$data->id = $reference->getId();
			if (!$this->db->updateObject($this->tableName, $data, 'id'))
			{
				throw new \RuntimeException('Failed to update internal reference: ' . $this->db->getErrorMsg());
			}
		}

		// Clear cache after flush
		$cacheKey = 'internal_reference_' . $reference->getId();
		if ($this->cache->contains($cacheKey))
		{
			$this->cache->remove($cacheKey);
		}

		return true;
	}

	public function clearAll(): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->tableName));

		$this->db->setQuery($query);

		if(!$this->db->execute())
		{
			throw new \RuntimeException('Failed to clear all references: ' . $this->db->getErrorMsg());
		}

		// Clear cache
		$this->cache->clean();

		return true;
	}

	public function delete(int $id): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);

		if(!$this->db->execute())
		{
			throw new \RuntimeException('Failed to delete internal reference: ' . $this->db->getErrorMsg());
		}

		// Clear cache
		$this->cache->remove('internal_reference_' . $id);

		return true;
	}

	public function getById(int $id): ?InternalReferenceEntity
	{
		$internalReference = null;
		$cacheKey          = 'internal_reference_' . $id;
		if ($this->cache->contains($cacheKey))
		{
			$object = $this->cache->get($cacheKey);
		}

		if (empty($object))
		{
			$object = $this->getItemByField('id', $id);
		}

		if (!empty($object))
		{
			$this->cache->store($object, $cacheKey);
			$internalReference = $this->factory->fromDbObject($object, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $internalReference;
	}

	public function getActiveReference(int $ccid): ?InternalReferenceEntity
	{
		$reference = null;

		$objects = $this->getItemsByFields(['ccid' => $ccid, 'active' => 1]);

		if (!empty($objects))
		{
			$reference = $this->factory->fromDbObject($objects[0], $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $reference;
	}

	public function getLastSequence(): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('sequence_int'))
			->from($this->db->quoteName($this->tableName))
			->order($this->db->quoteName('sequence_int') . ' DESC')
			->setLimit(1);

		$this->db->setQuery($query);

		return $this->db->loadResult() ?? '0000';
	}

	public function getLastSequenceByYear(string $year): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('sequence_int'))
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('year') . ' = ' . $this->db->quote($year))
			->order($this->db->quoteName('sequence_int') . ' DESC')
			->setLimit(1);

		$this->db->setQuery($query);

		return $this->db->loadResult() ?? '0000';
	}

	public function getLastSequenceByCampaign(int $campaignId): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('sequence_int'))
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('campaign') . ' = ' . $this->db->quote($campaignId))
			->order($this->db->quoteName('sequence_int') . ' DESC')
			->setLimit(1);

		$this->db->setQuery($query);

		return $this->db->loadResult() ?? '0000';
	}

	public function getLastSequenceByProgram(int $programId): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('sequence_int'))
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('program') . ' = ' . $this->db->quote($programId))
			->order($this->db->quoteName('sequence_int') . ' DESC')
			->setLimit(1);

		$this->db->setQuery($query);

		return $this->db->loadResult() ?? '0000';
	}

	public function getFactory(): InternalReferenceFactory
	{
		return $this->factory;
	}


}