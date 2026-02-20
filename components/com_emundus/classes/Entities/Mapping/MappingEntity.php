<?php

namespace Tchooz\Entities\Mapping;

class MappingEntity
{
	private int $id = 0;

	private string $label = '';

	private int $synchronizerId = 0;

	private string $targetObject = '';

	private ?array $params = [];

	/**
	 * @var array<MappingRowEntity>
	 */
	private array $rows = [];

	public function __construct(int $id = 0, string $label = '', int $synchronizerId = 0, string $targetObject = '', array $params = [], array $rows = [])
	{
		$this->id = $id;
		$this->label = $label;
		$this->synchronizerId = $synchronizerId;
		$this->targetObject = $targetObject;
		$this->params = $params;
		$this->rows = $rows;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getSynchronizerId(): int
	{
		return $this->synchronizerId;
	}

	public function setSynchronizerId(int $synchronizerId): void
	{
		$this->synchronizerId = $synchronizerId;
	}

	public function getTargetObject(): string
	{
		return $this->targetObject;
	}

	public function setTargetObject(string $targetObject): void
	{
		$this->targetObject = $targetObject;
	}

	public function getParams(): ?array
	{
		return $this->params;
	}

	public function setParams(?array $params): void
	{
		$this->params = $params;
	}

	/**
	 * @return array<MappingRowEntity>
	 */
	public function getRows(): array
	{
		return $this->rows;
	}

	/**
	 * @param   array<MappingRowEntity>  $rows
	 */
	public function setRows(array $rows): void
	{
		$this->rows = $rows;
	}

	public function addRow(MappingRowEntity $row): void
	{
		$this->rows[] = $row;
	}

	public function serialize(): array
	{
		return [
			'id'              => $this->getId(),
			'label'           => $this->getLabel(),
			'synchronizer_id' => $this->getSynchronizerId(),
			'target_object'   => $this->getTargetObject(),
			'params'          => $this->getParams(),
			'rows'            => array_map(fn($row) => $row->serialize(), $this->getRows()),
		];
	}
}