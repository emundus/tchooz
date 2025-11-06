<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAfterDeleteFileDefinition extends EventDefinition
{
	public function __construct()
	{
		parent::__construct('onAfterDeleteFile', []);
	}


	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::FILE, TargetTypeEnum::USER];
	}
}