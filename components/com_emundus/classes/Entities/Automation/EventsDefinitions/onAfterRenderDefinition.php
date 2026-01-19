<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Fields\YesnoField;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAfterRenderDefinition extends EventDefinition
{
	public const KEY_LOGGED_IN = 'guest';
	public function __construct()
	{
		parent::__construct('onAfterRender',
			[
				new YesnoField(self::KEY_LOGGED_IN, Text::_('COM_EMUNDUS_AUTOMATION_EVENT_FIELD_USER_LOGGED_IN'), true),
			]
		);
	}
	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::USER];
	}
}