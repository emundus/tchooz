<?php
/**
 * @package     Tchooz\Repositories\User
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\User;

use Joomla\CMS\Cache\CacheController;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\List\ListResult;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Factories\User\EmundusUserFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Services\ExtensionService;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: 'jos_emundus_users', alias: 'eu', columns: [
	'id',
	'user_id',
	'firstname',
	'lastname',
	'profile_picture',
	'user_category',
	'is_anonym',
	'birth_date'
])]
class EmundusUserRepository extends EmundusRepository implements RepositoryInterface
{
	use TraitTable;

	private EmundusUserFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'emundususer', self::class);

		$this->factory = new EmundusUserFactory();
	}

	public function flush(EmundusUserEntity $emundusUserEntity): bool
	{
		$flushed = false;

		if (empty($emundusUserEntity->getUser()) || empty($emundusUserEntity->getUser()->id))
		{
			throw new \Exception('EmundusUserEntity must have a valid User associated to flush.');
		}

		$data = (object)[
			'user_id'       => $emundusUserEntity->getUser()->id,
			'firstname'     => $emundusUserEntity->getFirstname(),
			'lastname'      => $emundusUserEntity->getLastname(),
			'profile_picture' => $emundusUserEntity->getProfilePicture(),
			'user_category' => $emundusUserEntity->getUserCategory()?->getId(),
			'is_anonym'     => $emundusUserEntity->isAnonym() ? 1 : 0,
			'birth_date'      => $emundusUserEntity->getBirthDate()?->format('d/m/Y')
		];

		try {
			if (empty($emundusUserEntity->getId()))
			{
				if ($flushed = $this->db->insertObject($this->tableName, $data))
				{
					$emundusUserEntity->setId((int) $this->db->insertid());
				}
			}
			else
			{
				$data->id = $emundusUserEntity->getId();

				$flushed = $this->db->updateObject($this->tableName, $data, 'id');
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error flushing EmundusUserEntity: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.emundususer');
		}

		return $flushed;
	}

	public function delete(int $id): bool
	{
		// TODO: Implement delete() method.
	}

	public function getById(int $id): mixed
	{
		// TODO: Implement getById() method.
	}

	public function getByUserId(int $user_id): ?EmundusUserEntity
	{
		$emundus_user_entity = null;

		$emundus_user = $this->getItemByField('user_id', $user_id);

		if (!empty($emundus_user))
		{
			$emundus_user_entity = $this->factory->fromDbObject($emundus_user, $this->withRelations);
		}

		return $emundus_user_entity;
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return EmundusUserEntity|null
	 */
	public function getByFnum(string $fnum): ?EmundusUserEntity
	{
		$emundus_user_entity = null;

		$query = $this->db->getQuery(true);
		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->leftJoin($this->db->quoteName($this->getTableName(ApplicationFileRepository::class), 'af') . ' ON af.applicant_id = ' . $this->alias . '.user_id')
			->where('af.fnum = ' . $this->db->quote($fnum));
		$this->db->setQuery($query);
		$emundus_user = $this->db->loadObject();

		if (!empty($emundus_user))
		{
			$emundus_user_entity = $this->factory->fromDbObject($emundus_user, $this->withRelations);
		}

		return $emundus_user_entity;
	}

	public function associateGroup(int $groupId, int $userId): bool
	{
		$associated = false;

		try {
			$data = (object) [
				'group_id' => $groupId,
				'user_id' => $userId
			];

			$associated = $this->db->insertObject('#__emundus_groups', $data);
		} catch (\Exception $e) {
			Log::add('Error associating user ' . $userId . ' to group ' . $groupId . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.emundususer');
		}

		return $associated;
	}

	/**
	 * @param   int  $user_id
	 *
	 * @return array<string>
	 */
	public function getUserProgramsCodes(int $user_id): array
	{
		$codes = [];

		$cacheKey = 'emundus_user_programs_' . $user_id;
		if ($this->cache && $this->cache->contains($cacheKey))
		{
			return $this->cache->get($cacheKey);
		}

		if (!empty($user_id))
		{
			$query = $this->db->getQuery(true);

			$query->select('sp.code')
				->from($this->db->quoteName('#__emundus_groups', 'g'))
				->leftJoin($this->db->quoteName('#__emundus_setup_groups', 'sg') . ' ON ' . $this->db->quoteName('g.group_id') . ' = ' . $this->db->quoteName('sg.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'sgr') . ' ON ' . $this->db->quoteName('sg.id') . ' = ' . $this->db->quoteName('sgr.parent_id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'sp') . ' ON ' . $this->db->quoteName('sgr.course') . ' = ' . $this->db->quoteName('sp.code'))
				->where($this->db->quoteName('g.user_id') . ' = ' . $this->db->quote($user_id));

			try
			{
				$this->db->setQuery($query);
				$programs = $this->db->loadColumn();

				$codes = array_filter(array_unique($programs));

				if (!empty($codes) && $this->cache)
				{
					$this->cache->store($codes, $cacheKey);
				}
			}
			catch (\Exception $e)
			{
				Log::add('component/com_emundus/models/program | Error at getting programs of the user ' . $user_id . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $codes;
	}

	/**
	 * @param   int  $user_id
	 *
	 * @return array<int>
	 */
	public function getUserProgramsIds(int $user_id): array
	{
		$ids = [];

		$cacheKey = 'emundus_user_programs_ids_' . $user_id;
		if ($this->cache && $this->cache->contains($cacheKey))
		{
			return $this->cache->get($cacheKey);
		}

		if (!empty($user_id))
		{
			$query = $this->db->getQuery(true);

			$query->select('sp.id')
				->from($this->db->quoteName('#__emundus_groups', 'g'))
				->leftJoin($this->db->quoteName('#__emundus_setup_groups', 'sg') . ' ON ' . $this->db->quoteName('g.group_id') . ' = ' . $this->db->quoteName('sg.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'sgr') . ' ON ' . $this->db->quoteName('sg.id') . ' = ' . $this->db->quoteName('sgr.parent_id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'sp') . ' ON ' . $this->db->quoteName('sgr.course') . ' = ' . $this->db->quoteName('sp.code'))
				->where($this->db->quoteName('g.user_id') . ' = ' . $this->db->quote($user_id));

			try
			{
				$this->db->setQuery($query);
				$programs = $this->db->loadColumn();

				$ids = array_filter(array_unique($programs));

				if (!empty($ids) && $this->cache)
				{
					$this->cache->store($ids, $cacheKey);
				}
			}
			catch (\Exception $e)
			{
				Log::add('component/com_emundus/models/program | Error at getting programs of the user ' . $user_id . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		$ids = array_unique($ids);
		$ids = array_map('intval', $ids);

		return $ids;
	}

	/**
	 * @param   string  $search
	 * @param   int     $limit
	 *
	 * @return array<EmundusUserEntity>
	 * @throws \Exception
	 */
	public function getApplicants(string $search = '', int $limit = 0): array
	{
		$applicants = [];

		try
		{
			$query = $this->db->getQuery(true);
			$query->select($this->columns)
				->from($this->db->quoteName($this->tableName, $this->alias))
				->leftJoin($this->db->quoteName($this->getTableName(ApplicationFileRepository::class), 'af') . ' ON ' . $this->db->quoteName('af.applicant_id') . ' = ' . $this->db->quoteName($this->alias . '.user_id'))
				->where($this->db->quoteName('af.published') . ' = 1');
			if (!empty($search))
			{
				$searchEscaped = $this->db->quote('%' . $this->db->escape($search, true) . '%');
				$query->where('(' . $this->db->quoteName($this->alias . '.firstname') . ' LIKE ' . $searchEscaped
					. ' OR ' . $this->db->quoteName($this->alias . '.lastname') . ' LIKE ' . $searchEscaped
					. ' OR ' . $this->db->quoteName($this->alias . '.user_id') . ' LIKE ' . $searchEscaped
					. ')');
			}

			$this->db->setQuery($query, 0, $limit);
			$applicantsObjects = $this->db->loadObjectList();

			if (!empty($applicantsObjects))
			{
				$applicants = $this->factory->fromDbObjects($applicantsObjects, $this->withRelations);
			}
		}
		catch (\Exception $e)
		{
			throw new \Exception('Error fetching applicants: ' . $e->getMessage());
		}

		return $applicants;
	}

	/**
	 * @param   string  $sort
	 * @param   string  $search
	 * @param   int     $lim
	 * @param   int     $page
	 * @param   string  $order_by
	 *
	 * @return ListResult
	 * @throws \Exception
	 */
	public function getExceptions(
		string $sort = 'DESC',
		string $search = '',
		int    $lim = 25,
		int    $page = 0,
		string $order_by = 'eu.id',
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

		$exceptions = [];

		try
		{
			$query = $this->db->createQuery();
			$query->select('DISTINCT ' . $this->alias . '.id')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->leftJoin($this->db->quoteName('#__emundus_setup_exceptions', 'ese') . ' ON ' . $this->db->quoteName('ese.user') . ' = ' . $this->db->quoteName($this->alias . '.user_id'))
				->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName($this->alias . '.user_id'))
				->where($this->db->quoteName('ese.id') . ' IS NOT NULL');

			if (!empty($search))
			{
				$searchEscaped = $this->db->quote('%' . $this->db->escape($search, true) . '%');
				$query->where('(' . $this->db->quoteName($this->alias . '.firstname') . ' LIKE ' . $searchEscaped
					. ' OR ' . $this->db->quoteName($this->alias . '.lastname') . ' LIKE ' . $searchEscaped
					. ' OR ' . $this->db->quoteName($this->alias . '.user_id') . ' LIKE ' . $searchEscaped
					. ')');
			}
			$sort = strtoupper($sort) === 'ASC' ? 'ASC' : 'DESC';
			$query->order($this->db->quoteName($order_by) . ' ' . $sort);

			$this->db->setQuery($query);
			$exceptions_count = sizeof($this->db->loadObjectList());

			$query->select($this->columns);
			$this->db->setQuery($query, $offset, $limit);
			$exceptionsObjects = $this->db->loadObjectList();

			if (!empty($exceptionsObjects))
			{
				$exceptions = $this->factory->fromDbObjects($exceptionsObjects, $this->withRelations);
			}

			$result->setItems($exceptions);
			$result->setTotalItems($exceptions_count);
		}
		catch (\Exception $e)
		{
			throw new \Exception('Error fetching exceptions: ' . $e->getMessage());
		}

		return $result;
	}

	public function getExceptionByUserId(int $user_id): ?object
	{
		$exception = null;

		try
		{
			$query = $this->db->getQuery(true);
			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_exceptions'))
				->where($this->db->quoteName('user') . ' = ' . $this->db->quote($user_id));
			$this->db->setQuery($query);
			$exception = $this->db->loadObject();
		}
		catch (\Exception $e)
		{
			Log::add('Error fetching exception for user ' . $user_id . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.emundususer');
		}

		return $exception;
	}

	/**
	 * @param   int  $user_id
	 *
	 * @return bool
	 */
	public function addException(int $user_id): bool
	{
		$added = false;

		try
		{
			$exceptionParameters = ExtensionService::getParamValue('com_emundus', 'id_applicants', []);
			if (!empty($exceptionParameters) && !is_array($exceptionParameters))
			{
				$exceptionParameters = explode(',', $exceptionParameters);
			}

			// First check if not already exist
			$exceptionExist = $this->getExceptionByUserId($user_id);

			if (empty($exceptionExist))
			{
				$insert = (object) [
					'date_time' => Factory::getDate()->toSql(),
					'user'      => $user_id
				];

				$added = $this->db->insertObject('#__emundus_setup_exceptions', $insert);
			}
			else
			{
				// Already exist, consider as added
				$added = true;
			}

			if ($added && !in_array($user_id, $exceptionParameters))
			{
				$exceptionParameters[] = $user_id;
				ExtensionService::updateExtensionParam('com_emundus', 'id_applicants', implode(',', $exceptionParameters));
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error adding exception for user ' . $user_id . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.emundususer');
		}

		return $added;
	}

	/**
	 * @param   array<int>  $ids
	 *
	 * @return bool
	 */
	public function deleteExceptions(array $ids): bool
	{
		$deleted = false;

		try
		{
			$exceptionParameters = ExtensionService::getParamValue('com_emundus', 'id_applicants', []);
			if (!empty($exceptionParameters) && !is_array($exceptionParameters))
			{
				$exceptionParameters = explode(',', $exceptionParameters);
			}

			$query = $this->db->getQuery(true);
			$query->delete($this->db->quoteName('#__emundus_setup_exceptions'))
				->where($this->db->quoteName('user') . ' IN (' . implode(',', array_map([$this->db, 'quote'], $ids)) . ')');
			$this->db->setQuery($query);
			$deleted = $this->db->execute();

			if ($deleted)
			{
				foreach ($ids as $id)
				{
					if (in_array($id, $exceptionParameters))
					{
						$exceptionParameters = array_diff($exceptionParameters, [$id]);
					}
				}

				ExtensionService::updateExtensionParam('com_emundus', 'id_applicants', implode(',', $exceptionParameters));
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error deleting exceptions with ids ' . implode(',', $ids) . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.emundususer');
		}

		return $deleted;
	}

	/**
	 * @param   int  $groupId
	 *
	 * @return array<EmundusUserEntity>
	 */
	public function getUsersByGroup(int $groupId): array
	{
		$users = [];

		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->leftJoin($this->db->quoteName('#__emundus_groups', 'eg') . ' ON eg.user_id = '.$this->alias.'.user_id')
			->where('eg.group_id = ' . $groupId);
		$this->db->setQuery($query);
		$users = $this->db->loadObjectList();

		if(!empty($users))
		{
			$users = $this->factory->fromDbObjects($users, $this->withRelations);
		}

		return $users;
	}

	/**
	 * Get all applicants (users having a profile associated to an active campaign)
	 *
	 * @return EmundusUserEntity[]
	 */
	public function getAllApplicants(int $limit = 30, string $search = ''): array
	{
		$emundus_user_entities = [];

		// Get applicants profiles
		$query = $this->db->getQuery(true);

		$query->select('id')
			->from($this->db->quoteName('#__emundus_setup_profiles', 'esp'))
			->where('esp.published = 1');
		$this->db->setQuery($query);
		$applicant_profiles = $this->db->loadColumn();

		$query->clear()
			->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->leftJoin($this->db->quoteName('#__users', 'u') . ' ON u.id = '.$this->alias.'.user_id')
			->leftJoin($this->db->quoteName('#__emundus_users_profiles', 'eup') . ' ON eup.user_id = '.$this->alias.'.user_id')
			->where('eup.profile_id IN (' . implode(',', $applicant_profiles) . ')')
			->orWhere($this->alias.'.profile IN (' . implode(',', $applicant_profiles) . ')')
			->group($this->alias.'.id');

		if(!empty($search)) {
			$search = $this->db->quote('%' . $this->db->escape($search, true) . '%');
			$query->andWhere('(' . $this->alias.'.firstname LIKE ' . $search . ' OR ' . $this->alias.'.lastname LIKE ' . $search . ' OR u.email LIKE ' . $search . ')');
		}

		$this->db->setQuery($query, 0, $limit);
		$emundus_users = $this->db->loadAssocList();

		if (!empty($emundus_users)) {
			foreach ($emundus_users as $emundus_user) {
				$emundus_user_entities[] = $this->factory->fromDbObject($emundus_user, $this->withRelations);
			}
		}

		return $emundus_user_entities;
	}
}