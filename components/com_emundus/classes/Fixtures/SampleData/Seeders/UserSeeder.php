<?php
/**
 * @package     Tchooz\Fixtures\SampleData\Seeders
 * @subpackage
 *
 * @copyright   (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Fixtures\SampleData\Seeders;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseInterface;
use Tchooz\Fixtures\SampleData\Data\FakeData;

\defined('_JEXEC') or die;

/**
 * Creates Joomla users + their emundus_users profile in batches.
 *
 * Each Joomla user is inserted individually (we need its id for the child rows),
 * then the emundus_users and usergroup-map rows are flushed as batched
 * multi-row inserts to keep the 5000-scale run fast.
 */
final class UserSeeder
{
	private DatabaseInterface $db;
	private FakeData          $fake;

	public function __construct(DatabaseInterface $db, FakeData $fake)
	{
		$this->db   = $db;
		$this->fake = $fake;
	}

	/**
	 * @param   int           $count        Number of users to create.
	 * @param   int           $profileId    eMundus applicant profile id.
	 * @param   int[]         $joomlaGroups Joomla usergroup ids to map (e.g. [2] Registered).
	 * @param   int           $startIndex   Offset used to keep emails unique across runs.
	 * @param   callable|null $onProgress   fn(int $done) called after each user.
	 *
	 * @return  int[]  Created Joomla user ids.
	 */
	public function seed(int $count, int $profileId, array $joomlaGroups, int $startIndex = 0, ?callable $onProgress = null): array
	{
		$now       = date('Y-m-d H:i:s');
		$userIds   = [];
		$emundus   = [];
		$groupMaps = [];

		for ($i = 0; $i < $count; $i++)
		{
			$firstname = $this->fake->firstName();
			$lastname  = $this->fake->lastName();
			$email     = $this->fake->uniqueEmail($firstname, $lastname, $startIndex + $i);

			$user = (object) [
				'name'          => $firstname . ' ' . $lastname,
				'username'      => $email,
				'email'         => $email,
				'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
				'block'         => 0,
				'sendEmail'     => 0,
				'registerDate'  => $now,
				'lastvisitDate' => $now,
				'activation'    => 1,
				'params'        => '{}',
			];

			if (!$this->db->insertObject('#__users', $user))
			{
				throw new \RuntimeException('Failed to insert user ' . $email);
			}
			$userId    = (int) $this->db->insertid();
			$userIds[] = $userId;

			$emundus[] = (object) [
				'user_id'      => $userId,
				'registerDate' => $now,
				'firstname'    => $firstname,
				'lastname'     => $lastname,
				'profile'      => $profileId,
			];

			foreach ($joomlaGroups as $groupId)
			{
				$groupMaps[] = (object) ['user_id' => $userId, 'group_id' => (int) $groupId];
			}

			if ($onProgress !== null)
			{
				$onProgress($i + 1);
			}
		}

		$this->batchInsert('#__emundus_users', $emundus);
		$this->batchInsert('#__user_usergroup_map', $groupMaps);

		return $userIds;
	}

	/**
	 * Insert rows in chunks using a single multi-row statement per chunk.
	 *
	 * @param   object[]  $rows
	 */
	private function batchInsert(string $table, array $rows, int $chunkSize = 500): void
	{
		if (empty($rows))
		{
			return;
		}

		$columns = array_keys(get_object_vars($rows[0]));

		foreach (array_chunk($rows, $chunkSize) as $chunk)
		{
			$query = $this->db->getQuery(true)
				->insert($this->db->quoteName($table))
				->columns($this->db->quoteName($columns));

			foreach ($chunk as $row)
			{
				$values = [];
				foreach ($columns as $column)
				{
					$value    = $row->$column;
					$values[] = is_int($value) ? $value : $this->db->quote($value);
				}
				$query->values(implode(',', $values));
			}

			$this->db->setQuery($query);
			$this->db->execute();
		}
	}
}
