<?php
/**
 * @package         Tchooz\Fixtures\SampleData\Data
 * @subpackage
 *
 * @copyright   (C) eMundus
 * @license         GNU General Public License version 2 or later
 */

namespace Tchooz\Fixtures\SampleData\Data;

use Joomla\Database\DatabaseInterface;

\defined('_JEXEC') or die;

/**
 * Random data generator for sample dossiers/users.
 *
 * Lookup lists (nationalities, countries) are loaded ONCE in the constructor
 * instead of being re-queried for every generated row.
 */
final class FakeData
{
	private const FIRST_NAMES = [
		'John', 'Jane', 'Alice', 'Robert', 'Emily', 'Michael', 'Laura', 'David', 'Sophia', 'James',
		'Olivia', 'Lucas', 'Emma', 'Nathan', 'Chloé', 'Hugo', 'Léa', 'Louis', 'Manon', 'Gabriel',
		'Camille', 'Arthur', 'Sarah', 'Jules', 'Inès', 'Adam', 'Zoé', 'Raphaël', 'Julie', 'Maxime',
		'Nadia', 'Karim', 'Fatima', 'Mehdi', 'Yasmine', 'Wei', 'Li', 'Chen', 'Ana', 'Diego',
	];

	private const LAST_NAMES = [
		'Doe', 'Smith', 'Johnson', 'Brown', 'Davis', 'Wilson', 'Martinez', 'Garcia', 'Anderson', 'Taylor',
		'Moore', 'Martin', 'Bernard', 'Dubois', 'Petit', 'Durand', 'Leroy', 'Moreau', 'Simon', 'Laurent',
		'Lefebvre', 'Michel', 'Roux', 'Fournier', 'Girard', 'Bonnet', 'Dupont', 'Lambert', 'Fontaine', 'Rousseau',
		'El Amrani', 'Ben Ali', 'Nguyen', 'Wang', 'Kowalski', 'Rossi', 'Silva', 'Costa', 'Nielsen', 'Novak',
	];

	private const CITIES = ['Paris', 'La Rochelle', 'Nantes', 'Marseille', 'Bruxelles', 'New York', 'Madrid', 'Barcelone', 'Lyon', 'Toulouse'];

	private const ZIP_CODES = ['75001', '75005', '75011', '64505', '17000', '16100', '44000', '13001', '69001', '31000'];

	private const FONCTIONS = ['Développeur', 'Business Développeur', 'Chef de projet', 'Directeur', 'Manager', 'Responsable', 'Consultant', 'Ingénieur', 'Architecte', 'Designer', 'Graphiste', 'Décorateur'];

	private const SERVICES = ['Direction', 'Ressources humaines', 'Comptabilité', 'Marketing', 'Communication', 'Recherche', 'Informatique', 'Juridique', 'Commercial', 'Support'];

	private const ORG_ADJECTIVES = ['Nouvelle', 'Grande', 'Jeune', 'Première', 'Internationale', 'Européenne', 'Régionale', 'Nationale'];

	private const ORG_NOUNS = ['Université', 'Institut', 'Fondation', 'Association', 'Laboratoire', 'École', 'Centre', 'Agence', 'Fédération', 'Collectif'];

	private const ORG_FIELDS = ['de Recherche', 'des Sciences', 'des Arts', 'de Technologie', 'd\'Innovation', 'de Développement', 'de Formation', 'd\'Excellence', 'du Numérique', 'de l\'Environnement'];

	private const GENDERS = [1, 2];

	private const LANGUAGES = [1, 2, 3, 4, 5, 6, 7];
	private const LANGUAGES_REPEAT = ['Anglais', 'Allemand', 'Arabe', 'Chinois', 'Espagnol', 'Italien', 'Russe'];
	private const LANGUAGES_LEVEL = ['Débutant', 'Intermédiaire', 'Avancé'];
	private const SURVEY = [1, 2, 3, 4];
	private const YES_NO = [0, 1];

	private const LOREM = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus. Suspendisse lectus tortor, dignissim sit amet, adipiscing nec, ultricies sed, dolor.';

	private array $nationalities;
	private array $countries;

	public function __construct(DatabaseInterface $db)
	{
		$this->nationalities = $this->loadIds($db, 'data_nationality');
		$this->countries     = $this->loadIds($db, 'data_country');
	}

	private function loadIds(DatabaseInterface $db, string $table): array
	{
		try
		{
			$query = $db->getQuery(true)
				->select('id')
				->from($db->quoteName($table));
			$db->setQuery($query);

			return array_map('intval', $db->loadColumn() ?: []);
		}
		catch (\Exception $e)
		{
			return [];
		}
	}

	public function firstName(): string
	{
		return self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
	}

	public function lastName(): string
	{
		return self::LAST_NAMES[array_rand(self::LAST_NAMES)];
	}

	public function fonction(): string
	{
		return self::FONCTIONS[array_rand(self::FONCTIONS)];
	}

	public function service(): string
	{
		return self::SERVICES[array_rand(self::SERVICES)];
	}

	public function phone(): string
	{
		return '+33 ' . rand(6, 9) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99);
	}

	/**
	 * Random organization name, e.g. "Institut International de Recherche".
	 */
	public function organizationName(): string
	{
		return self::ORG_NOUNS[array_rand(self::ORG_NOUNS)]
			. ' ' . self::ORG_ADJECTIVES[array_rand(self::ORG_ADJECTIVES)]
			. ' ' . self::ORG_FIELDS[array_rand(self::ORG_FIELDS)];
	}

	/**
	 * Guaranteed-unique contact email using a caller-provided index. Kept on a
	 * distinct sub-domain so seeded contacts never collide with seeded applicants.
	 */
	public function uniqueContactEmail(string $firstname, string $lastname, int $index): string
	{
		$slug = strtolower($firstname . '.' . $lastname);
		$slug = preg_replace('/[^a-z0-9.]+/', '', $this->stripAccents($slug));

		return $slug . '+' . $index . '@sample-contact.emundus.fr';
	}

	/**
	 * Guaranteed-unique email using a caller-provided index.
	 */
	public function uniqueEmail(string $firstname, string $lastname, int $index): string
	{
		$slug = strtolower($firstname . '.' . $lastname);
		$slug = preg_replace('/[^a-z0-9.]+/', '', $this->stripAccents($slug));

		return $slug . '+' . $index . '@sample.emundus.fr';
	}

	private function stripAccents(string $value): string
	{
		$ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

		return $ascii === false ? $value : strtolower($ascii);
	}

	private function rand(array $pool)
	{
		return $pool[array_rand($pool)];
	}

	private function nationality(): int
	{
		return empty($this->nationalities) ? 0 : $this->rand($this->nationalities);
	}

	private function country(): int
	{
		return empty($this->countries) ? 0 : $this->rand($this->countries);
	}

	/**
	 * Full application form dataset (per fnum), keyed by table name.
	 * Array values are repeat-group rows handled by ApplicationFileRepository::insertDatas().
	 */
	public function applicationData(string $firstname, string $lastname): array
	{
		$datas = [
			'jos_emundus_1001_00' => [
				'e_804_7994'  => $this->rand(self::GENDERS),
				'e_804_7996'  => $firstname,
				'e_804_7995'  => $lastname,
				'e_804_7997'  => date('Y-m-d', strtotime('-' . rand(18, 50) . ' years')),
				'e_804_7998'  => $this->rand(self::CITIES),
				'e_804_7999'  => $this->country(),
				'e_804_8000'  => $this->nationality(),
				'e_805_8004'  => $this->country(),
				'e_805_8003'  => $this->rand(self::CITIES),
				'e_805_8002'  => $this->rand(self::ZIP_CODES),
				'telephone_1' => '+33 ' . rand(6, 9) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
				'e_804_8007'  => rand(1, 200) . ' rue de ' . ucfirst($this->rand(self::CITIES)),
			],
			'jos_emundus_1001_01' => [
				'e_807_8028' => $this->rand(self::YES_NO),
				'e_807_8039' => $this->rand(self::YES_NO),
			],
			'jos_emundus_1001_04' => [
				'e_816_8052' => $this->rand(self::YES_NO),
			],
			'jos_emundus_1001_06' => [
				'e_819_8068' => $this->rand(self::LANGUAGES),
			],
			'jos_emundus_1001_08' => [
				'e_823_8079' => '["' . $this->rand(self::SURVEY) . '"]',
			],
		];

		if ($datas['jos_emundus_1001_01']['e_807_8028'] === 1)
		{
			foreach (range(1, rand(1, 3)) as $ignored)
			{
				$datas['jos_emundus_1001_01']['jos_emundus_1001_01_811_repeat'][] = [
					'e_811_8030' => date('Y', strtotime('-' . rand(1, 5) . ' years')),
					'e_811_8031' => 'Bac +' . rand(1, 5),
					'e_811_8032' => 'Baccalauréat',
					'e_811_8034' => 'Lycée ' . ucfirst($this->rand(self::CITIES)),
					'e_811_8036' => $this->rand(self::CITIES),
					'e_811_8033' => $this->country(),
				];
			}
		}

		if ($datas['jos_emundus_1001_01']['e_807_8039'] === 1)
		{
			foreach (range(1, rand(1, 3)) as $ignored)
			{
				$datas['jos_emundus_1001_01']['jos_emundus_1001_01_814_repeat'][] = [
					'e_814_8040' => date('Y-m-d', strtotime('-' . rand(1, 5) . ' years')),
					'e_814_8041' => date('Y-m-d', strtotime('-' . rand(2, 5) . ' years')),
					'e_814_8042' => 'Formation ' . rand(1, 5),
					'e_814_8045' => 'Certification ' . rand(1, 5),
				];
			}
		}

		return $datas;
	}
}
