<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class MicrosoftDynamicsIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public function getParameters(): array
	{
		$authGroup   = new FieldGroup('authentication', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_AUTHENTICATION_GROUP_LABEL'));

		return [
			new StringField('domain', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_DOMAIN'), true, $authGroup),
			new StringField('client_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_CLIENT_ID'), true, $authGroup),
			new PasswordField('client_secret', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_CLIENT_SECRET'), true, $authGroup),
			new StringField('tenant_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_TENANT_ID'), true, $authGroup),
		];
	}

	public function getDefaultParameters(): array
	{
		return [
			'base_url'        => '',
			'api_url'        => 'api/data/v9.2',
			'authentication' => [
				'route'            => '',
				'method'           => 'post',
				'grant_type'       => 'client_credentials',
				'scope'            => '',
				'domain'            => '',
				'type'             => 'bearer',
				'create_token'     => true,
				'token_attribute'  => 'access_token',
				'token_storage'    => 'database',
				'token_validity'   => 3600,
				'token'            => '',
				'token_expiration' => '',
				'content_type'     => 'form_params',
			],
		];
	}
}