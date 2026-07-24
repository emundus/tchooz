<?php

namespace Tchooz\Services\Addons\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Fields\TextAreaField;
use Tchooz\Entities\Fields\WysiwygField;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Services\Addons\EmundusAddonConfiguration;

class PollAddonConfiguration extends EmundusAddonConfiguration
{
	public function getParameters(): array
	{
		$configGroup = new FieldGroup('configuration', '');

		return [
			new StringField('run_email_subject', Text::_('COM_EMUNDUS_POLL_RUN_EMAIL_SUBJECT_TEMPLATE'), true, $configGroup),
			new WysiwygField('run_email_body', Text::_('COM_EMUNDUS_POLL_RUN_EMAIL_TEMPLATE'), true, $configGroup),
			new StringField('close_email_subject', Text::_('COM_EMUNDUS_POLL_CLOSE_EMAIL_SUBJECT_TEMPLATE'), true, $configGroup),
			new WysiwygField('close_email_body', Text::_('COM_EMUNDUS_POLL_CLOSE_EMAIL_TEMPLATE'), true, $configGroup)
		];
	}

	public function getDefaultParameters(): array
	{
		return [];
	}
}