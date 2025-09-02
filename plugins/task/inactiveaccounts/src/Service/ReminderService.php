<?php
/**
 * @package     Joomla\Plugin\Task\Inactiveaccounts\Service
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Task\Inactiveaccounts\Service;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\Plugin\Task\Inactiveaccounts\Helper\Date;

class ReminderService
{
	private string $logo;

	private string $template;

	private CMSApplicationInterface $application;

	public function __construct(DatabaseInterface $db, QueryInterface $query, CMSApplicationInterface $application)
	{
		if(!class_exists('EmundusHelperEmails'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/helpers/emails.php';
		}
		$this->logo = \EmundusHelperEmails::getLogo();
		$this->logo = str_replace('/administrator', '', $this->logo);

		$query->clear()
			->select($db->quoteName('Template'))
			->from($db->quoteName('#__emundus_email_templates'))
			->where($db->quoteName('id') . ' = 1');
		$db->setQuery($query);
		$this->template = $db->loadResult();

		$this->application = $application;
	}

	public function sendReminder(User $account, int $daysToDisableAfter, array $reminders, string $subject = 'PLG_TASK_DISABLEINACTIVEACCOUNTS_EMAIL_SUBJECT', string $body = 'PLG_TASK_DISABLEINACTIVEACCOUNTS_EMAIL_BODY'): bool
	{
		$mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

		$data = [
			'sitename' => $this->application->get('sitename'),
		];

		// We reverse the reminders to start with the highest one
		$reminders = array_reverse($reminders);

		$no_reminder = null;

		foreach ($reminders as $reminder)
		{
			// If reminder is already sent, skip it
			if ($account->getParam('reminder_sent_' . $reminder) == 1)
			{
				break; // No need to send reminder
			}

			if ($account->lastvisitDate < Date::getModifiedDate(($daysToDisableAfter - $reminder), true))
			{
				// If reminder is not sent, set it as no_reminder
				$no_reminder = $reminder;
				break;
			}
		}

		if (empty($no_reminder))
		{
			return true; // No reminder to send
		}

		$disabled_date               = Factory::getDate($account->lastvisitDate)->modify("$daysToDisableAfter days");
		$today                       = Factory::getDate();
		$interval                    = $today->diff(Factory::getDate($disabled_date));
		$data['days_before_disable'] = $interval->days;
		if ($data['days_before_disable'] <= 0)
		{
			$data['days_before_disable'] = 1;
		}

		$subject = Text::sprintf($subject, $data['sitename'], $data['days_before_disable']);

		$data['name']            = $account->name;
		$data['date_of_disable'] = $disabled_date->format('d/m/Y');
		$body                    = Text::sprintf($body, $data['name'], $data['sitename'], $data['date_of_disable'], $data['sitename']);

		$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/", "/\[SITE_NAME\]/"], [$subject, $body, $data['sitename']], $this->template);
		$body = preg_replace("/\[LOGO\]/", $this->logo, $body);

		// Set sender
		$sender = [
			$this->application->get('mailfrom'),
			$this->application->get('fromname')
		];

		$mailer->setSender($sender);
		$mailer->addReplyTo($this->application->get('mailfrom'), $this->application->get('fromname'));
		$mailer->addRecipient($account->email);
		$mailer->setSubject($subject);
		$mailer->isHTML(true);
		$mailer->Encoding = 'base64';
		$mailer->setBody($body);

		$send = $mailer->Send();

		if ($send)
		{
			$account->setParam('reminder_sent_' . $no_reminder, 1);

			if (!$account->save())
			{
				throw new \Exception('Error saving user parameters after sending reminder email.');
			}

			sleep(1); // To avoid sending too many emails at once
		}

		return $send;
	}
}