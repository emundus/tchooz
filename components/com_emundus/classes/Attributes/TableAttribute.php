<?php
/**
 * @package     classes\Attributes
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace classes\Attributes;

#[\Attribute]
class TableAttribute
{
	public string $table;

	public function __construct(string $table)
	{
		$this->table = $table;
	}
}