<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Enums\Payment\PayboxEnvironmentEnum;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class PayboxIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public function getParameters(): array
	{
		$authGroup   = new FieldGroup('authentication', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_PAYBOX_SETUP_AUTH'));
		$configGroup = new FieldGroup('configuration', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_PAYBOX_SETUP_PARAMS'));

		$modeChoices = array_map(
			static fn(PayboxEnvironmentEnum $environment): ChoiceFieldValue => new ChoiceFieldValue($environment->value, $environment->getLabel()),
			PayboxEnvironmentEnum::cases()
		);

		return [
			new StringField('site', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_PAYBOX_SETUP_SITE'), true, $authGroup),
			new StringField('rang', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_PAYBOX_SETUP_RANG'), true, $authGroup),
			new StringField('identifiant', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_PAYBOX_SETUP_IDENTIFIANT'), true, $authGroup),
			new PasswordField('hmac_key', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_PAYBOX_SETUP_HMAC_KEY'), true, $authGroup),
			new StringField('public_key', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_PAYBOX_SETUP_PUBLIC_KEY'), true, $authGroup),
			new ChoiceField('mode', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_PAYBOX_SETUP_MODE'), $modeChoices, true, false, $configGroup),
			new StringField('endpoint', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_PAYBOX_SETUP_ENDPOINT'), false, $configGroup),
		];
	}

	public function getDefaultParameters(): array
	{
		return [
			'configuration' => [
				'mode' => 'TEST',
			],
		];
	}
}