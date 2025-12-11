<?php
/**
 * @package     Tchooz\Repositories\Application
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\ApplicationFile;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use phpDocumentor\Reflection\Types\Self_;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Enums\Campaigns\StatusEnum;
use Tchooz\Factories\ApplicationFile\ApplicationChoicesFactory;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Traits\TraitTable;
use function Symfony\Component\String\s;

#[TableAttribute(
	table: '#__emundus_campaign_candidature_choices',
	alias: 'eccc',
	columns: [
		'id',
		'campaign_id',
		'fnum',
		'user_id',
		'state',
		'order',
	]
)]
class ApplicationChoicesRepository extends EmundusRepository implements RepositoryInterface
{
	private ApplicationChoicesFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'application_choices', self::class);

		$this->factory = new ApplicationChoicesFactory();
	}

	public function flush(ApplicationChoicesEntity $entity, ?bool $checkRules = true): bool
	{
		$flushed = false;

		if (empty($entity->getCampaign()) || empty($entity->getFnum()))
		{
			throw new \InvalidArgumentException('Campaign ID and Fnum are required to flush ApplicationChoicesEntity');
		}

		$existing_choices = $this->getChoicesByFnum($entity->getFnum());

		if ($checkRules)
		{
			$this->checkRules($entity, $existing_choices);
		}

		if (empty($entity->getOrder()))
		{
			$entity->setOrder(count($existing_choices) + 1);
		}

		if (empty($entity->getId()))
		{
			$insert = (object) [
				'campaign_id' => $entity->getCampaign()->getId(),
				'fnum'        => $entity->getFnum(),
				'user_id'     => $entity->getUser()->id,
				'state'       => $entity->getState()->value,
				'order'       => $entity->getOrder(),
			];


			if(!$this->db->insertObject($this->tableName, $insert))
			{
				throw new \Exception('Failed to insert ApplicationChoicesEntity');
			}

			$entity->setId($this->db->insertid());
			$flushed = true;
		}
		else
		{
			$old_data = $this->getById($entity->getId());

			$update = (object) [
				'id'          => $entity->getId(),
				'campaign_id' => $entity->getCampaign()->getId(),
				'fnum'        => $entity->getFnum(),
				'user_id'     => $entity->getUser()->id,
				'state'       => $entity->getState()->value,
				'order'       => $entity->getOrder(),
			];

			$flushed = $this->db->updateObject($this->tableName, $update, 'id');
		}

		// If state is confirmed, move application to the campaign confirmed
		if(
			$entity->getState() === ChoicesStateEnum::CONFIRMED &&
			(
				empty($old_data) || ($old_data->getState() !== ChoicesStateEnum::CONFIRMED)
			)
		)
		{
			if (!class_exists('EmundusModelApplication'))
			{
				require_once JPATH_SITE . '/components/com_emundus/models/application.php';
			}
			$m_application = new \EmundusModelApplication();
			$m_application->moveApplication($entity->getFnum(), $entity->getFnum(), $entity->getCampaign()->getId());
		}
		// If old state was confirmed and new state is not, move application back to parent campaign
		elseif(
			!empty($old_data) && $old_data->getState() === ChoicesStateEnum::CONFIRMED && $entity->getState() !== ChoicesStateEnum::CONFIRMED && !empty($entity->getCampaign()->getParent()))
		{
			if (!class_exists('EmundusModelApplication'))
			{
				require_once JPATH_SITE . '/components/com_emundus/models/application.php';
			}
			$m_application = new \EmundusModelApplication();
			$m_application->moveApplication($entity->getFnum(), $entity->getFnum(), $entity->getCampaign()->getParent()->getId());
		}

		return $flushed;
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->getQuery(true);

			$query->clear()
				->delete($this->tableName)
				->where('id = ' . $id);

			try
			{
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			}
			catch (\Exception $e)
			{
				Log::add('Error on delete application choice : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.application_choices');
			}
		}

		return $deleted;
	}

	public function getById(int $id): ?ApplicationChoicesEntity
	{
		$application_choice_entity = null;

		$query = $this->db->getQuery(true);
		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->alias.'.id = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		$application_choice = $this->db->loadAssoc();

		if (!empty($application_choice))
		{
			$application_choice_entity = $this->factory->fromDbObject($application_choice, $this->withRelations);
		}

		return $application_choice_entity;
	}

	/**
	 * @return ApplicationChoicesEntity[]
	 */
	public function getChoicesByFnum(string $fnum, array $user_programs = [], ChoicesStateEnum $state = null, int $more_form_id = 0): array
	{
		$application_choices_entity = [];

		$elements   = $this->getChoicesMoreElements($more_form_id);
		$table_name = $this->getMoreTableName($more_form_id);

		$query = $this->buildQuery($fnum, $user_programs, $state);

		$this->db->setQuery($query);
		$application_choices = $this->db->loadObjectList();

		foreach ($application_choices as $application_choice)
		{
			$application_choice->more_data = $this->getMoreData((int) $application_choice->id, $more_form_id, $elements, $table_name);
			$application_choices_entity[]  = $this->factory->fromDbObject($application_choice, $this->withRelations, [], null, $elements);
		}

		return $application_choices_entity;
	}

	public function getChoicesMoreElements(int $form_id): array
	{
		$elements = [];

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

	public function getMoreTableName(int $form_id): string
	{
		$table_name = '';

		$cache     = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', ['defaultgroup' => 'com_emundus']);
		$cache_key = 'table_name_' . $form_id;
		if ($cache->contains($cache_key))
		{
			return $cache->get($cache_key);
		}

		if (!empty($form_id))
		{
			$query = $this->db->getQuery(true);

			$query->select('db_table_name')
				->from($this->db->quoteName('#__fabrik_lists'))
				->where($this->db->quoteName('form_id') . ' = ' . $form_id);
			$this->db->setQuery($query);
			$table_name = $this->db->loadResult();

			if (!empty($table_name))
			{
				$cache->store($table_name, $cache_key);
			}
		}

		return $table_name;
	}

	public function getMoreData(int $parent_id, int $form_id, array $elements = [], string $table_name = ''): array
	{
		$more_data = [];
		if (empty($parent_id) || empty($form_id))
		{
			return $more_data;
		}

		$query = $this->db->getQuery(true);

		try
		{
			if (empty($elements))
			{
				$elements = $this->getChoicesMoreElements($form_id);
			}

			if (empty($table_name))
			{
				$table_name = $this->getMoreTableName($form_id);
			}

			$cache     = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('output', ['defaultgroup' => 'com_emundus']);
			$cache_key = 'joins_' . $table_name . '_' . $form_id;
			if ($cache->contains($cache_key))
			{
				$join_tables = $cache->get($cache_key);
			}

			if (empty($join_tables))
			{
				$query->clear()
					->select('element_id, table_join, table_key')
					->from($this->db->quoteName('#__fabrik_joins'))
					->where($this->db->quoteName('join_from_table') . ' = ' . $this->db->quote($table_name));
				$this->db->setQuery($query);
				$join_tables = $this->db->loadAssocList('element_id');

				if (!empty($join_tables))
				{
					$cache->store($join_tables, $cache_key);
				}
			}

			$query->clear()
				->select('*')
				->from($this->db->quoteName($table_name, 't'))
				->where('t.parent_id = ' . $this->db->quote($parent_id));
			$this->db->setQuery($query);
			$more_data = $this->db->loadAssoc();
			
			foreach ($elements as $element)
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
			Log::add('Error on getMoreData for parent id ' . $parent_id . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.application_choices');
		}

		return !empty($more_data) ? $more_data : [];
	}

	public function buildQuery(string $fnum = '', array $user_programs = [], ChoicesStateEnum $state = null): QueryInterface
	{
		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->order($this->alias.'.order ASC');

		if(!empty($fnum))
		{
			$query->where($this->alias.'.fnum = ' . $this->db->quote($fnum));
		}

		if (!empty($user_programs))
		{
			$query->leftJoin(
				$this->db->quoteName($this->getTableName(ApplicationFileRepository::class), 'af') .
				' ON ' .
				$this->db->quoteName('af.fnum') . ' = ' . $this->db->quoteName($this->alias.'.fnum'))
				->leftJoin(
					$this->db->quoteName($this->getTableName(CampaignRepository::class), 'c') .
					' ON ' .
					$this->db->quoteName('c.id') . ' = ' . $this->db->quoteName($this->alias.'.campaign_id'))
				->where('c.training IN (' . implode(',', array_map([$this->db, 'quote'], $user_programs)) . ')');
		}

		if (!empty($state))
		{
			$query->where($this->alias.'.state = ' . $this->db->quote($state->value));
		}

		return $query;
	}

	private function checkRules($entity, $existing_choices = []): true
	{
		if (!class_exists('EmundusModelWorkflow'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/workflow.php';
		}
		$m_workflow     = new \EmundusModelWorkflow();
		$choices_config = $m_workflow->getChoicesConfigurationFromFnum($entity->getFnum());

		if (empty($existing_choices))
		{
			$existing_choices = $this->getChoicesByFnum($entity->getFnum());
		}

		if (!empty($choices_config['max']))
		{
			if (count($existing_choices) > $choices_config['max'])
			{
				throw new \InvalidArgumentException(Text::_('PLG_EMUNDUS_APPLICATION_CHOICES_MAX_REACHED'));
			}
		}

		foreach ($existing_choices as $existing_choice)
		{
			if ($existing_choice->getId() !== $entity->getId() &&
				$existing_choice->getCampaign()->getId() === $entity->getCampaign()->getId() &&
				$existing_choice->getFnum() === $entity->getFnum())
			{
				throw new \InvalidArgumentException(Text::_('PLG_EMUNDUS_APPLICATION_CHOICES_ALREADY_EXIST'));
			}
		}

		if (!$entity->getCampaign()->isPublished() || $entity->getCampaign()->getStatus() === StatusEnum::CLOSED)
		{
			throw new \InvalidArgumentException(Text::_('PLG_EMUNDUS_APPLICATION_CHOICES_INVALID'));
		}

		$applicationFileRepository = new ApplicationFileRepository();
		$application_file          = $applicationFileRepository->getByFnum($entity->getFnum());

		if (empty($application_file) || $application_file->getCampaignId() !== $entity->getCampaign()->getParent()->getId())
		{
			throw new \InvalidArgumentException(Text::_('PLG_EMUNDUS_APPLICATION_CHOICES_INVALID_PARENT'));
		}

		return true;
	}
}