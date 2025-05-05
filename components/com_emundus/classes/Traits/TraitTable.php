<?php
/**
 * @package     Tchooz\Traits
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Traits;

trait TraitTable
{
	public function getTableName(string $class): string
	{
		$reflection = new \ReflectionClass($class);
		$attributes = $reflection->getAttributes('Tchooz\Attributes\TableAttribute');

		if (count($attributes) > 0)
		{
			return $attributes[0]->getArguments()['table'];
		}

		return '';
	}
}