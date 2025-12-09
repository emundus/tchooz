<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\DisplayRule;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\FieldResearch;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Fields\YesnoField;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\NumericSign\SignConnectorsEnum;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\NumericSign\RequestRepository;
use Tchooz\Services\Automation\Condition\FormDataConditionResolver;

class ActionGenerateSignatureRequest extends ActionEntity
{

	public static function getIcon(): ?string
	{
		return 'signature';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::SIGN;
	}

	public static function isAsynchronous(): bool
	{
		return false;
	}

	public static function getType(): string
	{
		return 'generate_signature_request';
	}

	public static function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_AUTOMATION_ACTION_GENERATE_SIGNATURE_REQUEST');
	}

	public static function supportTargetTypes(): array
	{
		return [];
	}

	public function execute(ActionTargetEntity $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$status = ActionExecutionStatusEnum::FAILED;

		if (!empty($context->getFile()))
		{
			$attachment = $this->getParameterValue('attachment');
			if (!empty($attachment))
			{
				try {
					$ordered = $this->getParameterValue('ordered');
					$signers = $this->getSignersFromParameters($context);

					if (!class_exists('EmundusHelperFiles'))
					{
						require_once JPATH_ROOT . '/components/com_emundus/helpers/files.php';
					}
					$ccid = \EmundusHelperFiles::getIdFromFnum($context->getFile());
					if (!class_exists('EmundusModelSign'))
					{
						require_once JPATH_ROOT . '/components/com_emundus/models/sign.php';
					}
					$m_sign  = new \EmundusModelSign();
					$request_id = $m_sign->saveRequest(0, 'to_sign', $ccid, $this->getAutomatedTaskUserId(), $context->getFile(), $attachment, $this->getParameterValue('synchronizer'), $signers, 0, $this->getAutomatedTaskUserId(), $ordered, $this->getParameterValue('subject'));
					if (!empty($request_id))
					{
						$requestRepository = new RequestRepository();
						$requestEntity     = $requestRepository->loadRequestById($request_id);

						$dispatcher = Factory::getApplication()->getDispatcher();
						if ($dispatcher) {
							PluginHelper::importPlugin('emundus');
							$onCallEventHandler = new GenericEvent(
								'onCallEventHandler',
								[
									'onAfterRequestSaved',
									[
										'request_id' => $request_id,
										'status'     => $requestEntity->getStatus()->value,
										'ccid'       => $requestEntity->getCcid(),
										'user_id'    => $requestEntity->getUserId(),
										'fnum'       => $requestEntity->getFnum(),
										'attachment' => $requestEntity->getAttachment()->getId(),
										'connector'  => $requestEntity->getConnector()->value,
										'signers'    => $signers
									]
								]
							);
							$dispatcher->dispatch('onCallEventHandler', $onCallEventHandler);
						}

						$status = ActionExecutionStatusEnum::COMPLETED;
					} else {
						$status = ActionExecutionStatusEnum::FAILED;
					}
				} catch (\Exception $e)
				{
					Log::add('Error generating signature request: ' . $e->getMessage(), Log::ERROR, 'com_emundus.action');
					$status = ActionExecutionStatusEnum::FAILED;
				}
			}
		}

		return $status;
	}

	/**
	 * @param   ActionTargetEntity  $context
	 * @return array<string>
	 * @throws \Exception
	 */
	public function getSignersFromParameters(ActionTargetEntity $context): array
	{
		$signers = [];
		$signersParameters = $this->getParameterValues()['signers'] ?? [];

		foreach ($signersParameters as $signerParam)
		{
			$contactRepository = new ContactRepository();
			switch($signerParam['signer_type'])
			{
				case 'fnum':
					$contact = $contactRepository->getByFnum($context->getFile());
					if (empty($contact))
					{
						$contact = $contactRepository->getOrCreateContactFromUserId($context->getUserIdFromFile());
					}

					$signers[] = [
						'signer' => $contact->getId(),
						'order' => $signerParam['order'],
						'anchor' => $signerParam['anchor']
					];
					break;
				case 'form':
					$formDataResolver = new FormDataConditionResolver();
					$email = $formDataResolver->resolveValue($context, $signerParam['email_element']);
					$lastname = $formDataResolver->resolveValue($context, $signerParam['lastname_element']);
					$firstname = $formDataResolver->resolveValue($context, $signerParam['firstname_element']);
					$phone = $formDataResolver->resolveValue($context, $signerParam['phone_element']);

					if (!empty($email))
					{
						$contact = $contactRepository->getByEmail($email);
						if (empty($contact)) {
							$contact = $contactRepository->flush(
								new ContactEntity(
									email: $email,
									lastname: $lastname,
									firstname: $firstname,
									phone_1: $phone
								)
							);
						}

						$signers[] = [
							'signer' => $contact->getId(),
							'order' => $signerParam['order'],
							'anchor' => $signerParam['anchor']
						];
					}
					break;
				case 'contact':
					$signers[] = [
						'signer' => $signerParam['contact'],
						'order' => $signerParam['order'],
						'anchor' => $signerParam['anchor']
					];
					break;
			}
		}

		return $signers;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$signersGroup = new FieldGroup('signers', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_SIGNERS'), true);

			$typeField = new ChoiceField('signer_type', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_SIGNER_TYPE'), [
				new ChoiceFieldValue('fnum', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_SIGNER_TYPE_FNUM')),
				new ChoiceFieldValue('form', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_SIGNER_TYPE_FORM')),
				new ChoiceFieldValue('contact', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_SIGNER_TYPE_CONTACT')),
			], true, false, $signersGroup);

			$displayFormFieldsRules = [new DisplayRule($typeField, ConditionOperatorEnum::EQUALS, 'form'),];
			$research = new FieldResearch('form', 'getFabrikElementOptions');
			$this->parameters = [
				new ChoiceField('synchronizer', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_SYNCHRONIZER'), $this->getNumericSignSynchronizerOptions(), true),
				new ChoiceField('attachment', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_ATTACHMENTS'), $this->getAttachmentFieldOptions(), true, false),
				new StringField('subject', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_SUBJECT'), false),
				new YesnoField('ordered', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_ORDERED'), true),
				new NumericField('order', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_ORDER'), false, $signersGroup),
				new StringField('anchor', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_ANCHOR'), false, $signersGroup),
				$typeField,
				(new ChoiceField('email_element', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_SIGNER_EMAIL'), [], true, false, $signersGroup))->setDisplayRules($displayFormFieldsRules)->setResearch($research),
				(new ChoiceField('lastname_element', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_SIGNER_NAME'), [], true, false, $signersGroup))->setDisplayRules($displayFormFieldsRules)->setResearch($research),
				(new ChoiceField('firstname_element', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_SIGNER_FIRSTNAME'), [], true, false, $signersGroup))->setDisplayRules($displayFormFieldsRules)->setResearch($research),
				(new ChoiceField('phone_element', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_SIGNER_PHONE'), [], false, false, $signersGroup))->setDisplayRules($displayFormFieldsRules)->setResearch($research),
				(new ChoiceField('contact', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PARAMETER_SIGNATURE_SIGNER_CONTACT'), [], true, false, $signersGroup))->setDisplayRules([
					new DisplayRule($typeField, ConditionOperatorEnum::EQUALS, 'contact'),
				])->setResearch(new FieldResearch('contacts', 'getContactOptions'))
			];
		}

		return $this->parameters;
	}

	/**
	 * This is used to load default values based on what is stored in bdd
	 * @return void
	 */
	public function setParametersOptionsWithValues(): void
	{
		$formElementOptions = [];
		$contactElementsOptions = [];
		$contactIds = [];

		if (!empty($this->getParameterValues()['signers']))
		{
			$elements = [];
			foreach($this->getParameterValues()['signers'] as $row)
			{
				if ($row['signer_type'] !== 'form')
				{
					if ($row['signer_type'] === 'contact')
					{
						$contactIds[] = $row['contact'];
					}

					continue;
				}

				$elements[] = $row['email_element'];
				$elements[] = $row['lastname_element'];
				$elements[] = $row['firstname_element'];
				$elements[] = $row['phone_element'];
			}
			$elements = array_unique($elements);

			if (!empty($elements))
			{
				foreach ($elements as $element)
				{
					list($formId, $elementId) = explode('.', $element);
					$elements = \EmundusHelperEvents::getFormElements((int)$formId, (int)$elementId, true, [], []);

					if (!empty($elements))
					{
						foreach ($elements as $el)
						{
							$formElementOptions[] = new ChoiceFieldValue($el->form_id . '.' . $el->id, Text::_($el->label) . ' (' . Text::_($el->form_label) . ')');
						}
					}
				}
			}
		}

		if (!empty($contactIds))
		{
			$contactRepository = new ContactRepository();
			foreach ($contactIds as $contactId)
			{
				$contact = $contactRepository->getById((int)$contactId);
				if ($contact instanceof ContactEntity)
				{
					$contactElementsOptions[] = new ChoiceFieldValue($contact->getId(), $contact->getLastname() . ' ' . $contact->getFirstname() . ' (' . $contact->getEmail() . ')');
				}
			}
		}

		foreach ($this->parameters as $parameter)
		{
			if (in_array($parameter->getName(), ['email_element', 'lastname_element', 'firstname_element', 'phone_element']))
			{
				$parameter->setChoices($formElementOptions);
			}
			else if ($parameter->getName() === 'contact')
			{
				$parameter->setChoices($contactElementsOptions);
			}
		}
	}

	public function getLabelForLog(): string
	{
		return '';
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	public function getNumericSignSynchronizerOptions(): array
	{
		$options = [];

		foreach (SignConnectorsEnum::cases() as $connector)
		{
			$options[] = new ChoiceFieldValue($connector->value, Text::_($connector->getLabel()));
		}

		return $options;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	public function getAttachmentFieldOptions(): array
	{
		$options = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery()
			->select('id, value')
			->from($db->quoteName('#__emundus_setup_attachments'))
			->where($db->quoteName('published') . ' = 1');
		$db->setQuery($query);
		$results = $db->loadObjectList();

		if (!empty($results)) {
			foreach ($results as $result) {
				$options[] = new ChoiceFieldValue($result->id, $result->value);
			}
		}

		return $options;
	}
}