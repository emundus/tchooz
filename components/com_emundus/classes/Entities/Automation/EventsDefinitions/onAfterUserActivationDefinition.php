<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAfterUserActivationDefinition extends EventDefinition
{
	public function __construct()
	{
		parent::__construct('onAfterUserActivation', []);
	}

	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::USER];
	}
}