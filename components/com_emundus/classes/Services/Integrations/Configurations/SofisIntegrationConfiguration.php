<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;
use Tchooz\Entities\Fields\BooleanField;

class SofisIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public function getParameters(): array
	{
		$authGroup       = new FieldGroup('authentication', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_AUTHENTICATION_GROUP_LABEL'));
		$diagnosticGroup = new FieldGroup('diagnostic', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_DIAGNOSTIC_GROUP_LABEL'));

		// Connection fields all stay in the `authentication` group: it is the storage key read by SofisAuthenticator and SofisSynchronizer.
		return [
			// Step 1 — IDAMA token (client_credentials + Basic auth)
			new StringField('idp_token_url', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_IDP_TOKEN_URL_LABEL'), true, $authGroup),
			new StringField('idp_client_id', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_IDP_CLIENT_ID_LABEL'), true, $authGroup),
			new PasswordField('idp_secret', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_IDP_SECRET_LABEL'), true, $authGroup),

			// Step 2 — Entra ID token exchange through the MARIO gateway (IDAMA token as client_assertion)
			new StringField('token_endpoint', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_TOKEN_ENDPOINT_LABEL'), true, $authGroup),
			new StringField('entra_client_id', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_ENTRA_CLIENT_ID_LABEL'), true, $authGroup),
			(new StringField('resource', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_RESOURCE_LABEL'), true, $authGroup))
				->setHelpText(Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_RESOURCE_HELP')),

			// Step 3 — Sofis APIs through the MARIO gateway (never the Dynamics instance directly)
			(new StringField('base_url', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_BASE_URL_LABEL'), true, $authGroup))
				->setHelpText(Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_BASE_URL_HELP')),

			// Optional token exchange parameter
			new StringField('scope', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_SCOPE_LABEL'), false, $authGroup),

			(new BooleanField('debug', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_DEBUG_LABEL'), false, $diagnosticGroup))
				->setHelpText(Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_DEBUG_HELP')),
		];
	}

	public function getDefaultParameters(): array
	{
		return [
			'diagnostic' => [
				'debug' => 0,
			],
		];
	}
}
