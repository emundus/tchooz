<?php
/**
 * @package     Tchooz\Repositories\Profile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Profile;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Entities\Profile\ProfileEntity;
use Tchooz\Factories\Groups\GroupFactory;
use Tchooz\Factories\Profile\ProfileFactory;
use Tchooz\Repositories\EmundusRepository;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');

#[TableAttribute(table: 'jos_emundus_setup_profiles', alias: 'esp', columns: [
	'id',
	'label',
	'description',
	'published',
	'menutype',
	'acl_aro_groups',
	'class'
])]
class ProfileRepository extends EmundusRepository
{
	private ProfileFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'profiles', self::class);

		$this->factory = new ProfileFactory();
	}

	public function getFactory(): ProfileFactory
	{
		return $this->factory;
	}

	public function flush(ProfileEntity $profile): bool
	{
		if(empty($profile->getLabel()))
		{
			throw new \InvalidArgumentException('Label is required');
		}

		$data = (object) [
			'label' => $profile->getLabel(),
			'description' => $profile->getDescription(),
			'published' => $profile->isPublished() ? 1 : 0,
			'menutype' => $profile->getMenutype(),
			'acl_aro_groups' => $profile->getAclAroGroups(),
			'class' => $profile->getClass()
		];

		if(empty($profile->getId()))
		{
			$data->id = $this->getNextFreeId();
			if (!$this->db->insertObject($this->tableName, $data))
			{
				throw new \RuntimeException('Error while inserting profile: ' . $this->db->getErrorMsg());
			}
			$profile->setId($this->db->insertid());
		}
		else
		{
			$data->id = $profile->getId();
			if (!$this->db->updateObject($this->tableName, $data, 'id'))
			{
				throw new \RuntimeException('Error while updating profile: ' . $this->db->getErrorMsg());
			}
		}

		// Setup profiles changed: invalidate com_emundus cache (getApplicantsProfiles)
		(new \EmundusHelperCache())->clean();

		return true;
	}

	/**
	 * Profiles deleted without cleanup (v1 platforms) leave menus, campaigns and documents keyed on their id:
	 * reusing that id would attach them to the new profile, so the next id skips every id still referenced.
	 */
	public function getNextFreeId(): int
	{
		$query = $this->db->getQuery(true);

		$query->select('MAX(id)')
			->from($this->db->quoteName($this->tableName));
		$this->db->setQuery($query);
		$maxProfileId = (int) $this->db->loadResult();

		$menutypeSuffix = 'CAST(SUBSTRING(menutype, ' . (strlen('menu-profile') + 1) . ') AS UNSIGNED)';
		$menutypeRegex  = $this->db->quote('^menu-profile[0-9]+$');
		$maxUsedIds     = [$maxProfileId];

		foreach (['#__menu_types', '#__menu'] as $table)
		{
			$query->clear()
				->select('MAX(' . $menutypeSuffix . ')')
				->from($this->db->quoteName($table))
				->where($this->db->quoteName('menutype') . ' REGEXP ' . $menutypeRegex);
			$this->db->setQuery($query);
			$maxUsedIds[] = (int) $this->db->loadResult();
		}

		foreach (['#__emundus_setup_campaigns', '#__emundus_setup_attachment_profiles', '#__emundus_setup_formlist'] as $table)
		{
			$query->clear()
				->select('MAX(profile_id)')
				->from($this->db->quoteName($table));
			$this->db->setQuery($query);
			$maxUsedIds[] = (int) $this->db->loadResult();
		}

		$nextId = max($maxUsedIds) + 1;

		if ($maxProfileId === 999 || $maxProfileId === 1000)
		{
			$nextId = max($nextId, 1001);
		}

		return $nextId;
	}

	public function getById(int $id): ?ProfileEntity
	{
		$cacheKey = 'profile_'.$id;
		if($this->cache->contains($cacheKey))
		{
			$profileObject = $this->cache->get($cacheKey);
		}

		if(empty($profileObject))
		{
			return $this->getItemByField('id', $id, true, []);
		}

		return $this->factory->fromDbObject($profileObject);
	}
}