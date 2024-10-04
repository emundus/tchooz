<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Task.deletetestaccounts
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\DeleteTestAccounts\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * A task plugin. For Delete Test accounts after x days
 * {@see ExecuteTaskEvent}.
 *
 * @since 5.0.0
 */
final class DeleteTestAccounts extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	/**
	 * @var string[]
	 * @since 5.0.0
	 */
	private const TASKS_MAP = [
		'delete.testaccounts' => [
			'langConstPrefix' => 'PLG_TASK_DELETETESTACCOUNTS_DELETE',
			'method'          => 'deleteTestAccounts',
			'form'            => 'deleteForm',
		],
	];

	/**
	 * @var boolean
	 * @since 5.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * @inheritDoc
	 *
	 * @return string[]
	 *
	 * @since 5.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	/**
	 * @param   ExecuteTaskEvent  $event  The `onExecuteTask` event.
	 *
	 * @return integer  The routine exit code.
	 *
	 * @throws \Exception
	 * @since  5.0.0
	 */
	private function deleteTestAccounts(ExecuteTaskEvent $event): int
	{
		$daysToReminderAfter = (int) $event->getArgument('params')->testAccountReminderPeriod ?? 0;
		$daysToDeleteAfter   = (int) $event->getArgument('params')->testAccountDeletePeriod ?? 0;
		$this->logTask(sprintf('Delete test accounts after %d days', $daysToDeleteAfter));

		$app   = Factory::getApplication();
		$now   = Factory::getDate()->toSql();
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		try
		{
			// First get all testing_account that lastvisitdate is older than $daysToDeleteAfter
			if ($daysToDeleteAfter > 0)
			{
				$days = -1 * $daysToDeleteAfter;

				$query->select('id')
					->from($db->quoteName('#__users'))
					->where($db->quoteName('lastvisitdate') . ' < ' . $db->quote(Factory::getDate()->modify("-$days days")->toSql()))
					->where('JSON_VALID(params)')
					->where('JSON_EXTRACT(params, "$.testing_account")');
				$db->setQuery($query);
				$testAccountsToDelete = $db->loadColumn();

				if (!empty($testAccountsToDelete))
				{
					foreach ($testAccountsToDelete as $testAccount)
					{
						$u = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($testAccount);
						$u->delete();
					}
				}
			}

			// Then get all testing_account that lastvisitdate is older than $daysToReminderAfter
			if ($daysToReminderAfter > 0)
			{
				$days = -1 * $daysToReminderAfter;

				$query->clear()
					->select('id,email,name,lastvisitDate')
					->from($db->quoteName('#__users'))
					->where($db->quoteName('lastvisitdate') . ' < ' . $db->quote(Factory::getDate()->modify("-$days days")->toSql()))
					->where('JSON_VALID(params)')
					->where('JSON_EXTRACT(params, "$.testing_account")')
					->where('JSON_EXTRACT(params, "$.reminder_sent") IS NULL');
				$db->setQuery($query);
				$testAccountsToReminder = $db->loadAssocList();

				if (!empty($testAccountsToReminder))
				{
					$mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

					$subject = 'PLG_TASK_DELETETESTACCOUNTS_EMAIL_SUBJECT';
					$body    = 'PLG_TASK_DELETETESTACCOUNTS_EMAIL_BODY';

					$data = [
						'sitename'           => $app->get('sitename'),
						'days_before_delete' => $daysToDeleteAfter - $daysToReminderAfter,
					];

					$subject = Text::sprintf($subject, $data['sitename'], $data['days_before_delete']);

					require_once JPATH_ROOT . '/components/com_emundus/helpers/emails.php';
					$logo = \EmundusHelperEmails::getLogo();
					$logo = str_replace('/administrator', '', $logo);

					foreach ($testAccountsToReminder as $testAccount)
					{
						$data['name']             = $testAccount['name'];
						$data['date_of_deletion'] = Factory::getDate($testAccount['lastvisitDate'])->modify("$daysToDeleteAfter days")->format('d/m/Y');
						$body                     = Text::sprintf($body, $data['name'], $data['sitename'], $data['days_before_delete'], $data['date_of_deletion'], $data['sitename']);

						$query->clear()
							->select($db->quoteName('Template'))
							->from($db->quoteName('#__emundus_email_templates'))
							->where($db->quoteName('id') . ' = 1');
						$db->setQuery($query);
						$template = $db->loadResult();

						$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/", "/\[SITE_NAME\]/"], [$subject, $body, $data['sitename']], $template);

						$body = preg_replace("/\[LOGO\]/", $logo, $body);

						// Set sender
						$sender = [
							$app->get('mailfrom'),
							$app->get('fromname')
						];

						$mailer->setSender($sender);
						$mailer->addReplyTo($app->get('mailfrom'), $app->get('fromname'));
						$mailer->addRecipient($testAccount['email']);
						$mailer->setSubject($subject);
						$mailer->isHTML(true);
						$mailer->Encoding = 'base64';
						$mailer->setBody($body);

						$send = $mailer->Send();

						if ($send)
						{
							$u = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($testAccount['id']);
							$u->setParam('reminder_sent', 1);

							if (!$u->save())
							{
								$this->logTask('Error saving reminder_sent param');
							}
						}
					}
				}
			}
		}
		catch (\RuntimeException $e)
		{
			// Ignore it
			return Status::KNOCKOUT;
		}

		$this->logTask('Delete test accounts end');

		return Status::OK;
	}
}
