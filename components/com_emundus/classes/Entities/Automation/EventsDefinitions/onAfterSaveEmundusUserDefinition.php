<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Fields\YesnoField;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAfterSaveEmundusUserDefinition extends EventDefinition
{
	public function __construct()
	{
		// todo: add parameters
		parent::__construct('onAfterSaveEmundusUser', []);
	}


	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::USER];
	}
}