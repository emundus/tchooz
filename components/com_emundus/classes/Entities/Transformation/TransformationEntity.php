<?php

namespace Tchooz\Entities\Transformation;

use Tchooz\Enums\Mapping\MappingTransformersEnum;

// TODO: replace MappingTransformationEntity
class TransformationEntity
{
	private int $id;

	private MappingTransformersEnum $type;

	/**
	 * @var array $parameters
	 */
	private array $parameters;

	public function __construct(int $id, MappingTransformersEnum $type, array $parameters)
	{
		$this->id = $id;
		$this->type = $type;
		$this->parameters = $parameters;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): MappingTransformersEnum
	{
		return $this->type;
	}

	/**
	 * @return array
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function setType(MappingTransformersEnum $type): self
	{
		$this->type = $type;

		return $this;
	}

	/**
	 * @param array$parameters
	 * @return self
	 */
	public function setParameters(array $parameters): self
	{
		$this->parameters = $parameters;

		return $this;
	}
}