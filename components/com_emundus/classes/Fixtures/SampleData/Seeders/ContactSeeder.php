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
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Enums\Contacts\GenderEnum;
use Tchooz\Enums\Contacts\VerifiedStatusEnum;
use Tchooz\Fixtures\SampleData\Data\FakeData;
use Tchooz\Repositories\Contacts\ContactRepository;

\defined('_JEXEC') or die;

/**
 * Seeds sample contacts through {@see ContactRepository::flush()} so the real save
 * path (validation, duplicate-email guard, relations) is exercised. When
 * organization ids are provided, each contact is associated to one at random.
 *
 * Idempotent by email: every contact uses a stable
 * `<name>+<n>@sample-contact.emundus.fr` address, so re-running only fills the
 * gap up to $count.
 */
final class ContactSeeder
{
	private const EMAIL_DOMAIN = '@sample-contact.emundus.fr';

	private DatabaseInterface $db;
	private FakeData          $fake;
	private ContactRepository $repository;

	public function __construct(DatabaseInterface $db, FakeData $fake)
	{
		$this->db         = $db;
		$this->fake       = $fake;
		$this->repository = new ContactRepository();
	}

	/**
	 * Create contacts until $count sample rows exist in total.
	 *
	 * @param   int    $count            Target number of sample contacts.
	 * @param   int[]  $organizationIds  Organizations to associate contacts with (optional).
	 *
	 * @return  int  Number of contacts actually created.
	 */
	public function seed(int $count, array $organizationIds = []): int
	{
		if ($count < 1)
		{
			return 0;
		}

		$genders  = GenderEnum::cases();
		$existing = $this->existingSampleCount();
		$created  = 0;

		for ($index = $existing + 1; $index <= $count; $index++)
		{
			$firstname = $this->fake->firstName();
			$lastname  = $this->fake->lastName();

			$entity = new ContactEntity(
				email: $this->fake->uniqueContactEmail($firstname, $lastname, $index),
				lastname: $lastname,
				firstname: $firstname,
				phone_1: $this->fake->phone(),
				gender: $genders[array_rand($genders)],
				fonction: $this->fake->fonction(),
				service: $this->fake->service(),
				organizations: $this->pickOrganizations($organizationIds),
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
	 * A single random organization (as a lightweight stub carrying only its id,
	 * which is all ContactRepository::flush() reads for association).
	 *
	 * @param   int[]  $organizationIds
	 *
	 * @return  OrganizationEntity[]
	 */
	private function pickOrganizations(array $organizationIds): array
	{
		if (empty($organizationIds))
		{
			return [];
		}

		$orgId = $organizationIds[array_rand($organizationIds)];

		return [new OrganizationEntity(id: $orgId, name: 'stub')];
	}

	private function existingSampleCount(): int
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__emundus_contacts'))
			->where($this->db->quoteName('email') . ' LIKE ' . $this->db->quote('%' . self::EMAIL_DOMAIN));
		$this->db->setQuery($query);

		return (int) $this->db->loadResult();
	}
}
