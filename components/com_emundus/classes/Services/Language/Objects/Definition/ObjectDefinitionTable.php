<?php
/**
 * @package     Tchooz\Services\Language\Objects
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Language\Objects\Definition;

class ObjectDefinitionTable
{
	private string $name;

	private string $reference;

	private string $label;

	private array $filters;

	private bool $loadAll;

	private string $type;

	private bool $loadFirstData;

	private bool $loadFirstChild;

	public function __construct(
		string $name,
		string $reference,
		string $label,
		array  $filters = [],
		bool   $loadAll = false,
		string $type = 'standard',
		bool   $loadFirstData = false,
		bool   $loadFirstChild = false
	)
	{
		$this->name           = $name;
		$this->reference      = $reference;
		$this->label          = $label;
		$this->filters        = $filters;
		$this->loadAll        = $loadAll;
		$this->type           = $type;
		$this->loadFirstData  = $loadFirstData;
		$this->loadFirstChild = $loadFirstChild;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getReference(): string
	{
		return $this->reference;
	}

	public function setReference(string $reference): void
	{
		$this->reference = $reference;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getFilters(): array
	{
		return $this->filters;
	}

	public function setFilters(array $filters): void
	{
		$this->filters = $filters;
	}

	public function isLoadAll(): bool
	{
		return $this->loadAll;
	}

	public function setLoadAll(bool $loadAll): void
	{
		$this->loadAll = $loadAll;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): void
	{
		$this->type = $type;
	}

	public function isLoadFirstData(): bool
	{
		return $this->loadFirstData;
	}

	public function setLoadFirstData(bool $loadFirstData): void
	{
		$this->loadFirstData = $loadFirstData;
	}

	public function isLoadFirstChild(): bool
	{
		return $this->loadFirstChild;
	}

	public function setLoadFirstChild(bool $loadFirstChild): void
	{
		$this->loadFirstChild = $loadFirstChild;
	}

	public function __serialize(): array
	{
		return [
			'name'             => $this->name,
			'reference'        => $this->reference,
			'label'            => $this->label,
			'filters'          => implode(',', $this->filters),
			'load_all'         => $this->loadAll ? 'true' : 'false',
			'type'             => $this->type,
			'load_first_data'  => $this->loadFirstData ? 'true' : 'false',
			'load_first_child' => $this->loadFirstChild ? 'true' : 'false',
		];
	}
}