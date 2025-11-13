<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onUserLoginDefinition extends EventDefinition
{
	public function __construct()
	{
		parent::__construct('onUserLogin', []);
	}

	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::USER];
	}
}