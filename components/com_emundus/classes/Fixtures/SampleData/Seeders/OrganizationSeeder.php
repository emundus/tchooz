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
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Enums\Contacts\VerifiedStatusEnum;
use Tchooz\Fixtures\SampleData\Data\FakeData;
use Tchooz\Repositories\Contacts\OrganizationRepository;

\defined('_JEXEC') or die;

/**
 * Seeds sample organizations through {@see OrganizationRepository::flush()} so the
 * real save path (validation + relations) is exercised.
 *
 * Idempotent by `identifier_code`: every organization carries a stable
 * `SAMPLE-ORG-<n>` code, so re-running only fills the gap up to $count.
 */
final class OrganizationSeeder
{
	private const CODE_PREFIX = 'SAMPLE-ORG-';

	private DatabaseInterface      $db;
	private FakeData               $fake;
	private OrganizationRepository $repository;

	public function __construct(DatabaseInterface $db, FakeData $fake)
	{
		$this->db         = $db;
		$this->fake       = $fake;
		$this->repository = new OrganizationRepository();
	}

	/**
	 * Create organizations until $count sample rows exist in total.
	 *
	 * @param   int  $count  Target number of sample organizations.
	 *
	 * @return  int  Number of organizations actually created.
	 */
	public function seed(int $count): int
	{
		if ($count < 1)
		{
			return 0;
		}

		$existing = $this->existingSampleCount();
		$created  = 0;

		for ($index = $existing + 1; $index <= $count; $index++)
		{
			$entity = new OrganizationEntity(
				id: 0,
				name: $this->fake->organizationName() . ' #' . $index,
				description: '<p>Organisation de démonstration générée automatiquement.</p>',
				url_website: 'https://example.org/org-' . $index,
				identifier_code: self::CODE_PREFIX . $index,
				published: true,
				status: VerifiedStatusEnum::VERIFIED
			);

			if ($this->repository->flush($entity))
			{
				$created++;
			}
		}

		return $created;
	}

	/**
	 * Ids of every sample organization (for contact association).
	 *
	 * @return  int[]
	 */
	public function loadSeededIds(): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__emundus_organizations'))
			->where($this->db->quoteName('identifier_code') . ' LIKE ' . $this->db->quote(self::CODE_PREFIX . '%'));
		$this->db->setQuery($query);

		return array_map('intval', $this->db->loadColumn() ?: []);
	}

	private function existingSampleCount(): int
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__emundus_organizations'))
			->where($this->db->quoteName('identifier_code') . ' LIKE ' . $this->db->quote(self::CODE_PREFIX . '%'));
		$this->db->setQuery($query);

		return (int) $this->db->loadResult();
	}
}
