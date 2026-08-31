<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAfterProgramCreateDefinition extends EventDefinition
{
	public CONST PROGRAM_ID_KEY = 'program_id';

	public CONST PROGRAM_CODE_KEY = 'program_code';

	public CONST PROGRAM_LABEL_KEY = 'program_label';

	public function __construct()
	{
		// The created program is not in any pre-existing list, so its identifiers are exposed as free
		// fields: conditions are written against the value carried by the event context.
		parent::__construct(
			'onAfterProgramCreate',
			[
				new NumericField(self::PROGRAM_ID_KEY, Text::_('COM_EMUNDUS_AUTOMATION_EVENT_FIELD_PROGRAM_ID')),
				new StringField(self::PROGRAM_CODE_KEY, Text::_('COM_EMUNDUS_AUTOMATION_EVENT_FIELD_PROGRAM_CODE')),
				new StringField(self::PROGRAM_LABEL_KEY, Text::_('COM_EMUNDUS_AUTOMATION_EVENT_FIELD_PROGRAM_LABEL')),
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::USER];
	}
}
