<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class SacemGedIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public function getParameters(): array
	{
		$authGroup = new FieldGroup('authentication', Text::_('COM_EMUNDUS_INTEGRATIONS_SACEM_GED_AUTHENTICATION_GROUP_LABEL'));
		$apiGroup  = new FieldGroup('api', Text::_('COM_EMUNDUS_INTEGRATIONS_SACEM_GED_API_GROUP_LABEL'));

		return [
			// Client credentials grant against the Sacem identity provider.
			new StringField('idp_token_url', Text::_('COM_EMUNDUS_INTEGRATIONS_SACEM_GED_IDP_TOKEN_URL_LABEL'), true, $authGroup),
			new StringField('idp_client_id', Text::_('COM_EMUNDUS_INTEGRATIONS_SACEM_GED_CLIENT_ID_LABEL'), true, $authGroup),
			new PasswordField('idp_client_secret', Text::_('COM_EMUNDUS_INTEGRATIONS_SACEM_GED_CLIENT_SECRET_LABEL'), true, $authGroup),

			// The base URL goes down to the API context, which differs per environment: the routes are
			// appended to it as `/api/partners/<partner>/...`.
			(new StringField('base_url', Text::_('COM_EMUNDUS_INTEGRATIONS_SACEM_GED_BASE_URL_LABEL'), true, $apiGroup))
				->setHelpText(Text::_('COM_EMUNDUS_INTEGRATIONS_SACEM_GED_BASE_URL_HELP')),

			// The partner id is part of every document route.
			new StringField('partner_id', Text::_('COM_EMUNDUS_INTEGRATIONS_SACEM_GED_PARTNER_ID_LABEL'), true, $apiGroup),
		];
	}

	public function getDefaultParameters(): array
	{
		return [];
	}
}
