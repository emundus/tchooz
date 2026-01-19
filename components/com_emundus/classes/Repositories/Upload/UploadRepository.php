<?php

namespace Tchooz\Repositories\Upload;

use Joomla\CMS\Language\Text;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Factories\Upload\UploadFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(table: '#__emundus_uploads', alias: 'upload', columns: ['id', 'fnum', 'attachment_id'])]
class UploadRepository extends EmundusRepository implements RepositoryInterface
{
	private UploadFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct(
			withRelations: $withRelations,
			exceptRelations: $exceptRelations,
			name: 'upload',
			className: self::class
		);

		$this->factory = new UploadFactory();
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->tableName))
				->where($this->db->quoteName($this->primaryKey) . ' = ' . $id);

			$this->db->setQuery($query);

			$deleted = (bool) $this->db->execute();
		}

		return $deleted;
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return array<UploadEntity>
	 */
	public function getByFnum(string $fnum): array
	{
		$entities = [];
		$objects = $this->getItemsByField('fnum', $fnum);

		if (!empty($objects))
		{
			$entities = $this->factory->fromDbObjects($objects);
		}

		return $entities;
	}

	/**
	 * @param   array  $params
	 *
	 * @return array<UploadEntity>
	 */
	public function getBy(array $params): array
	{
		$entities = [];

		$objects = $this->getItemsByFields($params);
		if (!empty($objects))
		{
			$entities = $this->factory->fromDbObjects($objects);
		}

		return $entities;
	}

	/**
	 * @param   int  $id
	 *
	 * @return UploadEntity|null
	 */
	public function getById(int $id): ?UploadEntity
	{
		$entity = null;
		$object = $this->getItemByField('id', $id);

		if (!empty($object))
		{
			$entities = $this->factory->fromDbObjects([$object]);
			$entity = $entities[0];
		}

		return $entity;
	}

	/**
	 * @param   UploadEntity  $entity
	 *
	 * @return bool
	 */
	public function flush(UploadEntity $entity): bool
	{
		$flushed = false;

		$this->verifyRequirements($entity);

		if (empty($entity->getId()))
		{
			$object = (object) [
				'timedate' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
				'user_id' => $entity->getUserId(),
				'fnum' => $entity->getFnum(),
				'campaign_id' => $entity->getCampaignId(),
				'attachment_id' => $entity->getAttachmentId(),
				'filename' => $entity->getFilename(),
				'description' => $entity->getDescription(),
				'local_filename' => $entity->getLocalFilename(),
				'size' => $entity->getSize(),
				'is_validated' => $entity->getValidationStatus()->value,
				'signed_file' => $entity->isSigned() ? 1 : 0,
				'thumbnail' => $entity->getThumbnail(),
				'can_be_deleted' => (int) $entity->canBeDeleted(),
				'can_be_viewed' => (int) $entity->canBeViewed(),
				'modified' => null,
				'modified_by' => null,
			];

			if ($this->db->insertObject($this->tableName, $object))
			{
				$entity->setId((int) $this->db->insertid());
				$flushed = true;
			}
		}
		else
		{
			$object = (object) [
				'id' => $entity->getId(),
				'user_id' => $entity->getUserId(),
				'fnum' => $entity->getFnum(),
				'campaign_id' => $entity->getCampaignId(),
				'attachment_id' => $entity->getAttachmentId(),
				'filename' => $entity->getFilename(),
				'description' => $entity->getDescription(),
				'local_filename' => $entity->getLocalFilename(),
				'size' => $entity->getSize(),
				'is_validated' => $entity->getValidationStatus()->value,
				'is_signed' => $entity->isSigned() ? 1 : 0,
				'thumbnail' => $entity->getThumbnail(),
				'can_be_deleted' => (int) $entity->canBeDeleted(),
				'can_be_viewed' => (int) $entity->canBeViewed(),
				'modified' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
				'modified_by' => $entity->getModifiedBy() ?? null,
			];

			$flushed = $this->db->updateObject($this->tableName, $object, 'id');
		}

		return $flushed;
	}

	/**
	 * @param   UploadEntity  $entity
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException
	 */
	public function verifyRequirements(UploadEntity $entity): void
	{
		if (empty($entity->getFnum()))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_ERROR_UPLOAD_FNUM_REQUIRED'));
		}

		if (empty($entity->getAttachmentId()))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_ERROR_UPLOAD_ATTACHMENT_ID_REQUIRED'));
		}

		if (empty($entity->getFilename()))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_ERROR_UPLOAD_FILENAME_REQUIRED'));
		}
	}
}