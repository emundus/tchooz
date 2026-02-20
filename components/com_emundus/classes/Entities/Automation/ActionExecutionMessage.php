<?php

namespace Tchooz\Entities\Automation;

use Tchooz\Enums\Automation\ActionMessageTypeEnum;

class ActionExecutionMessage
{
	public ActionMessageTypeEnum $type = ActionMessageTypeEnum::INFO;
	public string $message;
	private ?\DateTimeImmutable $timestamp = null;

	public function __construct(string $message, ActionMessageTypeEnum $type = ActionMessageTypeEnum::INFO, ?\DateTimeImmutable $timestamp = null)
	{
		$this->message = $message;
		$this->type    = $type;
		$this->timestamp = new \DateTimeImmutable();
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getType(): ActionMessageTypeEnum
	{
		return $this->type;
	}

	public function getTimestamp(): \DateTimeImmutable
	{
		return $this->timestamp ?? new \DateTimeImmutable();
	}

	public function serialize(): array
	{
		return [
			'type' => $this->type->value,
			'message' => $this->message,
			'timestamp' => $this->timestamp?->format('Y-m-d H:i:s'),
		];
	}
}