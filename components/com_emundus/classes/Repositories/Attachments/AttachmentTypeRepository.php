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
use Joomla\Database\QueryInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Attachments\AttachmentType;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Factories\Attachments\AttachmentTypeFactory;
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

	private AttachmentTypeFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [], $name = '', $className = self::class)
	{
		parent::__construct($withRelations, $exceptRelations, $name, $className);
		Log::addLogger(['text_file' => 'com_emundus.attachment_type.repository.php'], Log::ALL, ['com_emundus.attachment_type.repository']);

		$this->factory = new AttachmentTypeFactory();
	}

	public function flush(): bool
	{
		$flushed = false;

		// todo: implement flush logic

		return $flushed;
	}

	public function loadAttachmentTypeById(int $id): ?AttachmentType
	{
		$attachmentType = null;

		$query = $this->db->createQuery();

		$query->select('*')
			->from($this->getTableName(self::class))
			->where('id = ' . $id);
		$this->db->setQuery($query);
		$attachment_type_object = $this->db->loadObject();

		if(!empty($attachment_type_object))
		{
			$attachmentTypes = $this->factory::fromDbObjects([$attachment_type_object]);
			$attachmentType = $attachmentTypes[0] ?? null;
		}

		return $attachmentType;
	}

	/**
	 * @param   array   $filters
	 * @param   int     $limit
	 * @param   int     $page
	 * @param   string  $select
	 * @param   string  $order
	 *
	 * @return array<AttachmentType>
	 */
	public function get(array $filters = [], int $limit = 10, int $page = 1, string $select = '*', string $order = ''): array
	{
		$types = [];

		// todo: use EmundusRepository get method

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

			$types = $this->factory::fromDbObjects($objects);
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus.attachment_type.repository');
		}

		return $types;
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
				$attachmentTypes = $this->factory::fromDbObjects($objects);
			}
			catch (\Exception $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_emundus.attachment_type.repository');
			}
		}

		return $attachmentTypes;
	}
}