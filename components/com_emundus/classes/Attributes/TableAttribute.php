<?php
/**
 * @package     Tchooz\Attributes
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Attributes;

#[\Attribute]
class TableAttribute
{
	public string $table;
	public string $alias;
	public array $columns;

	public function __construct(string $table, string $alias = '', array $columns = [])
	{
		$this->table = $table;
		$this->alias = $alias ?: $table;
		$this->columns = $columns;
	}
}