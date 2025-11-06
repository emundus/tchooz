<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Tchooz\Entities\Automation\EventsDefinitions\Defaults\FabrikFormEventDefinition;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onBeforeLoadEventDefinition extends Defaults\FabrikFormEventDefinition
{
	public function __construct()
	{
		parent::__construct('onBeforeLoad');
	}

	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::FILE, TargetTypeEnum::USER];
	}
}