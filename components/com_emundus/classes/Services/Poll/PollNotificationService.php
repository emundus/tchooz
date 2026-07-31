<?php

namespace Tchooz\Services\Poll;

use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Entities\Poll\PollParticipantsEntity;
use Tchooz\Repositories\Poll\PollParticipantsRepository;
use Tchooz\Services\Emails\EmailService;

/**
 * Send poll notifications (to participants and creator) and own the body/subject sanitization
 * pipeline. Extracted from PollService to keep email + HTML sanitization concerns isolated.
 */
class PollNotificationService
{
	private EmailService $emailService;
	private PollParticipantsRepository $pollParticipantsRepository;

	public function __construct(
		?EmailService               $emailService = null,
		?PollParticipantsRepository $pollParticipantsRepository = null
	)
	{
		$this->emailService               = $emailService ?? new EmailService();
		$this->pollParticipantsRepository = $pollParticipantsRepository ?? new PollParticipantsRepository();

		if (!class_exists('HtmlSanitizerSingleton'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
		}

		Log::addLogger(['text_file' => 'com_emundus.poll.php'], Log::ALL, ['com_emundus.poll']);
	}

	/**
	 * Sanitize an HTML body fragment intended for email delivery.
	 */
	public function sanitizeBody(string $body): string
	{
		return HtmlSanitizerSingleton::getInstance()->sanitizeFor('body', $body);
	}

	/**
	 * Send a notification email to every targeted participant. When $recipients is empty, all
	 * poll participants are notified.
	 *
	 * @param   PollParticipantsEntity[]  $recipients
	 * @param   ?string                   $replyTo     Optional Reply-To address applied to every email.
	 * @param   bool                      $async       When true, queue each email for asynchronous delivery instead of sending it immediately.
	 *
	 * @return  int  Number of emails successfully sent (or queued when $async is true).
	 */
	public function notifyParticipants(PollEntity $poll, string $subject, string $body, array $recipients = [], ?string $replyTo = null, bool $async = true): int
	{
		$subject = strtr($subject, ['{poll}' => $poll->getName()]);
		$sent    = 0;

		if (empty($recipients))
		{
			$recipients = $poll->getParticipants();
		}

		foreach ($recipients as $recipient)
		{
			assert($recipient instanceof PollParticipantsEntity);

			$personalizedBody = $this->buildBody($poll, $recipient, $body);
			$userId           = !empty($recipient->getUser()->id) ? (int) $recipient->getUser()->id : null;

			try
			{
				if ($async)
				{
					$task = $this->emailService->sendEmailWithoutTemplateAsync(
						$recipient->getEmail(),
						$subject,
						$personalizedBody,
						$userId,
						null,
						null,
						$replyTo
					);

					if (empty($task))
					{
						throw new \RuntimeException('Failed to queue email.');
					}
				}
				else
				{
					$this->emailService->resetMailer()->sendEmailWithoutTemplate(
						$recipient->getEmail(),
						$subject,
						$personalizedBody,
						null,
						$userId,
						reply_to_override: $replyTo
					);
				}

				$sent++;
			}
			catch (\Throwable $e)
			{
				Log::add(
					sprintf(
						'Failed to send poll #%d notification to %s: %s',
						$poll->getId(),
						$recipient->getEmail(),
						$e->getMessage()
					),
					Log::WARNING,
					'com_emundus.poll'
				);
			}
		}

		return $sent;
	}

	/**
	 * Send a notification email to the poll creator, if one is recorded.
	 */
	public function notifyCreator(PollEntity $poll, string $subject, string $body): bool
	{
		$creatorId = $poll->getCreatedBy();
		if (empty($creatorId))
		{
			return false;
		}

		$creator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $creatorId);
		if (empty($creator) || empty($creator->email))
		{
			return false;
		}

		$subject = strtr($subject, ['{poll}' => $poll->getName()]);

		$creatorParticipant = new PollParticipantsEntity(
			0,
			$poll,
			$creator->email,
			'',
			$creator->name ?? '',
			$creator
		);
		$creatorBody = $this->buildBody($poll, $creatorParticipant, $body);

		try
		{
			$this->emailService->resetMailer()->sendEmailWithoutTemplate(
				$creator->email,
				$subject,
				$creatorBody,
				null,
				(int) $creatorId
			);

			return true;
		}
		catch (\Throwable $e)
		{
			Log::add(
				sprintf('Failed to send poll #%d notification to creator %d: %s', $poll->getId(), $creatorId, $e->getMessage()),
				Log::WARNING,
				'com_emundus.poll'
			);

			return false;
		}
	}

	private function buildBody(PollEntity $poll, PollParticipantsEntity $participant, string $body): string
	{
		$displayName = trim(($participant->getFirstname() ?? '') . ' ' . ($participant->getLastname() ?? ''));
		if ($displayName === '')
		{
			$displayName = $participant->getEmail();
		}

		$siteName = Factory::getApplication()->get('sitename', 'Tchooz');

		return strtr($body, [
			'{name}'        => $displayName,
			'{poll}'        => $poll->getName(),
			'{description}' => $poll->getDescription(),
			'{sitename}'    => $siteName,
			'{siteurl}'     => Uri::base()
		]);
	}
}
