<?php

namespace Tchooz\Entities\Mapping;

use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;

class MappingRowEntity
{
	private int $id = 0;

	private int $mappingId = 0;

	private int $order = 0;

	private ConditionTargetTypeEnum $sourceType;

	private string $sourceField = '';

	private string $targetField = '';

	/**
	 * @var array<MappingTransformEntity>
	 */
	private array $transformations = [];

	public function __construct(int $id, int $mappingId = 0, int $order = 0, ConditionTargetTypeEnum $sourceType = ConditionTargetTypeEnum::FORMDATA, string $sourceField = '', string $targetField = '', array $transformations = [])
	{
		$this->id = $id;
		$this->mappingId = $mappingId;
		$this->order = $order;
		$this->sourceType = $sourceType;
		$this->sourceField = $sourceField;
		$this->targetField = $targetField;
		$this->setTransformations($transformations);
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getMappingId(): int
	{
		return $this->mappingId;
	}

	public function setMappingId(int $mappingId): void
	{
		$this->mappingId = $mappingId;
	}

	public function getOrder(): int
	{
		return $this->order;
	}

	public function setOrder(int $order): void
	{
		$this->order = $order;
	}

	public function getSourceType(): ConditionTargetTypeEnum
	{
		return $this->sourceType;
	}

	public function setSourceType(ConditionTargetTypeEnum $sourceType): void
	{
		$this->sourceType = $sourceType;
	}

	public function getSourceField(): string
	{
		return $this->sourceField;
	}

	public function setSourceField(string $sourceField): void
	{
		$this->sourceField = $sourceField;
	}

	public function getTargetField(): string
	{
		return $this->targetField;
	}

	public function setTargetField(string $targetField): void
	{
		$this->targetField = $targetField;
	}

	/**
	 * @return array<MappingTransformEntity>
	 */
	public function getTransformations(): array
	{
		return $this->transformations;
	}

	/**
	 * @param   array<MappingTransformEntity>  $transformations
	 *
	 * @return void
	 */
	public function setTransformations(array $transformations): void
	{
		foreach ($transformations as $transformation)
		{
			assert($transformation instanceof MappingTransformEntity);
		}

		$this->transformations = $transformations;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'mapping_id' => $this->mappingId,
			'order' => $this->order,
			'source_type' => $this->sourceType->value,
			'source_field' => $this->sourceField,
			'target_field' => $this->targetField,
			'transformations' => array_map(fn (MappingTransformEntity $transformation) => $transformation->serialize(), $this->transformations),
		];
	}
}