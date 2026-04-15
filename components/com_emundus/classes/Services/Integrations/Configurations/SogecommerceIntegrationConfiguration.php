<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class SogecommerceIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public function getParameters(): array
	{
		$authGroup = new FieldGroup('authentication', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_SOGECOMMERCE_SETUP_AUTH'));
		$configGroup = new FieldGroup('configuration', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_SOGECOMMERCE_SETUP_PARAMS'));

		return [
			new StringField('client_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_SOGECOMMERCE_SETUP_CLIENT_ID'), true, $authGroup),
			new PasswordField('client_secret', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_SOGECOMMERCE_SETUP_CLIENT_SECRET'), true, $authGroup),
			new StringField('endpoint', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_SOGECOMMERCE_SETUP_ENDPOINT_URL'), true, $configGroup),
			new ChoiceField('mode', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_SOGECOMMERCE_SETUP_MODE'), [
				new ChoiceFieldValue('TEST', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_SOGECOMMERCE_SETUP_MODE_TEST')),
				new ChoiceFieldValue('PRODUCTION', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_SOGECOMMERCE_SETUP_MODE_PROD')),
			], true, false, $configGroup),
			new StringField('return_url', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_SOGECOMMERCE_SETUP_SUCCESS_URL'), true, $configGroup)
		];
	}

	public function getDefaultParameters(): array
	{
		return [
			'endpoint' => 'https://sogecommerce.societegenerale.eu/vads-payment/',
			'mode' => 'TEST',
			'configuration' => [
				'endpoint' => 'https://sogecommerce.societegenerale.eu/vads-payment/',
				'mode' => 'TEST',
			]
		];
	}
}