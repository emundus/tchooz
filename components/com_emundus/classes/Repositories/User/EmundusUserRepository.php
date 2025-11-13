<?php
/**
 * @package     Tchooz\Repositories\User
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\User;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Factories\User\EmundusUserFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_users')]
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
		't.user_category'
	];

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'emundususer');

		$this->factory = new EmundusUserFactory();
	}

	public function flush($entity): mixed
	{
		// TODO: Implement flush() method.
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