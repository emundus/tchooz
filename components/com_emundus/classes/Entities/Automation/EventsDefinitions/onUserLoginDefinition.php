<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\User\AuthenticationModeEnum;
use Tchooz\Factories\Field\ChoiceFieldFactory;

class onUserLoginDefinition extends EventDefinition
{
	public function __construct()
	{
		$modes = ChoiceFieldFactory::makeOptionsFromEnum(AuthenticationModeEnum::cases());

		parent::__construct('onUserLogin', [
			new ChoiceField('mode', Text::_('COM_EMUNDUS_LOGIN_MODE'), $modes)
		]);
	}

	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::USER];
	}
}