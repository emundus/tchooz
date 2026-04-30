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

		return true;
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