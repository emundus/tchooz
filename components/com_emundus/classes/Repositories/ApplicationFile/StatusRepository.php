<?php
/**
 * @package     Tchooz\Repositories\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\ApplicationFile;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\ApplicationFile\StatusEntity;
use Tchooz\Factories\ApplicationFile\StatusFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: '#__emundus_setup_status',
	alias: 'ess',
	columns: ['id', 'step', 'value', 'ordering', 'class']
)]
class StatusRepository extends EmundusRepository implements RepositoryInterface
{
	private StatusFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'status', self::class);

		$this->factory = new StatusFactory();
	}

	public function delete(int $id): bool
	{
		// TODO: Implement delete() method.
	}

	public function getById(int $id): ?StatusEntity
	{
		$status_entity = null;

		$query = $this->db->getQuery(true)
			->select($this->columns)
			->from($this->tableName)
			->where($this->db->quoteName('id') . ' = ' . $id);
		$this->db->setQuery($query);
		$status = $this->db->loadObject();

		if (!empty($status)) {
			$status_entity = $this->factory->fromDbObject($status, $this->withRelations);
		}

		return $status_entity;
	}

	public function getByStep(int $step): ?StatusEntity
	{
		$status_entity = null;

		$query = $this->db->getQuery(true)
			->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->db->quoteName('step') . ' = ' . $step);
		$this->db->setQuery($query);
		$status = $this->db->loadObject();

		if (!empty($status)) {
			$status_entity = $this->factory->fromDbObject($status, $this->withRelations);
		}

		return $status_entity;
	}
}