<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class OvhIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public function getParameters(): array
	{
		$authGroup   = new FieldGroup('authentication', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_AUTHENTICATION_GROUP_LABEL'));

		return [
			new StringField('client_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_OVH_SETUP_CLIENT_ID'), true, $authGroup),
			new PasswordField('client_secret', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_OVH_SETUP_CLIENT_SECRET'), true, $authGroup),
			new StringField('consumer_key', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_OVH_SETUP_CONSUMER_KEY'), true, $authGroup),
		];
	}

	public function getDefaultParameters(): array
	{
		return [];
	}
}