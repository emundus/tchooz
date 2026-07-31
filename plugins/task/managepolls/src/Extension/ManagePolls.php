<?php

namespace Joomla\Plugin\Task\ManagePolls\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\Poll\PollRepository;
use Tchooz\Services\Poll\PollService;

\defined('_JEXEC') or die;

/**
 * Scheduled task that opens polls whose start date has been reached and closes polls
 * whose end date has passed. State transitions are delegated to {@see PollService}.
 */
class ManagePolls extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	protected const TASKS_MAP = [
		'plg_task_managepolls' => [
			'langConstPrefix' => 'PLG_TASK_MANAGEPOLLS',
			'method'          => 'managePolls',
		],
	];

	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList' => 'advertiseRoutines',
			'onExecuteTask'     => 'standardRoutineHandler',
		];
	}

	public function __construct($config = [])
	{
		parent::__construct($config);
		Log::addLogger(['text_file' => 'com_emundus.poll.php'], Log::ALL, ['com_emundus.poll']);
	}

	/**
	 * Open every due upcoming poll and close every expired open poll.
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return int
	 */
	public function managePolls(ExecuteTaskEvent $event): int
	{
		$pollAddon = (new AddonRepository())->getByName('poll');
		if ($pollAddon === null || !$pollAddon->isActivated())
		{
			Log::add('Poll addon disabled, skipping managePolls task', Log::INFO, 'com_emundus.poll');

			return Status::OK;
		}
		

		$langCode = ComponentHelper::getParams('com_languages')->get('site', 'fr-FR');
		$lang = Factory::$language;
		$lang->setDefault($langCode);
		$lang->load('com_emundus', JPATH_SITE . '/components/com_emundus', $langCode);
		$lang->load('', JPATH_SITE, $langCode);

		$pollRepository = new PollRepository();
		$pollService    = new PollService();

		$hasError = false;

		$hasError = !$this->openDuePolls($pollRepository, $pollService) || $hasError;
		$hasError = !$this->closeExpiredPolls($pollRepository, $pollService) || $hasError;

		return $hasError ? Status::INVALID_EXIT : Status::OK;
	}

	/**
	 * @return bool  False when at least one poll failed to open.
	 */
	private function openDuePolls(PollRepository $pollRepository, PollService $pollService): bool
	{
		$success = true;

		foreach ($pollRepository->getPollIdsToOpen() as $pollId)
		{
			try
			{
				$pollService->runPoll(pollIds: $pollId, notify: true);
				Log::add('Poll ' . $pollId . ' opened by scheduled task', Log::INFO, 'com_emundus.poll');
			}
			catch (\Throwable $e)
			{
				$success = false;
				Log::add('Error opening poll ' . $pollId . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.poll');
			}
		}

		return $success;
	}

	/**
	 * @return bool  False when at least one poll failed to close.
	 */
	private function closeExpiredPolls(PollRepository $pollRepository, PollService $pollService): bool
	{
		$success = true;

		foreach ($pollRepository->getPollIdsToClose() as $pollId)
		{
			try
			{
				$pollService->closePoll($pollId);
				Log::add('Poll ' . $pollId . ' closed by scheduled task', Log::INFO, 'com_emundus.poll');
			}
			catch (\Throwable $e)
			{
				$success = false;
				Log::add('Error closing poll ' . $pollId . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.poll');
			}
		}

		return $success;
	}
}
