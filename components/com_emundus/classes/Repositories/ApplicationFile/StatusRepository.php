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

#[TableAttribute(table: 'jos_emundus_setup_status', alias: 'ess', columns: [
	'id',
	'step',
	'value',
	'ordering',
	'class'
])]
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

	/**
	 *
	 * @return array<StatusEntity>
	 */
	public function getAll(): array
	{
		$statuses = [];

		$query = $this->db->getQuery(true)
			->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->order($this->db->quoteName('ordering') . ' ASC');
		$this->db->setQuery($query);
		$results = $this->db->loadObjectList();

		foreach ($results as $status) {
			$statuses[] = $this->factory->fromDbObject($status, $this->withRelations);
		}

		return $statuses;
	}

	public function getById(int $id): ?StatusEntity
	{
		return $this->getItemByField('id', $id, true);
	}

	public function getByStep(int $step): ?StatusEntity
	{
		return $this->getItemByField('step', $step, true);
	}

	public function getFactory(): StatusFactory
	{
		return $this->factory;
	}
}