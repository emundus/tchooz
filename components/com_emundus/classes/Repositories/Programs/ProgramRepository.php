<?php
/**
 * @package     Tchooz\Repositories\Programs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Programs;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Factories\Programs\ProgramFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\Groups\GroupRepository;
use Tchooz\Services\UploadService;
use Tchooz\Traits\TraitDispatcher;

#[TableAttribute(
	table: '#__emundus_setup_programmes',
	alias: 'esp',
	columns: [
		'id',
		'code',
		'label',
		'notes',
		'published',
		'programmes',
		'synthesis',
		'apply_online',
		'ordering',
		'logo',
		'color'
	]
)
]
class ProgramRepository extends EmundusRepository
{
	use TraitDispatcher;
	private ProgramFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'programme', self::class);
		$this->factory = new ProgramFactory();
	}

	public function flush(ProgramEntity $programEntity, ?User $user = null): bool
	{
		if(empty($user))
		{
			$user = Factory::getApplication()->getIdentity();
		}

		$programEntity->sanitize();

		$data = [
			'code'         => $programEntity->getSlug(),
			'label'        => $programEntity->getLabel(),
			'notes'        => $programEntity->getNotes(),
			'published'    => $programEntity->isPublished() ? 1 : 0,
			'programmes'   => $programEntity->getProgrammes(),
			'synthesis'    => $programEntity->getSynthesis(),
			'apply_online' => $programEntity->isApplyOnline() ? 1 : 0,
			'logo'         => $programEntity->getLogo(),
		];

		$isNew = empty($programEntity->getId());

		if ($isNew)
		{
			$this->dispatchJoomlaEvent('onBeforeProgramCreate', ['data' => $data]);
		}

		$data = (object) $data;
		if ($isNew)
		{
			if (!$this->db->insertObject($this->tableName, $data, 'id'))
			{
				throw new \Exception('Failed to insert program');
			}

			$programEntity->setId((int) $data->id);
		}
		else
		{
			$data->id = $programEntity->getId();
			if (!$this->db->updateObject($this->tableName, $data, 'id'))
			{
				throw new \Exception('Failed to update program');
			}
		}

		// Clear cache
		$hCache = new \EmundusHelperCache();
		$hCache->clean(false);

		if ($isNew)
		{
			$this->dispatchJoomlaEvent('onAfterProgramCreate', ['programme' => $data, 'user_id' => $user->id, 'context' => new EventContextEntity($user, [],[],[])]);
		}

		return true;
	}

	public function getById(int $id): ?ProgramEntity
	{
		$program_entity = null;

		$query = $this->db->getQuery(true);
		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where('id = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		$program = $this->db->loadAssoc();

		if (!empty($program))
		{
			$program_entity = $this->factory::fromDbObject($program);
		}

		return $program_entity;
	}

	public function getByCode(string $code): ?ProgramEntity
	{
		$program_entity = null;

		$query = $this->db->getQuery(true);
		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where('code = ' . $this->db->quote($code));
		$this->db->setQuery($query);
		$program = $this->db->loadAssoc();

		if (!empty($program))
		{
			$program_entity = $this->factory::fromDbObject($program);
		}

		return $program_entity;
	}

	public function getCodesByIds(array $ids): array
	{
		$codes = [];

		if (!empty($ids))
		{
			$query = $this->db->getQuery(true);
			$query->select('code')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where('id IN (' . implode(',', array_map([$this->db, 'quote'], $ids)) . ')');
			$this->db->setQuery($query);
			$codes = $this->db->loadColumn();
		}

		return $codes;
	}

	public function getCategories(): array
	{
		$cacheKey = 'program_categories';
		if ($this->cache && $this->cache->contains($cacheKey))
		{
			return $this->cache->get($cacheKey);
		}

		$query = $this->db->getQuery(true);
		$query->select('programmes')
			->from($this->db->quoteName($this->tableName))
			->where('published = 1')
			->order('programmes ASC');
		$this->db->setQuery($query);
		$categories = $this->db->loadColumn();
		$categories = array_filter(array_unique($categories));

		if ($this->cache && !empty($categories))
		{
			$this->cache->store($categories, $cacheKey);
		}

		return $categories;
	}

	/**
	 * @param   string  $programCode
	 *
	 * @return array<GroupEntity>
	 *
	 * @since version
	 */
	public function getGroupsByProgramCode(string $programCode): array
	{
		$groups = [];

		$query = $this->db->getQuery(true);
		$query->select('parent_id')
			->from($this->db->quoteName('#__emundus_setup_groups_repeat_course'))
			->where('course = ' . $this->db->quote($programCode));
		$this->db->setQuery($query);
		$groupIds = $this->db->loadColumn();

		$groupRepository = new GroupRepository();
		if (!empty($groupIds))
		{
			$groups = $groupRepository->getItemsByField('id', $groupIds, true);
		}

		return $groups;
	}

	public function deleteLogo(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->createQuery();

			$query->select('logo')
				->from($this->tableName)
				->where('id = ' . $id);

			try
			{
				$this->db->setQuery($query);
				$logoPath = $this->db->loadResult();

				if (!empty($logoPath))
				{
					$uploader = new UploadService('images/emundus/programs/');
					$deleted  = $uploader->deleteFile($logoPath);
				}
				else
				{
					$deleted = true;
				}

				if ($deleted)
				{
					$update = $this->db->createQuery();
					$update->update($this->tableName)
						->set('logo = NULL')
						->where('id = ' .  $id);

					$this->db->setQuery($update)->execute();
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error on delete program logo : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.program');
			}
		}

		return $deleted;
	}

	public function getFactory(): ProgramFactory
	{
		return $this->factory;
	}
}