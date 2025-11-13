<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onBeforeApplicantEnterApplicationDefinition extends EventDefinition
{
	public function __construct()
	{
		parent::__construct('onBeforeApplicantEnterApplication', []);
	}

	/**
	 * @inheritDoc
	 */
	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::FILE];
	}
}