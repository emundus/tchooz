<?php

namespace Tchooz\Entities\Automation\Actions;

use EmundusHelperEmails;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Plugin\Task\ExecuteEmundusActions\Extension\ExecuteEmundusActions;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionExecutionMessage;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\Task\TaskPriorityEnum;

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
		$emailId = $this->getParameterValue(self::EMAIL_TO_SEND_PARAMETER);

		// No email template selected: send a raw email (subject/body carried by the target parameters).
		if (empty($emailId))
		{
			$contexts = is_array($context) ? $context : [$context];
			$allSent  = true;
			foreach ($contexts as $ctx)
			{
				if ($this->executeRawEmail($ctx) !== ActionExecutionStatusEnum::COMPLETED)
				{
					$allSent = false;
				}
			}

			return $allSent ? ActionExecutionStatusEnum::COMPLETED : ActionExecutionStatusEnum::FAILED;
		}

		if (!class_exists('EmundusModelEmails')) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
		}
		$m_emails = new \EmundusModelEmails();

		$sent = false;
		if (!empty($context->getFile())) {
			if ($m_emails->sendEmail($context->getFile(), $emailId, null, [], false, $this->getAutomatedTaskUserId()))
			{
				$this->addExecutionMessage(new ActionExecutionMessage('Email sent to file ' . $context->getFile()));
				$sent = true;
			}
			else
			{
				$this->addExecutionMessage(new ActionExecutionMessage('Failed to send email to file ' . $context->getFile(), \Tchooz\Enums\Automation\ActionMessageTypeEnum::ERROR));
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
					$this->addExecutionMessage(new ActionExecutionMessage('Email sent to user ' . $user->email));
					$sent = true;
				}
				else
				{
					$this->addExecutionMessage(new ActionExecutionMessage('Failed to send email to user ' . $user->email, \Tchooz\Enums\Automation\ActionMessageTypeEnum::ERROR));
				}
			} else if (!empty($context->getCustom()))
			{
				$h_emails = new EmundusHelperEmails();
				if ($h_emails->correctEmail($context->getCustom()))
				{
					if ($m_emails->sendEmailNoFnum($context->getCustom(), $emailId, null, null, [], $fnum, true, [], $this->getAutomatedTaskUserId())) {
						$this->addExecutionMessage(new ActionExecutionMessage('Email sent to ' . $context->getCustom()));
						$sent = true;
					}
					else
					{
						$this->addExecutionMessage(new ActionExecutionMessage('Failed to send email to ' . $context->getCustom(), \Tchooz\Enums\Automation\ActionMessageTypeEnum::ERROR));
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
		// TODO: Allow to set email parameters without templates ? (subject, body, replyto,...)
		if (empty($this->parameters))
		{
			$this->parameters = [
				new ChoiceField(self::EMAIL_TO_SEND_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SEND_EMAIL_PARAMETER_EMAIL_TO_SEND_LABEL'), $this->getEmailsChoices(), true)
			];
		}

		return $this->parameters;
	}

	/**
	 * Send a raw email (no template) for the given target. Subject, body and reply-to are read from the
	 * target parameters ('subject', 'body', 'reply_to'); the recipient is the target user or custom address.
	 */
	private function executeRawEmail(ActionTargetEntity $context): ActionExecutionStatusEnum
	{
		$params  = $context->getParameters();
		$subject = $params['subject'] ?? '';
		$body    = $params['body'] ?? '';
		$replyTo = $params['reply_to'] ?? null;

		$to     = null;
		$userId = null;
		if (!empty($context->getUserId()))
		{
			$userId = $context->getUserId();
			$user   = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);
			$to     = $user->email ?? null;
		}
		elseif (!empty($context->getCustom()))
		{
			$to = $context->getCustom();
		}

		if (empty($to) || empty($body))
		{
			$this->addExecutionMessage(new ActionExecutionMessage('Raw email skipped: missing recipient or body.', \Tchooz\Enums\Automation\ActionMessageTypeEnum::ERROR));

			return ActionExecutionStatusEnum::FAILED;
		}

		$fnum = $context->getFile();
		if (empty($fnum) && !empty($context->getOriginalContext()))
		{
			$fnum = $context->getOriginalContext()->getFile();
		}

		try
		{
			(new \Tchooz\Services\Emails\EmailService())->resetMailer()->sendEmailWithoutTemplate(
				$to,
				$subject,
				$body,
				null,
				$userId,
				[],
				$fnum,
				[],
				$this->getAutomatedTaskUserId(),
				$replyTo
			);
			$this->addExecutionMessage(new ActionExecutionMessage('Raw email sent to ' . $to));

			return ActionExecutionStatusEnum::COMPLETED;
		}
		catch (\Throwable $e)
		{
			$this->addExecutionMessage(new ActionExecutionMessage('Failed to send raw email to ' . $to . ': ' . $e->getMessage(), \Tchooz\Enums\Automation\ActionMessageTypeEnum::ERROR));

			return ActionExecutionStatusEnum::FAILED;
		}
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

	public function getPriority(): TaskPriorityEnum
	{
		return TaskPriorityEnum::HIGH;
	}
}