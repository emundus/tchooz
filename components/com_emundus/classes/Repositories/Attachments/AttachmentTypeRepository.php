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
use Tchooz\Entities\Campaigns\CampaignEntity;
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
			return new AttachmentType(
				$attachment_type_object->id,
				$attachment_type_object->lbl,
				$attachment_type_object->value,
				$attachment_type_object->description,
				$attachment_type_object->allowed_types,
				$attachment_type_object->nbmax,
				$attachment_type_object->ordering,
				$attachment_type_object->published == 1,
				$attachment_type_object->category
			);
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
				$types[] = new AttachmentType(
					$object->id,
					$object->lbl,
					$object->value,
					$object->description,
					$object->allowed_types,
					$object->nbmax,
					$object->ordering,
					$object->published == 1,
					$object->category
				);
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

	/**
	 * @param   array<CampaignEntity>  $campaigns
	 *
	 * @return array<AttachmentType>
	 */
	public function getAttachmentLettersByCampaigns(array $campaigns): array
	{
		$attachmentTypes = [];

		if (!empty($campaigns))
		{
			$campaignIds = array_map(fn($c) => $c->getId(), $campaigns);
			$programCodes = array_map(fn($c) => $c->getProgram()->getCode(), $campaigns);

			$query = $this->db->createQuery();
			$query->select('DISTINCT ' . $this->alias . '.*')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->leftJoin($this->db->quoteName('#__emundus_setup_letters', 'esl') . ' ON ' . $this->db->quoteName('esl.attachment_id') . ' = ' . $this->db->quoteName($this->alias . '.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_letters_repeat_campaign', 'eslrc') . ' ON ' . $this->db->quoteName('eslrc.parent_id') . ' = ' . $this->db->quoteName('esl.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_letters_repeat_training', 'eslrt') . ' ON ' . $this->db->quoteName('eslrt.parent_id') . ' = ' . $this->db->quoteName('esl.id'))
				->where('(' . $this->db->quoteName('eslrc.campaign') . ' IN (' . implode(',', $campaignIds) . ') OR ' . $this->db->quoteName('eslrt.training') . ' IN (' . implode(',', array_map(fn($code) => $this->db->quote($code), $programCodes)) . '))')
				->andWhere($this->db->quoteName($this->alias . '.published') . ' = 1');

			try
			{
				$this->db->setQuery($query);
				$objects = $this->db->loadObjectList();

				foreach ($objects as $object)
				{
					$attachmentTypes[] = new AttachmentType(
						$object->id,
						$object->lbl,
						$object->value,
						$object->description,
						$object->allowed_types,
						$object->nbmax,
						$object->ordering,
						$object->published == 1,
						$object->category
					);
				}
			}
			catch (\Exception $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_emundus.attachment_type.repository');
			}
		}

		return $attachmentTypes;
	}
}