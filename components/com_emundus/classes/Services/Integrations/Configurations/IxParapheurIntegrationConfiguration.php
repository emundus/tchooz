<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Attachments\AttachmentType;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class IxParapheurIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public function getParameters(): array
	{
		$authGroup   = new FieldGroup('authentication', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_AUTHENTICATION_GROUP_LABEL'));
		$configGroup = new FieldGroup('configuration', Text::_('COM_EMUNDUS_INTEGRATIONS_IXPARAPHEUR_CONFIGURATION_GROUP_LABEL'));

		return [
			new StringField('base_url', Text::_('COM_EMUNDUS_INTEGRATIONS_IXPARAPHEUR_BASE_URL_LABEL'), true, $authGroup),
			new PasswordField('token', Text::_('COM_EMUNDUS_INTEGRATIONS_IXPARAPHEUR_TOKEN_LABEL'), true, $authGroup),
			new StringField('nature', Text::_('COM_EMUNDUS_INTEGRATIONS_IXPARAPHEUR_NATURE_LABEL'), true, $configGroup),
			new StringField('default_signer_email', Text::_('COM_EMUNDUS_INTEGRATIONS_IXPARAPHEUR_DEFAULT_SIGNER_EMAIL_LABEL'), false, $configGroup),
			new ChoiceField('default_attachment_id', Text::_('COM_EMUNDUS_INTEGRATIONS_IXPARAPHEUR_DEFAULT_ATTACHMENT_ID_LABEL'), $this->getAttachments(), false, false, $configGroup),
		];
	}

	private function getAttachments(): array
	{
		$attachmentsChoices = [];

		$attachmentTypeRepository = new AttachmentTypeRepository();

		$availableAttachments = $attachmentTypeRepository->get();
		foreach ($availableAttachments as $availableAttachment)
		{
			assert($availableAttachment instanceof AttachmentType);
			$attachmentsChoices[] = new ChoiceFieldValue($availableAttachment->getId(), $availableAttachment->getName());
		}

		return $attachmentsChoices;
	}

	public function getDefaultParameters(): array
	{
		return [];
	}
}
