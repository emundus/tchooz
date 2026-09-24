<?php
/**
 * @package     Tchooz\Fixtures\SampleData\Seeders
 * @subpackage
 *
 * @copyright   (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Fixtures\SampleData\Seeders;

use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Fixtures\SampleData\Data\FakeData;

\defined('_JEXEC') or die;

/**
 * Creates application files (dossiers) for a set of applicants.
 *
 * Two paths:
 *  - events ON  (default): builds an ApplicationFileEntity and calls
 *    ApplicationFileRepository::flush(), which fires the workflow/status
 *    Joomla events. Correct but slower.
 *  - fast (--fast): inserts the candidature row directly then reuses
 *    ApplicationFileRepository::insertDatas() for the form tables, skipping
 *    every event. Much faster for raw volume.
 */
final class ApplicationFileSeeder
{
	private DatabaseInterface         $db;
	private FakeData                  $fake;
	private ApplicationFileRepository $repository;

	/** @var array<int, User> Small per-run cache to avoid re-loading applicants. */
	private array $userCache = [];

	public function __construct(DatabaseInterface $db, FakeData $fake, ApplicationFileRepository $repository)
	{
		$this->db         = $db;
		$this->fake       = $fake;
		$this->repository = $repository;
	}

	/**
	 * @param   int[]            $userIds         Applicant ids.
	 * @param   int              $maxFilesPerUser 1..N dossiers per applicant.
	 * @param   array<int, int>  $campaignWeights campaign_id => weight.
	 * @param   array<int, int>  $statusWeights   status_step => weight.
	 * @param   bool             $fast            Skip Joomla events when true.
	 * @param   callable|null    $onProgress      fn(int $filesCreated) after each file.
	 *
	 * @return  array<string, int>  fnum => applicant_id map of created files.
	 */
	public function seed(array $userIds, int $maxFilesPerUser, array $campaignWeights, array $statusWeights, bool $fast = false, ?callable $onProgress = null): array
	{
		$created = [];

		if (empty($campaignWeights))
		{
			return $created;
		}

		foreach ($userIds as $userId)
		{
			$files = $maxFilesPerUser > 1 ? rand(1, $maxFilesPerUser) : 1;

			for ($f = 0; $f < $files; $f++)
			{
				$campaignId = (int) $this->weightedPick($campaignWeights);
				$statusStep = (int) $this->weightedPick($statusWeights);
				$fnum       = $this->generateFnum($campaignId);

				if ($fast)
				{
					$this->createFast((int) $userId, $campaignId, $statusStep, $fnum);
				}
				else
				{
					$this->createWithEvents((int) $userId, $campaignId, $statusStep, $fnum);
				}

				$created[$fnum] = (int) $userId;

				if ($onProgress !== null)
				{
					$onProgress(count($created));
				}
			}
		}

		// Release loaded User objects so memory does not grow across batches.
		$this->userCache = [];

		return $created;
	}

	private function createWithEvents(int $userId, int $campaignId, int $statusStep, string $fnum): void
	{
		$entity = new ApplicationFileEntity(
			user: $this->user($userId),
			fnum: $fnum,
			status: $statusStep,
			campaign_id: $campaignId,
			published: 1,
			data: $this->fakeDataFor($userId)
		);

		$this->repository->flush($entity, $userId);
	}

	private function createFast(int $userId, int $campaignId, int $statusStep, string $fnum): void
	{
		$candidature = (object) [
			'date_time'           => date('Y-m-d H:i:s'),
			'applicant_id'        => $userId,
			'user_id'             => $userId,
			'campaign_id'         => $campaignId,
			'fnum'                => $fnum,
			'status'              => $statusStep,
			'published'           => 1,
			'form_progress'       => 0,
			'attachment_progress' => 0,
		];

		if (!$this->db->insertObject('#__emundus_campaign_candidature', $candidature))
		{
			throw new \RuntimeException('Failed to create candidature ' . $fnum);
		}
		$ccid = (int) $this->db->insertid();

		foreach ($this->fakeDataFor($userId) as $table => $data)
		{
			$this->repository->insertDatas($data, $table, $fnum, $ccid, $userId);
		}
	}

	private function fakeDataFor(int $userId): array
	{
		$user = $this->user($userId);
		$name = explode(' ', trim((string) $user->name), 2);

		return $this->fake->applicationData($name[0] ?? 'Sample', $name[1] ?? 'User');
	}

	private function generateFnum(int $campaignId): string
	{
		return date('YmdHis') . str_pad((string) $campaignId, 7, '0', STR_PAD_LEFT) . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
	}

	private function user(int $userId): User
	{
		if (!isset($this->userCache[$userId]))
		{
			$this->userCache[$userId] = Factory::getContainer()
				->get(UserFactoryInterface::class)
				->loadUserById($userId);
		}

		return $this->userCache[$userId];
	}

	/**
	 * Pick a key from a weighted map (key => weight).
	 *
	 * @param   array<int|string, int>  $weights
	 *
	 * @return  int|string
	 */
	private function weightedPick(array $weights)
	{
		$total = array_sum($weights);
		if ($total <= 0)
		{
			return array_key_first($weights);
		}

		$roll = random_int(1, $total);
		foreach ($weights as $key => $weight)
		{
			$roll -= $weight;
			if ($roll <= 0)
			{
				return $key;
			}
		}

		return array_key_last($weights);
	}
}
