<?php
/**
 * @package     Tchooz\Services\Language\Objects
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Language\Objects\Definition;

class ObjectDefinition
{
	private ObjectDefinitionTable $table;

	private ObjectDefinitionFields $fields;

	public function __construct(ObjectDefinitionTable $table, ObjectDefinitionFields $fields)
	{
		$this->table = $table;
		$this->fields = $fields;
	}

	public function getTable(): ObjectDefinitionTable
	{
		return $this->table;
	}

	public function setTable(ObjectDefinitionTable $table): void
	{
		$this->table = $table;
	}

	public function getFields(): ObjectDefinitionFields
	{
		return $this->fields;
	}

	public function setFields(ObjectDefinitionFields $fields): void
	{
		$this->fields = $fields;
	}
}