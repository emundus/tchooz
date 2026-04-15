<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class TeamsIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public function getParameters(): array
	{
		$authGroup   = new FieldGroup('authentication', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_AUTHENTICATION_GROUP_LABEL'));

		return [
			new StringField('client_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_CLIENT_ID'), true, $authGroup),
			new PasswordField('client_secret', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_CLIENT_SECRET'), true, $authGroup),
			new StringField('tenant_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_TENANT_ID'), true, $authGroup),
			new StringField('email', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_EMAIL'), true, $authGroup),
		];
	}

	public function getDefaultParameters(): array
	{
		return [
			'base_url'       => 'https://graph.microsoft.com',
			'api_url'        => 'v1.0',
			'authentication' => [
				'route'            => '',
				'method'           => 'post',
				'grant_type'       => 'client_credentials',
				'scope'            => 'https://graph.microsoft.com/.default',
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