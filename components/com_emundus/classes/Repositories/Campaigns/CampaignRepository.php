<?php
/**
 * @package     Tchooz\Repositories\Campaigns
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Campaigns;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Entities\List\ListResult;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Factories\Campaigns\CampaignFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\Programs\ProgramRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_setup_campaigns')]
class CampaignRepository extends EmundusRepository implements RepositoryInterface
{
	use TraitTable;

	private CampaignFactory $factory;

	private const COLUMNS = [
		't.id',
		't.label',
		't.short_description',
		't.description',
		't.start_date',
		't.end_date',
		't.profile_id',
		't.training',
		't.year',
		't.published',
		't.pinned',
		't.alias',
		't.visible',
		't.parent_id'
	];


	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'campaign');

		$this->factory = new CampaignFactory();
	}

	public function getAllCampaigns(
		$sort = 'DESC',
		$search = '',
		$lim = 25,
		$page = 0,
		$order_by = 't.id',
		$published = null,
		$parent_id = null,
		$user_category = null,
		$ids = [],
		$filters = []
	): ListResult
	{
		$result = new ListResult([], 0);

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

		if (empty($sort))
		{
			$sort = 'DESC';
		}

		$elements = $this->getCampaignMoreElements();

		$query = $this->db->getQuery(true);

		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'));

		// Apply filters if needed
		if (!empty($search))
		{
			$search     = $this->db->quote('%' . $this->db->escape($search, true) . '%', false);
			$conditions = [
				$this->db->quoteName('t.label') . ' LIKE ' . $search,
				$this->db->quoteName('t.description') . ' LIKE ' . $search,
				$this->db->quoteName('t.short_description') . ' LIKE ' . $search,
			];
			$query->where('(' . implode(' OR ', $conditions) . ')');
		}

		if (!empty($published) && $published !== 'all')
		{
			$published = $published == 'true' ? 1 : 0;
			$query->where($this->db->quoteName('t.published') . ' = ' . $published);
		}

		if (!empty($parent_id))
		{
			$where = $this->db->quoteName('t.parent_id') . ' = ' . (int) $parent_id;
			if (!empty($ids) && is_array($ids))
			{
				$where .= (' OR ' . $this->db->quoteName('t.id') . ' IN (' . implode(',', array_map('intval', $ids)) . ')');
			}

			$query->where('(' . $where . ')');
		}
		else
		{
			if (!empty($ids) && is_array($ids))
			{
				$query->where($this->db->quoteName('t.id') . ' IN (' . implode(',', array_map('intval', $ids)) . ')');
			}
		}

		if (!empty($user_category))
		{
			$query->leftJoin($this->db->quoteName('#__emundus_setup_campaigns_user_category', 'escuc') . ' ON escuc.campaign_id = t.id')
				->where('(escuc.user_category_id = ' . $this->db->quote($user_category) . ' OR escuc.user_category_id IS NULL)');
		}

		// Apply orders and limits if needed
		$query->group('t.id')
			->order($order_by . ' ' . $sort);

		try
		{
			$this->db->setQuery($query);
			$campaigns_count = sizeof($this->db->loadObjectList());

			$this->db->setQuery($query, $offset, $limit);
			$campaigns = $this->db->loadObjectList();

			foreach ($campaigns as $key => $campaign)
			{
				$campaign->more_data = $this->getMoreData((int) $campaign->id, $elements);
				if (!empty($filters))
				{
					foreach ($filters as $filter_key => $filter)
					{
						if (isset($campaign->more_data[$filter_key]))
						{
							if (is_array($campaign->more_data[$filter_key]))
							{
								if (!in_array($filter, $campaign->more_data[$filter_key]))
								{
									unset($campaigns[$key]);
									continue 2;
								}
							}
							else
							{
								if ($campaign->more_data[$filter_key] != $filter)
								{
									unset($campaigns[$key]);
									continue 2;
								}
							}
						}
					}
				}
				$campaigns[$key] = $this->factory->fromDbObject($campaign, $this->withRelations, [], null, $elements);
			}

			$result->setItems($campaigns);
			$result->setTotalItems($campaigns_count);
		}
		catch (\Exception $e)
		{
			Log::add('Error on get all campaigns : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.campaign');
		}

		return $result;
	}

	public function getCampaignMoreFormId(): int
	{
		$query = $this->db->getQuery(true);

		$cache     = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', ['defaultgroup' => 'com_emundus']);
		$cache_key = 'campaign_more_form_id';
		if ($cache->contains($cache_key))
		{
			return $cache->get($cache_key);
		}

		try
		{
			$query->select('fl.form_id')
				->from($this->db->quoteName('#__fabrik_lists', 'fl'))
				->where('fl.db_table_name = ' . $this->db->quote('jos_emundus_setup_campaigns_more'));
			$this->db->setQuery($query);
			$form_id = $this->db->loadResult();

			if (!empty($form_id))
			{
				$excluded_elements = ['id', 'date_time', 'campaign_id'];
				$query->clear()
					->select('COUNT(fe.id)')
					->from($this->db->quoteName('#__fabrik_elements', 'fe'))
					->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->db->quoteName('ffg.group_id') . ' = ' . $this->db->quoteName('fe.group_id'))
					->where($this->db->quoteName('fe.published') . ' = 1')
					->where($this->db->quoteName('fe.name') . ' NOT IN (' . implode(',', array_map([$this->db, 'quote'], $excluded_elements)) . ')')
					->where($this->db->quoteName('ffg.form_id') . ' = ' . (int) $form_id);
				$this->db->setQuery($query);
				$elements_count = $this->db->loadResult();

				if (!empty($elements_count))
				{
					$cache->store($form_id, $cache_key);

					return $form_id;
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on getChoicesMoreFormId : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.application_choices');
		}

		return 0;
	}

	public function getCampaignMoreElements(): array
	{
		$elements = [];

		$form_id = $this->getCampaignMoreFormId();

		if (!empty($form_id))
		{
			$cache     = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('output', ['defaultgroup' => 'com_emundus']);
			$cache_key = 'elements_' . $form_id;
			if ($cache->contains($cache_key))
			{
				return $cache->get($cache_key);
			}

			$query = $this->db->getQuery(true);

			$query->select('fe.*')
				->from($this->db->quoteName('#__fabrik_elements', 'fe'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->db->quoteName('ffg.group_id') . ' = ' . $this->db->quoteName('fe.group_id'))
				->where($this->db->quoteName('fe.published') . ' = 1')
				->where($this->db->quoteName('ffg.form_id') . ' = ' . $form_id);
			$this->db->setQuery($query);
			$elements = $this->db->loadAssocList();

			if (!empty($elements))
			{
				$cache->store($elements, $cache_key);
			}
		}

		return $elements;
	}

	public function getMoreData(int $campaign_id, array $elements = []): array
	{
		$more_data = [];

		$query = $this->db->getQuery(true);

		try
		{
			$cache     = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('output', ['defaultgroup' => 'com_emundus']);
			$cache_key = 'joins_jos_emundus_setup_campaigns_more';
			if ($cache->contains($cache_key))
			{
				$join_tables = $cache->get($cache_key);
			}

			if (empty($join_tables))
			{
				$query->clear()
					->select('element_id, table_join, table_key')
					->from($this->db->quoteName('#__fabrik_joins'))
					->where($this->db->quoteName('join_from_table') . ' = ' . $this->db->quote('jos_emundus_setup_campaigns_more'));
				$this->db->setQuery($query);
				$join_tables = $this->db->loadAssocList('element_id');
			}

			if (empty($elements))
			{
				$elements = $this->getCampaignMoreElements();
			}

			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__emundus_setup_campaigns_more', 't'))
				->where('t.campaign_id = ' . $this->db->quote($campaign_id));
			$this->db->setQuery($query);
			$more_data = $this->db->loadAssoc();


			foreach ($elements as $key => $element)
			{
				if (in_array($element['id'], array_keys($join_tables)))
				{
					$query->clear()
						->select($this->db->quoteName($join_tables[$element['id']]['table_key']))
						->from($this->db->quoteName($join_tables[$element['id']]['table_join']))
						->where($this->db->quoteName('parent_id') . ' = :id')
						->bind(':id', $more_data['id'], ParameterType::INTEGER);
					$this->db->setQuery($query);
					$values = $this->db->loadColumn();

					$more_data[$element['name']] = [];
					foreach ($values as $value)
					{
						$more_data[$element['name']][] = $value;
					}
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on getMoreData for campaign id ' . $campaign_id . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.campaign');
		}

		return !empty($more_data) ? $more_data : [];
	}

	/**
	 * @var CampaignEntity[]
	 */
	public function getParentCampaigns(): array
	{
		$parent_campaigns = [];

		$query = $this->db->getQuery(true);
		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->where('t.parent_id IS NULL OR t.parent_id = 0')
			->order('t.label ASC');
		$this->db->setQuery($query);
		$campaigns = $this->db->loadAssocList();

		if (!empty($campaigns))
		{
			foreach ($campaigns as $campaign)
			{
				$campaign_entity    = $this->factory->fromDbObject($campaign, $this->withRelations);
				$parent_campaigns[] = $campaign_entity;
			}
		}

		return $parent_campaigns;
	}

	/**
	 * @param   int  $parent_id
	 *
	 * @return array<CampaignEntity>
	 */
	public function getChildrenCampaigns(int $parent_id): array
	{
		$children_campaigns = [];

		$query = $this->db->getQuery(true);
		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->where('t.parent_id = ' . $this->db->quote($parent_id))
			->order('t.label ASC');
		$this->db->setQuery($query);
		$campaigns = $this->db->loadAssocList();

		if (!empty($campaigns))
		{
			foreach ($campaigns as $campaign)
			{
				$campaign_entity      = $this->factory->fromDbObject($campaign, $this->withRelations);
				$children_campaigns[] = $campaign_entity;
			}
		}

		return $children_campaigns;
	}

	public function getById(int $id): ?CampaignEntity
	{
		$campaign_entity = null;

		$query = $this->db->getQuery(true);
		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->where('t.id = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		$campaign = $this->db->loadAssoc();

		if (!empty($campaign))
		{
			$campaign_entity = $this->factory->fromDbObject($campaign, $this->withRelations);
		}

		return $campaign_entity;
	}

	public function getByLabel(string $label): ?CampaignEntity
	{
		$campaign_entity = null;

		$query = $this->db->getQuery(true);
		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->where('t.label = ' . $this->db->quote($label));
		$this->db->setQuery($query);
		$campaign = $this->db->loadAssoc();

		if (!empty($campaign))
		{
			$campaign_entity = $this->factory->fromDbObject($campaign, $this->withRelations);
		}

		return $campaign_entity;
	}

	public function getLinkedProgramsIds(int $campaign_id, string $fnum = ''): array
	{
		$programs_ids = [];

		$query = $this->db->getQuery(true);

		$query->select('p.id')
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->leftJoin($this->db->quoteName($this->getTableName(ProgramRepository::class), 'p') . ' ON p.code = t.training')
			->where('t.parent_id = ' . $this->db->quote($campaign_id));

		if (!empty($fnum))
		{
			$query->leftJoin($this->db->quoteName($this->getTableName(ApplicationChoicesRepository::class), 'ac') . ' ON ac.campaign_id = t.id')
				->where('ac.fnum = ' . $this->db->quote($fnum));
		}

		$this->db->setQuery($query);
		$child_programs = $this->db->loadColumn();

		if (!empty($child_programs))
		{
			$programs_ids = array_map('intval', $child_programs);
		}

		$campaign = $this->getById($campaign_id);
		if (!empty($campaign) && !empty($campaign->getParent()))
		{
			$parent_program_id = $campaign->getParent()->getProgram()->getId();

			if (!empty($parent_program_id))
			{
				$programs_ids[] = $parent_program_id;

				$linked_programs_ids = $this->getLinkedProgramsIds($campaign->getParent()->getId(), $fnum);
				$programs_ids        = array_merge($programs_ids, $linked_programs_ids);
			}
		}

		return $programs_ids;
	}

	/**
	 * @param   int  $campaignId
	 *
	 * @return StepEntity|null
	 */
	public function getCampaignDefaultStep(int $campaignId): ?StepEntity
	{
		$step = null;

		if (!empty($campaignId))
		{
			$campaign = $this->getById($campaignId);

			if (!empty($campaign) && !empty($campaign->getProfileId()))
			{
				if (!class_exists('EmundusModelForm'))
				{
					require_once(JPATH_ROOT . '/components/com_emundus/models/form.php');
				}
				$formModel = new \EmundusModelForm();
				$profile   = $formModel->getProfileLabelByProfileId($campaign->getProfileId());
				$step      = new StepEntity(0, 0, $profile->label, new StepTypeEntity(1), $campaign->getProfileId(), 0, [0], 1, 0, 1, 0);
			}
		}

		return $step;
	}

	public function getDbTablesByCampaignId(int $campaignId): array
	{
		$tables = [];

		if (!empty($campaignId))
		{
			$campaign = $this->getById($campaignId);

			if (!empty($campaign) && !empty($campaign->getProfileId()))
			{
				$query = $this->db->getQuery(true);

				$query->select('fl.db_table_name')
					->from($this->db->quoteName('#__emundus_setup_profiles', 'esp'))
					->leftJoin($this->db->quoteName('#__menu', 'm') . ' ON ' . $this->db->quoteName('m.menutype') . ' = ' . $this->db->quoteName('esp.menutype'))
					->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = SUBSTRING_INDEX(SUBSTRING(' . $this->db->quoteName('m.link') . ', LOCATE("formid=",' . $this->db->quoteName('m.link') . ')+7, 4), "&", 1)')
					->where($this->db->quoteName('esp.id') . ' = ' . $this->db->quote($campaign->getProfileId()))
					->where($this->db->quoteName('fl.db_table_name') . ' IS NOT NULL');
				$this->db->setQuery($query);
				$tables = $this->db->loadColumn();

				// TODO: Get tables from workflows
			}
		}

		return $tables;
	}

	public function buildQuery(): QueryInterface
	{

	}

	public function flush($entity): mixed
	{
		// TODO: Implement flush() method.
	}

	public function delete(int $id): bool
	{
		// TODO: Implement delete() method.
	}

	public function getParameters(): array
	{
		$emConfig = ComponentHelper::getParams('com_emundus');

		return [
			'campaign_date_format'     => $emConfig->get('campaign_date_format', 'd/m/Y H:i'),
			'campaign_show_start_date' => $emConfig->get('campaign_show_start_date', 1),
			'campaign_show_end_date'   => $emConfig->get('campaign_show_end_date', 1),
			'campaign_show_timezone'   => $emConfig->get('campaign_show_timezone', 1),
			'campaign_show_programme'  => $emConfig->get('campaign_show_programme', 1)
		];
	}
}