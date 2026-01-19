<?php

namespace Tchooz\Entities\Automation;

use Tchooz\Enums\Automation\ActionMessageTypeEnum;

class ActionExecutionMessage
{
	public ActionMessageTypeEnum $type = ActionMessageTypeEnum::INFO;
	public string $message;

	public function __construct(string $message, ActionMessageTypeEnum $type = ActionMessageTypeEnum::INFO)
	{
		$this->message = $message;
		$this->type    = $type;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getType(): ActionMessageTypeEnum
	{
		return $this->type;
	}
}