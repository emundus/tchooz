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

\defined('_JEXEC') or die;

/**
 * Generates procedural campaigns distributed across the showcase programs so a
 * large, realistic catalogue can be produced (hundreds of campaigns) instead of
 * the fixed handful declared in {@see \Tchooz\Fixtures\SampleData\Data\ProgramFixtures}.
 *
 * Idempotent by `alias`: each generated campaign carries a stable index-based
 * alias (`sample-campaign-<n>`), so re-running only fills the gap up to $count.
 */
final class CampaignSeeder
{
	private const ALIAS_PREFIX = 'sample-campaign-';
	private const PROFILE_ID   = 1001;

	private const DEGREES = ['Licence', 'Master 1', 'Master 2', 'Doctorat', 'Appel à projets', 'Bourse', 'Formation continue'];

	private const FIELDS = [
		'Économie', 'Journalisme', 'Informatique', 'Droit', 'Biologie', 'Physique', 'Sciences politiques',
		'Histoire', 'Design', 'Marketing', 'Ingénierie', 'Médecine', 'Psychologie', 'Mathématiques',
		'Architecture', 'Environnement', 'Robotique', 'Data Science', 'Communication', 'Géographie',
	];

	private const SESSIONS = ['', ' — 1ʳᵉ session', ' — 2ᵉ session', ' — Session de printemps', ' — Session d\'automne'];

	private DatabaseInterface $db;

	public function __construct(DatabaseInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * Generate $count campaigns spread across the given programs.
	 *
	 * @param   array<int, array{id: int, code: string}>  $programs  Target programs (id + code).
	 * @param   int                                       $userId    Owner set on created campaigns.
	 * @param   int                                       $count     How many campaigns to reach in total.
	 *
	 * @return  int  Number of campaigns actually inserted.
	 */
	public function seed(array $programs, int $userId, int $count): int
	{
		if (empty($programs) || $count < 1)
		{
			return 0;
		}

		$existing = $this->existingSampleCount();
		$created  = 0;

		for ($index = $existing + 1; $index <= $count; $index++)
		{
			$program  = $programs[($index - 1) % count($programs)];
			$campaign = $this->buildCampaign($program, $userId, $index);

			if ($this->db->insertObject('#__emundus_setup_campaigns', $campaign))
			{
				$created++;
			}
		}

		return $created;
	}

	/**
	 * Build one campaign row. Most campaigns are published so seeded dossiers can
	 * be distributed across them; a minority stay unpublished for realism.
	 */
	private function buildCampaign(array $program, int $userId, int $index): object
	{
		$degree = self::DEGREES[array_rand(self::DEGREES)];
		$field  = self::FIELDS[array_rand(self::FIELDS)];
		$label  = sprintf('[TEST] %s %s%s', $degree, $field, self::SESSIONS[array_rand(self::SESSIONS)]);

		[$startDate, $endDate, $academicYear] = $this->buildPeriod();

		return (object) [
			'label'             => $label,
			'description'       => '',
			'short_description' => '<p>' . $label . '</p>',
			'start_date'        => $startDate,
			'end_date'          => $endDate,
			'profile_id'        => self::PROFILE_ID,
			'training'          => $program['code'],
			'program_id'        => $program['id'],
			'user'              => $userId,
			'year'              => $academicYear,
			'published'         => (rand(1, 10) <= 9) ? 1 : 0,
			'pinned'            => 0,
			'alias'             => self::ALIAS_PREFIX . $index,
		];
	}

	/**
	 * Random realistic period: a start in the last ~2 years, an end 1..12 months
	 * later, and the matching academic year label (e.g. "2024-2025").
	 *
	 * @return  array{0: string, 1: string, 2: string}
	 */
	private function buildPeriod(): array
	{
		$startOffsetDays = rand(-730, 30);
		$durationDays    = rand(30, 365);

		$start = strtotime($startOffsetDays . ' days');
		$end   = strtotime('+' . $durationDays . ' days', $start);

		$startYear = (int) date('Y', $start);
		$year      = $startYear . '-' . ($startYear + 1);

		return [
			date('Y-m-d H:i:s', $start),
			date('Y-m-d H:i:s', $end),
			$year,
		];
	}

	/**
	 * Count campaigns previously produced by this seeder (alias prefix).
	 */
	private function existingSampleCount(): int
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__emundus_setup_campaigns'))
			->where($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote(self::ALIAS_PREFIX . '%'));
		$this->db->setQuery($query);

		return (int) $this->db->loadResult();
	}
}
