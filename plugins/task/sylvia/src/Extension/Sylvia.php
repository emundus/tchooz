<?php

namespace Joomla\Plugin\Task\Sylvia\Extension;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Synchronizers\Sylvia\SylviaSynchronizer;
use Tchooz\Traits\TraitAutomatedTask;

\defined('_JEXEC') or die;

/**
 * Scheduled task that, once a day, sends every applicant whose file is in one of the
 * configured "Statuts à synchroniser" to the Sylvia student-identification service.
 * The plugin's published state is driven by {@see \Tchooz\Services\Integrations\Handlers\SylviaIntegrationHandler}.
 */
class Sylvia extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;
	use TraitAutomatedTask;

	protected const TASKS_MAP = [
		'plg_task_sylvia' => [
			'langConstPrefix' => 'PLG_TASK_SYLVIA',
			'form'            => 'cron',
			'method'          => 'syncStudents',
		],
	];

	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	public function __construct($config = [])
	{
		parent::__construct($config);
		Log::addLogger(['text_file' => 'com_emundus.sylvia.php'], Log::ALL, ['com_emundus.sylvia']);
	}

	/**
	 * Identify, against Sylvia, every applicant with a file in the configured statuses.
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return int
	 */
	public function syncStudents(ExecuteTaskEvent $event): int
	{
		$synchronizer = (new SynchronizerRepository())->getByType('sylvia');
		if ($synchronizer === null || !$synchronizer->isEnabled())
		{
			Log::add('Sylvia integration disabled, skipping syncStudents task', Log::INFO, 'com_emundus.sylvia');

			return Status::OK;
		}

		$api      = new SylviaSynchronizer();
		$statuses = $api->getStatusesToSync();
		if (empty($statuses))
		{
			Log::add('No statuses configured for Sylvia sync, nothing to do', Log::INFO, 'com_emundus.sylvia');

			return Status::OK;
		}

		if ($event->getArgument('params')->debugmode == 1)
		{
			$debugResult = $api->identifyStudent('sanwald', 'giulia', '15.10.2001');
			Log::add('Sylvia debug identification result : ' . json_encode($debugResult), Log::INFO, 'com_emundus.sylvia');

			return Status::OK;
		}

		$students = $this->getStudentsToSync($statuses);
		if (empty($students))
		{
			Log::add('No applicant found in the configured Sylvia statuses', Log::INFO, 'com_emundus.sylvia');

			return Status::OK;
		}

		if (!class_exists('EmundusModelFiles'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/files.php';
		}

		if (!class_exists('EmundusModelApplication'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/application.php';
		}

		$userRepository = new EmundusUserRepository();
		$mFiles         = new \EmundusModelFiles();
		$mApplication   = new \EmundusModelApplication();
		$tagNotFound    = $api->getTagNotFound();
		$tagDoublon     = $api->getTagDoublon();
		$hasError       = false;

		foreach ($students as $student)
		{
			try
			{
				$this->syncStudent($student, $api, $userRepository, $mFiles, $mApplication, $tagNotFound, $tagDoublon);
			}
			catch (\Throwable $e)
			{
				$hasError = true;
				Log::add('Error syncing applicant ID ' . $student->applicant_id . ' with Sylvia: ' . $e->getMessage(), Log::ERROR, 'com_emundus.sylvia');
			}
		}

		return $hasError ? Status::INVALID_EXIT : Status::OK;
	}

	/**
	 * Identify a single applicant against Sylvia and write back the result (immat number,
	 * transmissible flag, or "not found" / "doublon" tags).
	 */
	private function syncStudent(object $student, SylviaSynchronizer $api, EmundusUserRepository $userRepository, \EmundusModelFiles $mFiles, \EmundusModelApplication $mApplication, ?string $tagNotFound, ?string $tagDoublon): void
	{
		$emundusUser = $userRepository->getByUserId((int) $student->applicant_id);
		if (empty($emundusUser))
		{
			Log::add('IGNORED: No eMundus user found for applicant ID ' . $student->applicant_id . ' (fnum ' . $student->fnum . ')', Log::WARNING, 'com_emundus.sylvia');

			return;
		}

		$birthDate = $emundusUser->getBirthDate();
		if (empty($birthDate))
		{
			Log::add('IGNORED: No birth date defined for applicant ID ' . $student->applicant_id . ' (fnum ' . $student->fnum . ')', Log::WARNING, 'com_emundus.sylvia');

			return;
		}

		$result = $api->identifyStudent(
			$emundusUser->getLastname(),
			$emundusUser->getFirstname(),
			$birthDate->format('d.m.Y')
		);

		if ($result['found'])
		{
			$this->fillElementByAlias($student->fnum, 'no_immat', $result['no_immat']);
			$this->fillElementByAlias($student->fnum, 'transmissible_expert', $result['transmissible_expert'] ? 1 : 0);

			// Student was previously "not found" / "doublon" but is now identified:
			// drop those tags so they don't skew filtering.
			$this->removeSyncTags($student->fnum, $mApplication, $tagNotFound, $tagDoublon);

			return;
		}

		if (!empty($result['homonyms']))
		{
			// Switched from "not found" to "doublon": drop the stale opposite tag.
			$this->removeSyncTags($student->fnum, $mApplication, $tagNotFound);

			if (!empty($tagDoublon))
			{
				$mFiles->tagFile([$student->fnum], [$tagDoublon], $this->getAutomatedTaskUserId());
			}

			return;
		}

		// Switched from "doublon" to "not found": drop the stale opposite tag.
		$this->removeSyncTags($student->fnum, $mApplication, $tagDoublon);

		if (!empty($tagNotFound))
		{
			$mFiles->tagFile([$student->fnum], [$tagNotFound], $this->getAutomatedTaskUserId());
		}

		$this->fillElementByAlias($student->fnum, 'transmissible_expert', 0);
	}

	/**
	 * Remove the given "not found" / "doublon" tags from a file when the applicant's
	 * identification state changes, so stale tags don't make status filtering inconsistent.
	 */
	private function removeSyncTags(string $fnum, \EmundusModelApplication $mApplication, ?string ...$tags): void
	{
		foreach ($tags as $tag)
		{
			if (!empty($tag))
			{
				$mApplication->deleteTag($tag, $fnum);
			}
		}
	}

	/**
	 * Fill the Fabrik element identified by its alias for the given file, if it exists.
	 */
	private function fillElementByAlias(string $fnum, string $alias, mixed $value): void
	{
		$elements = \EmundusHelperFabrik::getElementsByAlias($alias);

		if (!empty($elements))
		{
			$this->fillElement($fnum, $elements[0], $value);
		}
	}

	private function fillElement(string $fnum, object $element, mixed $value): bool
	{
		$db    = $this->getDatabase();
		$query = $db->createQuery();

		$query->clear()
			->select('id, ' . $element->name)
			->from($element->db_table_name)
			->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));
		$db->setQuery($query);
		$row = $db->loadObject();


		if (!empty($row) && !empty($row->id))
		{
			$row->{$element->name} = $value;

			return $db->updateObject($element->db_table_name, $row, 'id');
		}
		else
		{
			$row                   = new \stdClass();
			$row->time_date        = (new \DateTime())->format('Y-m-d H:i:s');
			$row->fnum             = $fnum;
			$row->user             = $this->getAutomatedTaskUserId();
			$row->{$element->name} = $value;

			return $db->insertObject($element->db_table_name, $row);
		}
	}

	/**
	 * Distinct applicants (applicant_id + fnum) whose published file sits in one of the given statuses.
	 *
	 * @param   int[]  $statuses
	 *
	 * @return array<object{applicant_id: int, fnum: string}>
	 */
	private function getStudentsToSync(array $statuses): array
	{
		$db    = $this->getDatabase();
		$query = $db->createQuery();

		$query->select('DISTINCT applicant_id, fnum')
			->from($db->quoteName('#__emundus_campaign_candidature'))
			->whereIn($db->quoteName('status'), $statuses)
			->where($db->quoteName('published') . ' = 1');

		$db->setQuery($query);

		return $db->loadObjectList() ?: [];
	}
}
