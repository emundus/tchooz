<?php
/**
 * @package     Tchooz\Services\Language\Objects
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Language\Objects\Definition;

class ObjectDefinitionFields
{
	private array $fields;

	private array $sections;

	public function __construct(array $fields = [], array $sections = [])
	{
		$this->fields = $fields;
		$this->sections = $sections;
	}

	public function getFields(): array
	{
		return $this->fields;
	}

	public function setFields(array $fields): void
	{
		$this->fields = $fields;
	}

	public function getSections(): array
	{
		return $this->sections;
	}

	public function setSections(array $sections): void
	{
		$this->sections = $sections;
	}

	public function __serialize(): array
	{
		return [
			'Fields'   => $this->fields,
			'Sections' => $this->sections,
		];
	}
}