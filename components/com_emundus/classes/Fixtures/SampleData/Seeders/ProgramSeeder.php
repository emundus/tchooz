<?php
/**
 * @package     Tchooz\Fixtures\SampleData\Seeders
 * @subpackage
 *
 * @copyright   (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Fixtures\SampleData\Seeders;

use Joomla\Database\DatabaseInterface;
use Tchooz\Fixtures\SampleData\Data\ProgramFixtures;

\defined('_JEXEC') or die;

/**
 * Seeds the showcase programs + campaigns. Idempotent by program `code`.
 */
final class ProgramSeeder
{
	/**
	 * Code prefix carried by every procedurally generated program, so runs are
	 * idempotent (re-running only fills the gap up to $count) and the campaign
	 * seeder can recognise them.
	 */
	public const PROCEDURAL_CODE_PREFIX = 'sample-program-';

	private const LEVELS = ['Licence', 'Master 1', 'Master 2', 'Doctorat', 'Appel à projets'];

	private const FAMILIES = [
		'Économie', 'Journalisme', 'Informatique', 'Droit', 'Biologie', 'Physique',
		'Sciences politiques', 'Histoire', 'Design', 'Marketing', 'Ingénierie',
		'Médecine', 'Psychologie', 'Mathématiques', 'Architecture', 'Environnement',
	];

	private DatabaseInterface $db;

	public function __construct(DatabaseInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * @param   int  $allRightsGroupId  Group that receives access to every seeded program.
	 * @param   int  $userId            Owner used on created campaigns.
	 *
	 * @return  int  Number of programs created (existing ones are skipped).
	 */
	public function seed(int $allRightsGroupId, int $userId): int
	{
		$created = 0;

		foreach (ProgramFixtures::programs() as $program)
		{
			if ($this->programExists($program['code']))
			{
				continue;
			}

			$campaigns = $program['campaigns'];
			unset($program['campaigns']);

			$program1 = (object) $program;
			if (!$this->db->insertObject('#__emundus_setup_programmes', $program1))
			{
				throw new \RuntimeException('Failed to insert program ' . $program['code']);
			}
			$programId = $this->db->insertid();

			$obj = (object) [
				'parent_id' => $allRightsGroupId,
				'course'    => $program['code'],
			];
			$this->db->insertObject('#__emundus_setup_groups_repeat_course', $obj);

			foreach ($campaigns as $campaign)
			{
				$campaign['user']       = $userId;
				$campaign['program_id'] = $programId;
				$campaign['profile_id'] = 1001;

				$campaign1 = (object) $campaign;
				if (!$this->db->insertObject('#__emundus_setup_campaigns', $campaign1))
				{
					throw new \RuntimeException('Failed to insert campaign ' . $campaign['alias']);
				}
			}

			$created++;
		}

		return $created;
	}

	/**
	 * Generate $count procedural programs on top of the showcase catalogue, so a
	 * large program list can be produced instead of the fixed handful declared in
	 * {@see ProgramFixtures}. Idempotent by code prefix: re-running only fills the
	 * gap up to $count. Procedural programs carry no campaigns of their own — the
	 * CampaignSeeder distributes procedural campaigns across them when requested.
	 *
	 * @param   int  $allRightsGroupId  Group that receives access to every seeded program.
	 * @param   int  $count             How many procedural programs to reach in total.
	 *
	 * @return  int  Number of programs actually inserted.
	 */
	public function seedProcedural(int $allRightsGroupId, int $count): int
	{
		if ($count < 1)
		{
			return 0;
		}

		$existing = $this->existingProceduralCount();
		$created  = 0;

		for ($index = $existing + 1; $index <= $count; $index++)
		{
			$program = $this->buildProceduralProgram($index);

			if (!$this->db->insertObject('#__emundus_setup_programmes', $program))
			{
				throw new \RuntimeException('Failed to insert program ' . $program->code);
			}

			$obj = (object) [
				'parent_id' => $allRightsGroupId,
				'course'    => $program->code,
			];
			$this->db->insertObject('#__emundus_setup_groups_repeat_course', $obj);

			$created++;
		}

		return $created;
	}

	/**
	 * Build one procedural program row with a stable, index-based code.
	 */
	private function buildProceduralProgram(int $index): object
	{
		$level  = self::LEVELS[array_rand(self::LEVELS)];
		$family = self::FAMILIES[array_rand(self::FAMILIES)];

		return (object) [
			'code'                     => self::PROCEDURAL_CODE_PREFIX . $index,
			'label'                    => sprintf('[TEST] %s %s', $level, $family),
			'published'                => 1,
			'programmes'               => $family,
			'synthesis'                => '<ul><li><strong>[APPLICANT_NAME]</strong></li><li><a href="mailto:[EMAIL]">[EMAIL]</a></li></ul>',
			'fabrik_group_id'          => 551,
			'fabrik_decision_group_id' => 552,
			'apply_online'             => 1,
			'ordering'                 => $index,
		];
	}

	/**
	 * Count programs previously produced by this seeder (code prefix).
	 */
	private function existingProceduralCount(): int
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__emundus_setup_programmes'))
			->where($this->db->quoteName('code') . ' LIKE ' . $this->db->quote(self::PROCEDURAL_CODE_PREFIX . '%'));
		$this->db->setQuery($query);

		return (int) $this->db->loadResult();
	}

	private function programExists(string $code): bool
	{
		$query = $this->db->getQuery(true)
			->select('id')
			->from($this->db->quoteName('#__emundus_setup_programmes'))
			->where($this->db->quoteName('code') . ' = :code')
			->bind(':code', $code);
		$this->db->setQuery($query);

		return !empty($this->db->loadResult());
	}
}
