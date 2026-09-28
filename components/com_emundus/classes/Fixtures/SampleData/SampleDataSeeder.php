<?php
/**
 * @package     Tchooz\Fixtures\SampleData
 * @subpackage
 *
 * @copyright   (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Fixtures\SampleData;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Fixtures\SampleData\Data\FakeData;
use Tchooz\Fixtures\SampleData\Data\ProgramFixtures;
use Tchooz\Fixtures\SampleData\Seeders\ApplicationFileSeeder;
use Tchooz\Fixtures\SampleData\Seeders\CampaignSeeder;
use Tchooz\Fixtures\SampleData\Seeders\ContactSeeder;
use Tchooz\Fixtures\SampleData\Seeders\OrganizationSeeder;
use Tchooz\Fixtures\SampleData\Seeders\ProgramSeeder;
use Tchooz\Fixtures\SampleData\Seeders\TagSeeder;
use Tchooz\Fixtures\SampleData\Seeders\UploadSeeder;
use Tchooz\Fixtures\SampleData\Seeders\UserSeeder;

\defined('_JEXEC') or die;

/**
 * Orchestrates the full sample-data generation: programs, users, dossiers,
 * uploads and tags. Callable from the CLI command or the sampledata plugin.
 */
final class SampleDataSeeder
{
	private const APPLICANT_PROFILE = 1001;
	private const JOOMLA_GROUPS      = [2];
	private const DEFAULT_BATCH      = 500;

	private DatabaseInterface     $db;
	private FakeData              $fake;
	private ProgramSeeder         $programSeeder;
	private CampaignSeeder        $campaignSeeder;
	private OrganizationSeeder    $organizationSeeder;
	private ContactSeeder         $contactSeeder;
	private UserSeeder            $userSeeder;
	private ApplicationFileSeeder $fileSeeder;
	private UploadSeeder          $uploadSeeder;
	private TagSeeder             $tagSeeder;

	private int $allRightsGroup;
	private int $ownerUserId;
	private int $attachmentId;

	public function __construct(DatabaseInterface $db)
	{
		$this->db   = $db;
		$this->fake = new FakeData($db);

		$this->programSeeder      = new ProgramSeeder($db);
		$this->campaignSeeder     = new CampaignSeeder($db);
		$this->organizationSeeder = new OrganizationSeeder($db, $this->fake);
		$this->contactSeeder      = new ContactSeeder($db, $this->fake);
		$this->userSeeder         = new UserSeeder($db, $this->fake);
		$this->fileSeeder    = new ApplicationFileSeeder($db, $this->fake, new ApplicationFileRepository());
		$this->uploadSeeder  = new UploadSeeder($db);
		$this->tagSeeder     = new TagSeeder($db);

		$params               = ComponentHelper::getParams('com_emundus');
		$this->allRightsGroup = (int) $params->get('all_rights_group', 1);
		$this->ownerUserId    = (int) $params->get('automated_task_user', 62);
		$this->attachmentId   = (int) $params->get('sampledata_attachment_id', 12);
	}

	/**
	 * Seed the showcase catalogue only (small, idempotent).
	 *
	 * @return  int  Programs created.
	 */
	public function seedPrograms(): int
	{
		return $this->programSeeder->seed($this->allRightsGroup, $this->ownerUserId);
	}

	/**
	 * Full generation.
	 *
	 * @param   int            $userCount       Applicants to create.
	 * @param   int            $maxFilesPerUser 1..N dossiers per applicant.
	 * @param   bool           $fast            Skip Joomla events (faster).
	 * @param   bool           $withUploads     Attach a sample PDF to dossiers.
	 * @param   int            $batchSize       Users per transactional batch.
	 * @param   callable|null  $onProgress      fn(string $phase, int $done, int $total).
	 * @param   int            $campaignCount     Total procedural campaigns to reach (0 = showcase only).
	 * @param   int            $organizationCount Total sample organizations to reach (0 = none).
	 * @param   int            $contactCount      Total sample contacts to reach (0 = none).
	 * @param   int            $programCount      Total procedural programs to reach (0 = showcase only).
	 *
	 * @return  array<string, int>  Stats: programs, campaigns, organizations, contacts, users, files, uploads, tags.
	 */
	public function run(
		int $userCount,
		int $maxFilesPerUser = 1,
		bool $fast = false,
		bool $withUploads = false,
		int $batchSize = self::DEFAULT_BATCH,
		?callable $onProgress = null,
		int $campaignCount = 0,
		int $organizationCount = 0,
		int $contactCount = 0,
		int $programCount = 0
	): array
	{
		$stats = ['programs' => 0, 'campaigns' => 0, 'organizations' => 0, 'contacts' => 0, 'users' => 0, 'files' => 0, 'uploads' => 0, 'tags' => 0];

		// Large runs (5000+) issue tens of thousands of queries. A dev query
		// monitor (e.g. Joomla DebugMonitor) keeps every query + backtrace in
		// memory, which the OS eventually OOM-kills. Detach it and lift the CLI
		// limits so the run can complete.
		$this->prepareForBulkRun();

		$stats['programs'] = $this->seedPrograms();
		if ($programCount > 0)
		{
			$stats['programs'] += $this->programSeeder->seedProcedural($this->allRightsGroup, $programCount);
		}

		$stats['campaigns'] = $campaignCount > 0
			? $this->campaignSeeder->seed($this->loadSeededPrograms(), $this->ownerUserId, $campaignCount)
			: 0;

		// Organizations first, so contacts can be associated to them.
		$stats['organizations'] = $organizationCount > 0
			? $this->organizationSeeder->seed($organizationCount)
			: 0;
		$stats['contacts'] = $contactCount > 0
			? $this->contactSeeder->seed($contactCount, $this->organizationSeeder->loadSeededIds())
			: 0;

		$campaignWeights = $this->buildCampaignWeights();
		$statusWeights   = $this->buildStatusWeights();

		if (empty($campaignWeights))
		{
			throw new \RuntimeException('No published campaigns found — seed programs first.');
		}

		$startIndex = $this->existingSampleUserCount();
		$remaining  = $userCount;
		$batchSize  = max(1, $batchSize);

		while ($remaining > 0)
		{
			$batchCount = min($batchSize, $remaining);

			$batch = $this->runBatch($batchCount, $maxFilesPerUser, $campaignWeights, $statusWeights, $fast, $withUploads, $startIndex);

			$stats['users']   += $batch['users'];
			$stats['files']   += $batch['files'];
			$stats['uploads'] += $batch['uploads'];
			$stats['tags']    += $batch['tags'];

			$startIndex += $batchCount;
			$remaining  -= $batchCount;

			if ($onProgress !== null)
			{
				$onProgress('users', $userCount - $remaining, $userCount);
			}

			// Reclaim memory between batches to survive large (5000+) runs.
			// Access::clearStatics() drops the per-user ACL identity/group caches
			// that otherwise grow with every distinct applicant and OOM-kill the run.
			unset($batch);
			Access::clearStatics();
			gc_collect_cycles();
		}

		return $stats;
	}

	/**
	 * Remove memory-retaining collaborators and lift CLI limits before a large run.
	 *
	 * The query monitor is the main culprit: in debug mode Joomla records every
	 * executed query (with a stack trace) for the profiler, which grows unbounded
	 * across a 5000-dossier run and gets the process OOM-killed. Bulk seeding does
	 * not need that record, so we detach it for the duration of the run.
	 */
	private function prepareForBulkRun(): void
	{
		if (\PHP_SAPI === 'cli')
		{
			@set_time_limit(0);
		}

		if (method_exists($this->db, 'setMonitor'))
		{
			$this->db->setMonitor(null);
		}
	}

	/**
	 * One transactional batch (transaction only in fast mode; events-on path
	 * lets the dispatched Joomla events manage their own transactions).
	 */
	private function runBatch(
		int $batchCount,
		int $maxFilesPerUser,
		array $campaignWeights,
		array $statusWeights,
		bool $fast,
		bool $withUploads,
		int $startIndex
	): array
	{
		if ($fast)
		{
			$this->db->transactionStart();
		}

		try
		{
			$userIds = $this->userSeeder->seed($batchCount, self::APPLICANT_PROFILE, self::JOOMLA_GROUPS, $startIndex);
			$fnums   = $this->fileSeeder->seed($userIds, $maxFilesPerUser, $campaignWeights, $statusWeights, $fast);

			$uploads = $withUploads ? $this->uploadSeeder->seed($fnums, $this->attachmentId) : 0;
			$tags    = $this->tagSeeder->seed($fnums);

			if ($fast)
			{
				$this->db->transactionCommit();
			}

			return ['users' => count($userIds), 'files' => count($fnums), 'uploads' => $uploads, 'tags' => $tags];
		}
		catch (\Throwable $e)
		{
			if ($fast)
			{
				$this->db->transactionRollback();
			}

			throw $e;
		}
	}

	/**
	 * Load the seeded programs (id + code) so procedural campaigns can be
	 * distributed across them: the showcase codes declared in ProgramFixtures plus
	 * any procedural programs produced by ProgramSeeder.
	 *
	 * @return  array<int, array{id: int, code: string}>
	 */
	private function loadSeededPrograms(): array
	{
		$codes = array_column(ProgramFixtures::programs(), 'code');

		$query = $this->db->getQuery(true)
			->select([$this->db->quoteName('id'), $this->db->quoteName('code')])
			->from($this->db->quoteName('#__emundus_setup_programmes'))
			->where($this->db->quoteName('code') . ' LIKE ' . $this->db->quote(ProgramSeeder::PROCEDURAL_CODE_PREFIX . '%'));

		if (!empty($codes))
		{
			$quotedCodes = array_map([$this->db, 'quote'], $codes);
			$query->extendWhere('OR', $this->db->quoteName('code') . ' IN (' . implode(',', $quotedCodes) . ')');
		}

		$this->db->setQuery($query);

		$programs = [];
		foreach ($this->db->loadObjectList() ?: [] as $row)
		{
			$programs[] = ['id' => (int) $row->id, 'code' => (string) $row->code];
		}

		return $programs;
	}

	/**
	 * Published campaigns with a random popularity weight (1..10) so a few
	 * campaigns end up much fuller than others — "weighted realistic".
	 *
	 * @return  array<int, int>  campaign_id => weight.
	 */
	private function buildCampaignWeights(): array
	{
		$query = $this->db->getQuery(true)
			->select('id')
			->from($this->db->quoteName('#__emundus_setup_campaigns'))
			->where($this->db->quoteName('published') . ' = 1');
		$this->db->setQuery($query);

		$weights = [];
		foreach ($this->db->loadColumn() ?: [] as $campaignId)
		{
			$weights[(int) $campaignId] = rand(1, 10);
		}

		return $weights;
	}

	/**
	 * Status steps skewed toward the earliest states (more drafts/submitted
	 * than accepted).
	 *
	 * @return  array<int, int>  status_step => weight.
	 */
	private function buildStatusWeights(): array
	{
		$query = $this->db->getQuery(true)
			->select('DISTINCT step')
			->from($this->db->quoteName('#__emundus_setup_status'))
			->order($this->db->quoteName('step') . ' ASC');
		$this->db->setQuery($query);

		$steps = array_map('intval', $this->db->loadColumn() ?: []);
		if (empty($steps))
		{
			return [0 => 1];
		}

		$weights = [];
		$count   = count($steps);
		foreach ($steps as $index => $step)
		{
			$weights[$step] = $count - $index;
		}

		return $weights;
	}

	private function existingSampleUserCount(): int
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__users'))
			->where($this->db->quoteName('email') . ' LIKE ' . $this->db->quote('%@sample.emundus.fr'));
		$this->db->setQuery($query);

		return (int) $this->db->loadResult();
	}
}
