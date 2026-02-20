<?php

namespace Tchooz\Factories\Automation;

use Tchooz\Entities\Automation\ActionExecutionMessage;
use Tchooz\Enums\Automation\ActionMessageTypeEnum;

class ActionExecutionMessageFactory
{
	public static function fromArray(array $array): ?ActionExecutionMessage
	{
		$message = null;

		if (!empty($array) && isset($array['message']))
		{
			$message = new ActionExecutionMessage(
				$array['message'],
				isset($array['type']) ? ActionMessageTypeEnum::from($array['type']) : ActionMessageTypeEnum::INFO,
				isset($array['timestamp']) ? new \DateTimeImmutable($array['timestamp']) : null
			);
		}

		return $message;
	}
}