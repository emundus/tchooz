<?php

require_once(__DIR__ . '/EmundusColumnTypeEnum.php');

readonly class EmundusTableColumn
{
	public function __construct(
		private string                $name,
		private EmundusColumnTypeEnum $type = EmundusColumnTypeEnum::VARCHAR,
		private ?int                  $length = null,
		private bool                  $isNullable = false,
		private mixed                 $default = null,
		private string                $comment = ''
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getType(): EmundusColumnTypeEnum
	{
		return $this->type;
	}

	public function getLength(): ?int
	{
		return $this->length;
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

	public function getDefault(): mixed
	{
		return $this->default;
	}

	public function getComment(): string
	{
		return $this->comment;
	}
}