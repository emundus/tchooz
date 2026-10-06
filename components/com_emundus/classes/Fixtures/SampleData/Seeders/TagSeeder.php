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
 * Assigns 0..2 random tags to each provided dossier.
 */
final class TagSeeder
{
	private DatabaseInterface $db;

	public function __construct(DatabaseInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * @param   array<string, int>  $fnumToApplicant  fnum => applicant_id.
	 *
	 * @return  int  Number of tag associations created.
	 */
	public function seed(array $fnumToApplicant): int
	{
		$tagIds = $this->loadTagIds();
		if (empty($tagIds) || empty($fnumToApplicant))
		{
			return 0;
		}

		$created = 0;

		foreach ($fnumToApplicant as $fnum => $applicantId)
		{
			$count = rand(0, 2);
			if ($count === 0)
			{
				continue;
			}

			$picked = (array) array_rand($tagIds, min($count, count($tagIds)));

			foreach ($picked as $key)
			{
				$assoc = (object) [
					'fnum'    => $fnum,
					'id_tag'  => (int) $tagIds[$key],
					'user_id' => (int) $applicantId,
				];

				if ($this->db->insertObject('#__emundus_tag_assoc', $assoc))
				{
					$created++;
				}
			}
		}

		return $created;
	}

	private function loadTagIds(): array
	{
		$query = $this->db->getQuery(true)
			->select('id')
			->from($this->db->quoteName('#__emundus_setup_action_tag'));
		$this->db->setQuery($query);

		return array_map('intval', $this->db->loadColumn() ?: []);
	}
}
