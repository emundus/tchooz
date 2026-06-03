<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAskForAnonymousRevealDefinition extends EventDefinition
{
	public function __construct()
	{
		parent::__construct('onAskForAnonymousReveal', []);
	}


	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::FILE];
	}
}