<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Fields\YesnoField;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAfterSaveEmundusUserDefinition extends EventDefinition
{
	public function __construct()
	{
		// todo: add parameters
		parent::__construct('onAfterSaveEmundusUser', [
			new YesnoField('is_new', Text::_('COM_EMUNDUS_AUTOMATION_EVENT_FIELD_USER_IS_NEW'))
		]);
	}


	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::USER];
	}
}