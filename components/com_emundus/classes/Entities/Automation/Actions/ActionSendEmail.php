<?php

namespace Tchooz\Entities\Automation\Actions;

use EmundusHelperEmails;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;

class ActionSendEmail extends ActionEntity
{
	public const EMAIL_TO_SEND_PARAMETER = 'email_to_send';

	public array $emailsChoices = [];

	public static function getType(): string
	{
		return 'send_email';
	}

	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_SEND_EMAIL_LABEL');
	}

	public static function getDescription(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_SEND_EMAIL_DESCRIPTION');
	}

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$this->verifyRequiredParameters();
		$emailId = $this->getParameterValue(self::EMAIL_TO_SEND_PARAMETER);

		if (!class_exists('EmundusModelEmails')) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
		}
		$m_emails = new \EmundusModelEmails();

		$sent = false;
		if (!empty($context->getFile())) {
			if ($m_emails->sendEmail($context->getFile(), $emailId, null, [], false, $this->getAutomatedTaskUserId()))
			{
				$sent = true;
			}
		} else {
			$fnum = null;
			if (!empty($context->getOriginalContext())) {
				$fnum = $context->getOriginalContext()->getFile();
			}

			if (!empty($context->getUserId()))
			{
				$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($context->getUserId());
				if ($m_emails->sendEmailNoFnum($user->email, $emailId, null, $context->getUserId(), [], $fnum, true, [], $this->getAutomatedTaskUserId())) {
					$sent = true;
				}
			} else if (!empty($context->getCustom()))
			{
				$h_emails = new EmundusHelperEmails();
				if ($h_emails->correctEmail($context->getCustom()))
				{
					if ($m_emails->sendEmailNoFnum($context->getCustom(), $emailId, null, null, [], $fnum, true, [], $this->getAutomatedTaskUserId())) {
						$sent = true;
					}
				}
			}
		}

		if (!$sent) {
			return ActionExecutionStatusEnum::FAILED;
		}

		return ActionExecutionStatusEnum::COMPLETED;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$this->parameters = [
				new ChoiceField(self::EMAIL_TO_SEND_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SEND_EMAIL_PARAMETER_EMAIL_TO_SEND_LABEL'), $this->getEmailsChoices(), true)
			];
		}

		return $this->parameters;
	}

	/**
	 * @return ChoiceFieldValue[]
	 */
	private function getEmailsChoices(): array
	{
		if (empty($this->emailsChoices))
		{
			if (!class_exists('EmundusModelMessages'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/messages.php');
			}
			$m_messages = new \EmundusModelMessages();
			$emails = $m_messages->getEmailsByCategory('all');

			foreach ($emails as $email)
			{
				$this->emailsChoices[] = new ChoiceFieldValue($email->id, $email->subject);
			}
		}

		return $this->emailsChoices;
	}

	public static function getIcon(): ?string
	{
		return 'mail';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::USER;
	}

	public static function supportTargetTypes(): array
	{
		return [TargetTypeEnum::FILE, TargetTypeEnum::USER];
	}

	public static function isAsynchronous(): bool
	{
		return true;
	}

	public function getLabelForLog(): string
	{
		$labelForLog = $this->getLabel();

		if (!empty($this->getParameterValue(self::EMAIL_TO_SEND_PARAMETER)))
		{
			$emails = $this->getEmailsChoices();
			foreach ($emails as $email)
			{
				if ($email->getValue() == $this->getParameterValue(self::EMAIL_TO_SEND_PARAMETER))
				{
					$labelForLog .= ' (' . $email->getLabel() . ') ';
					break;
				}
			}
		}

		return $labelForLog;
	}
}