<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class HubspotIntegrationConfiguration extends EmundusIntegrationConfiguration
{

	public function getParameters(): array
	{
		$authGroup = new FieldGroup('authentication', Text::_('COM_EMUNDUS_INTEGRATIONS_HUBSPOT_AUTHENTICATION_GROUP_LABEL'));
		$configGroup = new FieldGroup('configuration', Text::_('COM_EMUNDUS_INTEGRATIONS_HUBSPOT_CONFIGURATION_GROUP_LABEL'));

		return [
			new PasswordField('token', Text::_('COM_EMUNDUS_INTEGRATIONS_HUBSPOT_TOKEN_LABEL'), true, $authGroup),
			new StringField('base_url', Text::_('COM_EMUNDUS_INTEGRATIONS_HUBSPOT_BASE_URL_LABEL'), false, $configGroup),
		];
	}
}