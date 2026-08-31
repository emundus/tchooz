<?php

namespace Tchooz\Services\Integrations\Configurations;

use EmundusModelEmails;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Fields\WysiwygField;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\NumericSign\DocaposteContactEditabilityEnum;
use Tchooz\Enums\NumericSign\DocaposteContactReadOnlyParameterEnum;
use Tchooz\Factories\Field\ChoiceFieldFactory;
use Tchooz\Services\Field\DisplayRule;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class DocaposteIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	/**
	 * Formatting Docaposte accepts inside the agreement text.
	 */
	private const AGREEMENT_TEXT_ALLOWED_TAGS = ['b', 'strong', 'i', 'em', 'u', 'a'];

	private const AGREEMENT_TEXT_ALLOWED_ATTRIBUTES = ['href', 'target', 'rel'];

	/**
	 * Docaposte only makes a link clickable when it is absolute and secure.
	 */
	private const AGREEMENT_TEXT_ALLOWED_LINK_SCHEMES = ['https'];

	private const AGREEMENT_TEXT_MAX_LENGTH = 10000;

	public function getParameters(): array
	{
		$authGroup   = new FieldGroup('authentication', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_AUTHENTICATION_GROUP_LABEL'));
		$configGroup = new FieldGroup('configuration', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_CONFIGURATION_GROUP_LABEL'));

		$emails = $this->getEmails();

		$contactEditabilityFields = $this->getContactEditabilityFields($configGroup);

		return [
			new StringField('identifier', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_IDENTIFIER_LABEL'), true, $authGroup),
			new PasswordField('password', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_PASSWORD_LABEL'), true, $authGroup),
			new StringField('offerCode', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_OFFER_CODE_LABEL'), true, $authGroup),
			new StringField('organizationalUnitCode', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_ORGANIZATIONAL_UNIT_CODE_LABEL'), true, $authGroup),
			new StringField('senderEmail', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_SENDER_MAIL_LABEL'), true, $configGroup),
			new ChoiceField('emailInit', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_EMAIL_INIT_LABEL'), $emails, true, false, $configGroup),
			new ChoiceField('emailReminder', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_EMAIL_REMINDER_LABEL'), $emails, true, false, $configGroup),
			new ChoiceField('emailCancellation', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_EMAIL_CANCELLATION_LABEL'), $emails, false, false, $configGroup),
			new ChoiceField('emailCompletion', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_EMAIL_COMPLETION_LABEL'), $emails, false, false, $configGroup),
			...$contactEditabilityFields,
			$this->getAgreementTextField($configGroup),
			new ChoiceField('mode', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCUPOSTE_MODE_LABEL'), [
				new ChoiceFieldValue('TEST', 'TEST'),
				new ChoiceFieldValue('PRODUCTION', 'PRODUCTION')
			], true, false, $configGroup),
			new BooleanField('retrieveProofDocument', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_RETRIEVE_PROOF_DOCUMENT_LABEL'), false, $configGroup)
		];
	}

	public function getDefaultParameters(): array
	{
		return [
			'configuration' => [
				'contactEditability' => DocaposteContactEditabilityEnum::ALL->value
			]
		];
	}

	/**
	 * Builds the contact editability choice, followed by the per detail toggles it reveals when set to CUSTOM.
	 *
	 * @return array<Field>
	 */
	private function getContactEditabilityFields(FieldGroup $group): array
	{
		$editabilityField = new ChoiceField(
			'contactEditability',
			Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_CONTACT_EDITABILITY_LABEL'),
			ChoiceFieldFactory::makeOptionsFromEnum(DocaposteContactEditabilityEnum::cases()),
			true,
			false,
			$group,
			false,
			false
		);
		$editabilityField->setHelpText(Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_CONTACT_READ_ONLY_HELP'));

		$customRules = [
			new DisplayRule($editabilityField, ConditionOperatorEnum::EQUALS, DocaposteContactEditabilityEnum::CUSTOM->value)
		];

		return [
			$editabilityField,
			new BooleanField(
				DocaposteContactReadOnlyParameterEnum::EMAIL->value,
				Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_EMAIL_READ_ONLY_LABEL'),
				false,
				$group,
				null,
				$customRules
			),
			new BooleanField(
				DocaposteContactReadOnlyParameterEnum::PHONE->value,
				Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_PHONE_READ_ONLY_LABEL'),
				false,
				$group,
				null,
				$customRules
			)
		];
	}

	/**
	 * Text of the mandatory checkbox the signatory ticks before signing. Left empty, Docaposte shows
	 * its own acceptance window, so the field stays optional.
	 */
	private function getAgreementTextField(FieldGroup $group): WysiwygField
	{
		$agreementText = new WysiwygField(
			'agreementText',
			Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_AGREEMENT_TEXT_LABEL'),
			false,
			$group,
			null,
			self::AGREEMENT_TEXT_MAX_LENGTH
		);

		$agreementText->setAllowedMarkup(
			self::AGREEMENT_TEXT_ALLOWED_TAGS,
			self::AGREEMENT_TEXT_ALLOWED_ATTRIBUTES,
			self::AGREEMENT_TEXT_ALLOWED_LINK_SCHEMES
		);
		$agreementText->setSingleLine(true);
		$agreementText->setEditorContentHeight('2.5em');
		$agreementText->setHelpText(Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_AGREEMENT_TEXT_HELP'));

		return $agreementText;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getEmails(): array
	{
		$options = [];

		if (!class_exists('EmundusModelEmails'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/emails.php';
		}

		$modelEmail = new EmundusModelEmails();

		$emails = $modelEmail->getAllEmails(0, 1);

		foreach ($emails['datas'] as $email)
		{
			$options[] = new ChoiceFieldValue($email->id, $email->subject);
		}

		return $options;
	}
}