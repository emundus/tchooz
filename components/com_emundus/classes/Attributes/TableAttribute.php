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

	public function __construct(string $table, string $alias = '')
	{
		$this->table = $table;
		$this->alias = $alias ?: $table;
	}
}