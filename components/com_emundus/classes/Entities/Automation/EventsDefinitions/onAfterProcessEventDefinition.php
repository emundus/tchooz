<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Tchooz\Entities\Automation\EventsDefinitions\Defaults\FabrikFormEventDefinition;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAfterProcessEventDefinition extends Defaults\FabrikFormEventDefinition
{
	public const FORM_DATA_PARAMETER = 'form_data';

	public function __construct()
	{
		parent::__construct('onAfterProcess');
	}

	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::FILE, TargetTypeEnum::USER];
	}
}