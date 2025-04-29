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

	public function __construct(string $table)
	{
		$this->table = $table;
	}
}