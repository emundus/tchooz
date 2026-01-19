<?php

namespace Tchooz\Entities\Mapping;

use Tchooz\Enums\Mapping\MappingTransformersEnum;

class MappingTransformEntity
{
	private int $id = 0;

	private int $mappingRowId = 0;

	private int $order = 0;

	private MappingTransformersEnum $type;

	private array $parameters = [];

	public function __construct(int $id = 0, int $mappingRowId = 0, int $order = 0, MappingTransformersEnum $type = MappingTransformersEnum::TRIM, array $parameters = [])
	{
		$this->id = $id;
		$this->mappingRowId = $mappingRowId;
		$this->order = $order;
		$this->type = $type;
		$this->parameters = $parameters;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getMappingRowId(): int
	{
		return $this->mappingRowId;
	}

	public function setMappingRowId(int $mappingRowId): void
	{
		$this->mappingRowId = $mappingRowId;
	}

	public function getOrder(): int
	{
		return $this->order;
	}

	public function setOrder(int $order): void
	{
		$this->order = $order;
	}

	public function getType(): MappingTransformersEnum
	{
		return $this->type;
	}

	public function setType(MappingTransformersEnum $type): void
	{
		$this->type = $type;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function setParameters(array $parameters): void
	{
		$this->parameters = $parameters;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'mapping_row_id' => $this->mappingRowId,
			'order' => $this->order,
			'type' => $this->type->value,
			'parameters' => $this->parameters,
		];
	}
}