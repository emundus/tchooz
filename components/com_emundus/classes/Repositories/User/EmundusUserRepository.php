<?php
/**
 * @package     Tchooz\Repositories\User
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\User;

use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Factories\User\EmundusUserFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: 'jos_emundus_users', alias: 't', columns: [
	'id',
	'user_id',
	'firstname',
	'lastname',
	'profile_picture',
	'user_category',
	'is_anonym'
])]
class EmundusUserRepository extends EmundusRepository implements RepositoryInterface
{
	use TraitTable;

	private EmundusUserFactory $factory;

	private const COLUMNS = [
		't.id',
		't.user_id',
		't.firstname',
		't.lastname',
		't.profile_picture',
		't.user_category',
		't.is_anonym'
	];

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

		try {
			if (empty($emundusUserEntity->getId()))
			{
				$insert = (object)[
					'user_id'       => $emundusUserEntity->getUser()->id,
					'firstname'     => $emundusUserEntity->getFirstname(),
					'lastname'      => $emundusUserEntity->getLastname(),
					'profile_picture' => $emundusUserEntity->getProfilePicture(),
					'user_category' => $emundusUserEntity->getUserCategory()?->getId(),
					'is_anonym'     => $emundusUserEntity->isAnonym() ? 1 : 0
				];

				if ($flushed = $this->db->insertObject($this->getTableName(self::class), $insert))
				{
					$emundusUserEntity->setId((int)$this->db->insertid());
				}
			}
			else
			{
				$update = (object)[
					'id'            => $emundusUserEntity->getId(),
					'user_id'       => $emundusUserEntity->getUser()->id,
					'firstname'     => $emundusUserEntity->getFirstname(),
					'lastname'      => $emundusUserEntity->getLastname(),
					'profile_picture' => $emundusUserEntity->getProfilePicture(),
					'user_category' => $emundusUserEntity->getUserCategory()?->getId(),
					'is_anonym'     => $emundusUserEntity->isAnonym() ? 1 : 0
				];

				$flushed = $this->db->updateObject($this->getTableName(self::class), $update, 'id');
			}
		} catch (\Exception $e) {
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

		$query = $this->db->getQuery(true);
		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->where('t.user_id = ' . $this->db->quote($user_id));
		$this->db->setQuery($query);
		$emundus_user = $this->db->loadAssoc();

		if (!empty($emundus_user)) {
			$emundus_user_entity = $this->factory->fromDbObject($emundus_user, $this->withRelations);
		}

		return $emundus_user_entity;
	}

	public function getByFnum(string $fnum): ?EmundusUserEntity
	{
		$emundus_user_entity = null;

		$query = $this->db->getQuery(true);
		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->leftJoin($this->db->quoteName($this->getTableName(ApplicationFileRepository::class), 'af') . ' ON af.applicant_id = t.user_id')
			->where('af.fnum = ' . $this->db->quote($fnum));
		$this->db->setQuery($query);
		$emundus_user = $this->db->loadAssoc();

		if (!empty($emundus_user)) {
			$emundus_user_entity = $this->factory->fromDbObject($emundus_user, $this->withRelations);
		}

		return $emundus_user_entity;
	}
}