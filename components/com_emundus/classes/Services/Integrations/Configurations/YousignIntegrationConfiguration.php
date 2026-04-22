<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\DateField;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Services\Field\DisplayRule;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class YousignIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public function getParameters(): array
	{
		$authGroup   = new FieldGroup('authentication', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_AUTHENTICATION_GROUP_LABEL'));
		$configGroup = new FieldGroup('configuration', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_CONFIGURATION_GROUP_LABEL'));

		$modeField = new BooleanField('mode', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_PRODUCTION_MODE'), true, $authGroup);
		return [
			$modeField,
			new BooleanField('create_webhook', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_CREATE_WEBHOOK'), true, $authGroup, null, [
				new DisplayRule($modeField, ConditionOperatorEnum::EQUALS, 1)
			]),
			new PasswordField('token', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_BEARER_TOKEN'), true, $authGroup),
			new DateField('expiration_date', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_EXPIRATION_DATE'), false, $configGroup),
			new ChoiceField('signature_level', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SIGNATURE_LEVEL'), [
				new ChoiceFieldValue('electronic_signature', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SIGNATURE_LEVEL_ELECTRONIC')),
				new ChoiceFieldValue('advanced_electronic_signature', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SIGNATURE_LEVEL_ADVANCED')),
				new ChoiceFieldValue('qualified_electronic_signature', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SIGNATURE_LEVEL_QUALIFIED'))
			], true, false, $configGroup),
			new ChoiceField('signature_authentication_mode', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SIGNATURE_AUTHENTICATION_MODE'), [
				new ChoiceFieldValue('otp_email', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SIGNATURE_AUTHENTICATION_MODE_EMAIL')),
				new ChoiceFieldValue('otp_sms', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SIGNATURE_AUTHENTICATION_MODE_SMS')),
			], true, false, $configGroup),
			new StringField('request_name', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_REQUEST_NAME'), false, $configGroup),
			new ChoiceField('signature_display_mode', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SIGNATURE_DISPLAY_MODE'), [
				new ChoiceFieldValue('minimal', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SIGNATURE_DISPLAY_MODE_MINIMAL')),
				new ChoiceFieldValue('detailed', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SIGNATURE_DISPLAY_MODE_DETAILED')),
			], true, false, $configGroup),
		];
	}

	public function getDefaultParameters(): array
	{
		return [
			'authentication' => [
				'mode' => 1,
				'create_webhook' => 0,
			],
			'configuration' => [
				'signature_level' => 'electronic_signature',
				'signature_authentication_mode' => 'otp_email',
				'signature_display_mode' => 'minimal',
			]
		];
	}
}