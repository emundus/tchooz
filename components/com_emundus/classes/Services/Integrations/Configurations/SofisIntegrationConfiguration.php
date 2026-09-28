<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class SofisIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public function getParameters(): array
	{
		$authGroup = new FieldGroup('authentication', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_AUTHENTICATION_GROUP_LABEL'));

		return [
			// Step 1 — authorization server (IDAMA token via client_credentials + Basic auth)
			new StringField('idp_token_url', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_IDP_TOKEN_URL_LABEL'), true, $authGroup),
			new StringField('idp_client_id', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_IDP_CLIENT_ID_LABEL'), true, $authGroup),
			new PasswordField('idp_secret', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_IDP_SECRET_LABEL'), true, $authGroup),

			// Step 2 — EntraId token exchange (IDAMA token as client_assertion)
			new StringField('token_endpoint', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_TOKEN_ENDPOINT_LABEL'), true, $authGroup),
			new StringField('entra_client_id', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_ENTRA_CLIENT_ID_LABEL'), true, $authGroup),
			new StringField('resource', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_RESOURCE_LABEL'), true, $authGroup),
			new StringField('scope', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_SCOPE_LABEL'), false, $authGroup),
		];
	}

	public function getDefaultParameters(): array
	{
		return [];
	}
}
