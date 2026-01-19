<?php
/**
 * @package     Tchooz\Repositories\Attachments
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Attachments;

use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Attachments\AttachmentType;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_setup_attachments', alias: 'esa', columns:
	[
		'id',
		'lbl',
		'value',
		'description',
		'allowed_types',
		'nbmax',
		'ordering',
		'published',
		'category'
	]
)]
class AttachmentTypeRepository extends EmundusRepository
{
	use TraitTable;

	public function __construct($withRelations = true, $exceptRelations = [], $name = '', $className = self::class)
	{
		parent::__construct($withRelations, $exceptRelations, $name, $className);
		Log::addLogger(['text_file' => 'com_emundus.attachment_type.repository.php'], Log::ALL, ['com_emundus.attachment_type.repository']);
	}

	public function flush(): bool
	{
		$flushed = false;

		// todo: implement flush logic

		return $flushed;
	}

	public function loadAttachmentTypeById(int $id): ?AttachmentType
	{
		$query = $this->db->createQuery();

		$query->select('*')
			->from($this->getTableName(self::class))
			->where('id = ' . $id);
		$this->db->setQuery($query);
		$attachment_type_object = $this->db->loadObject();

		if(!empty($attachment_type_object))
		{
			$attachment_type = new AttachmentType();
			$attachment_type->setId($attachment_type_object->id);
			$attachment_type->setLbl($attachment_type_object->lbl);
			$attachment_type->setName($attachment_type_object->value);
			$attachment_type->setDescription($attachment_type_object->description);
			$attachment_type->setAllowedTypes($attachment_type_object->allowed_types);
			$attachment_type->setNbMax($attachment_type_object->nbmax);
			$attachment_type->setOrdering($attachment_type_object->ordering);
			$attachment_type->setPublished($attachment_type_object->published);
			$attachment_type->setCategory($attachment_type_object->category);

			return $attachment_type;
		}

		return null;
	}

	/**
	 * @param   array  $filters
	 * @param   int    $limit
	 * @param   int    $page
	 *
	 * @return array<AttachmentType>
	 */
	public function get(array $filters = [], int $limit = 10, int $page = 1): array
	{
		$types = [];

		$query = $this->db->createQuery();
		$query->select($this->alias . '.*')
			->from($this->db->quoteName($this->tableName, $this->alias));

		if (!empty($filters))
		{
			$this->applyFilters($query, $filters);
		}

		if (!empty($limit))
		{
			$offset = ($page - 1) * $limit;
			$query->setLimit($limit, $offset);
		}

		try
		{
			$this->db->setQuery($query);
			$objects = $this->db->loadObjectList();

			foreach ($objects as $object)
			{
				$attachmentType = new AttachmentType();
				$attachmentType->setId($object->id);
				$attachmentType->setLbl($object->lbl);
				$attachmentType->setName($object->value);
				$attachmentType->setDescription($object->description);
				$attachmentType->setAllowedTypes($object->allowed_types);
				$attachmentType->setNbMax($object->nbmax);
				$attachmentType->setOrdering($object->ordering);
				$attachmentType->setPublished($object->published);
				$attachmentType->setCategory($object->category);
				$types[] = $attachmentType;
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus.attachment_type.repository');
		}

		return $types;
	}

	public function applyFilters($query, array $filters): void
	{
		foreach ($filters as $field => $value)
		{
			if (!in_array($field, $this->columns))
			{
				continue;
			}

			if (is_array($value))
			{
				$query->where($this->tableName . '.' . $field . ' IN (' . implode(',', $value) . ')');
			} else {
				$query->where($this->tableName . '.' . $field . ' = ' . $this->db->quote($value));
			}
		}
	}
}