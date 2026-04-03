<?php

namespace Tchooz\Entities\Fabrik;

use Tchooz\Enums\Automation\ConditionTargetTypeEnum;

/**
 * Class ObservableElement
 * Used in plugin element_calculation context to represent an element that can be observed for changes
 *
 * @package Tchooz\Entities\Fabrik
 */
class ObservableElement
{
	public function __construct(
		public ConditionTargetTypeEnum $targetType = ConditionTargetTypeEnum::FORMDATA,
		public string                  $id,
		public string                  $name,
		public mixed                   $value,
		public int                     $groupId,
		public bool                    $inRepeatGroup = false,
	)
	{
	}

	public function getTargetType(): ConditionTargetTypeEnum
	{
		return $this->targetType;
	}

	public function setTargetType(ConditionTargetTypeEnum $targetType): self
	{
		$this->targetType = $targetType;

		return $this;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function setId(string $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getValue(): mixed
	{
		return $this->value;
	}

	public function setValue(mixed $value): self
	{
		$this->value = $value;

		return $this;
	}

	public function getGroupId(): int
	{
		return $this->groupId;
	}

	public function setGroupId(int $groupId): self
	{
		$this->groupId = $groupId;

		return $this;
	}

	public function isInRepeatGroup(): bool
	{
		return $this->inRepeatGroup;
	}

	public function setInRepeatGroup(bool $inRepeatGroup): self
	{
		$this->inRepeatGroup = $inRepeatGroup;

		return $this;
	}

	public function serialize(): array
	{
		return [
			'targetType'    => $this->targetType->value,
			'id'            => $this->id,
			'name'          => $this->name,
			'value'         => $this->value,
			'groupId'       => $this->groupId,
			'inRepeatGroup' => $this->inRepeatGroup
		];
	}
}