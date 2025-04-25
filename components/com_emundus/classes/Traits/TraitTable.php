<?php
/**
 * @package     classes\Traits
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace classes\Traits;

trait TraitTable
{
	public function getTableName(string $class): string
	{
		$reflection = new \ReflectionClass($class);
		$attributes = $reflection->getAttributes('classes\Attributes\TableAttribute');

		if (count($attributes) > 0)
		{
			return $attributes[0]->getArguments()['table'];
		}

		return '';
	}
}