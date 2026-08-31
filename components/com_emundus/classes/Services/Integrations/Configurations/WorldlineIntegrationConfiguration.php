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

		$modeField = new BooleanField('mode', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_PRODUCTION_MODE'), true, $authGroup);
		$modeField->setHelpText(Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_PRODUCTION_MODE_HELP'));

		$subdomainField = new StringField('checkout_subdomain', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_CHECKOUT_SUBDOMAIN'), false, $authGroup);
		$subdomainField->setHelpText(Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_CHECKOUT_SUBDOMAIN_HELP'));

		// Displayed so the administrator can paste it into the Worldline Configuration Center.
		// The value itself is built by IntegrationSetup.vue, which knows the site origin and
		// the synchronizer id; it is read-only and never persisted.
		$webhookUrlField = new StringField('webhook_url', Text::_('COM_EMUNDUS_WORLDLINE_SETUP_WEBHOOK_ENDPOINT_LABEL'), false, $authGroup);
		$webhookUrlField->setHelpText(Text::_('COM_EMUNDUS_WORLDLINE_SETUP_WEBHOOK_ENDPOINT_LABEL_HELP'));
		$webhookUrlField->setReadonly(true)->setCopyable(true);

		return [
			$modeField,
			new StringField('merchant_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_MERCHANT_ID'), true, $authGroup),
			new StringField('api_key_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_API_KEY_ID'), true, $authGroup),
			new PasswordField('api_secret', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_API_SECRET'), true, $authGroup),
			$subdomainField,
			// Worldline signs its webhooks with a key pair that is distinct from the API one.
			new StringField('webhook_key_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_WEBHOOK_KEY_ID'), true, $authGroup),
			new PasswordField('webhook_secret', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_WEBHOOK_SECRET'), true, $authGroup),
			$webhookUrlField,
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
