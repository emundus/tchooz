<?php
/**
 * @package     Tchooz\Repositories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Tchooz\Traits\TraitTable;

class EmundusRepository
{
	use TraitTable;

	protected bool $withRelations;
	protected array $exceptRelations = [];

	protected DatabaseInterface $db;

	protected string $tableName = '';
	protected string $primaryKey = 'id';
	protected string $alias = 't';
	protected array $columns = [];

	public function __construct(
		$withRelations = true,
		$exceptRelations = [],
		$name = '',
		$className = self::class
	)
	{
		$this->db              = Factory::getContainer()->get('DatabaseDriver');

		$this->tableName = $this->getTableName($className);
		$this->alias 	 = $this->getTableAlias($className);
		$this->columns   = $this->getTableColumns($className);

		$this->withRelations   = $withRelations;
		$this->exceptRelations = $exceptRelations;

		if (!empty($name))
		{
			Log::addLogger(['text_file' => "com_emundus.repository.{$name}.php"], Log::ALL, ["com_emundus.repository.{$name}"]);
		}
	}
}