<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class DocusignIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public function getParameters(): array
	{
		$authGroup = new FieldGroup('authentication', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCUSIGN_AUTHENTICATION_GROUP_LABEL'));
		$configGroup = new FieldGroup('configuration', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCUSIGN_CONFIGURATION_GROUP_LABEL'));

		return [
			new StringField('user_guid', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCUSIGN_USER_GUID_LABEL'), true, $authGroup),
			new StringField('account_id', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCUSIGN_ACCOUNT_ID_LABEL'), true, $authGroup),
			new PasswordField('integration_key', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCUSIGN_INTEGRATION_KEY_LABEL'), true, $authGroup),
			new PasswordField('secret_key', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCUSIGN_SECRET_KEY_LABEL'), true, $authGroup),
			new PasswordField('rsa_private_key', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCUSIGN_RSA_PRIVATE_KEY_LABEL'), true, $authGroup),
			new ChoiceField('mode', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCUSIGN_MODE_LABEL'), [
				new ChoiceFieldValue('TEST', 'TEST'),
				new ChoiceFieldValue('PRODUCTION', 'PRODUCTION')
			], true, false, $configGroup)
		];
	}
}