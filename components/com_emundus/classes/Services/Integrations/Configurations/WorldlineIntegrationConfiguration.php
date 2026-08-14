<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class WorldlineIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public function getParameters(): array
	{
		$authGroup = new FieldGroup('authentication', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_AUTH'));

		return [
			new BooleanField('mode', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_PRODUCTION_MODE'), true, $authGroup),
			new StringField('merchant_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_MERCHANT_ID'), true, $authGroup),
			new StringField('api_key_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_API_KEY_ID'), true, $authGroup),
			new PasswordField('api_secret', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_API_SECRET'), true, $authGroup),
			// Optional: webhooks are only a fallback, the nominal flow polls GetHostedCheckoutStatus.
			new StringField('webhook_key_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_WEBHOOK_KEY_ID'), false, $authGroup),
			new PasswordField('webhook_secret', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_WEBHOOK_SECRET'), false, $authGroup),
		];
	}

	public function getDefaultParameters(): array
	{
		return [
			'authentication' => [
				'mode' => 0,
			],
		];
	}
}
