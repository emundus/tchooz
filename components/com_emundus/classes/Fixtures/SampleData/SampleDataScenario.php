<?php

namespace Tchooz\Fixtures\SampleData;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Tchooz\Fixtures\ScenarioInterface;
use Tchooz\Fixtures\ScenarioOption;

\defined('_JEXEC') or die;

/**
 * Exposes the full sample-data generation (programs, users, dossiers, uploads, tags) as a scenario
 * the CLI can run through the same registry as the single-entity fixtures. The actual work stays in
 * {@see SampleDataSeeder}; this only maps CLI options onto its run() signature.
 */
final class SampleDataScenario implements ScenarioInterface
{
	private const DEFAULT_USERS          = 100;
	private const DEFAULT_FILES_PER_USER = 1;
	private const DEFAULT_BATCH          = 500;
	private const DEFAULT_CAMPAIGNS      = 0;
	private const DEFAULT_ORGANIZATIONS  = 0;
	private const DEFAULT_CONTACTS       = 0;
	private const DEFAULT_PROGRAMS       = 0;

	private DatabaseInterface $db;

	public function __construct(?DatabaseInterface $db = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
	}

	public static function getScenarioName(): string
	{
		return 'sampledata';
	}

	public static function getOptions(): array
	{
		return [
			new ScenarioOption('users', 'Number of applicant users to create', self::DEFAULT_USERS),
			new ScenarioOption('files-per-user', 'Max dossiers per applicant (1..N random)', self::DEFAULT_FILES_PER_USER),
			new ScenarioOption('programs', 'Total procedural programs to generate (0 = showcase only)', self::DEFAULT_PROGRAMS),
			new ScenarioOption('campaigns', 'Total procedural campaigns to generate across programs (0 = showcase only)', self::DEFAULT_CAMPAIGNS),
			new ScenarioOption('organizations', 'Total sample organizations to generate (0 = none)', self::DEFAULT_ORGANIZATIONS),
			new ScenarioOption('contacts', 'Total sample contacts to generate, associated to organizations (0 = none)', self::DEFAULT_CONTACTS),
			new ScenarioOption('batch', 'Users per transactional batch', self::DEFAULT_BATCH),
			new ScenarioOption('fast', 'Skip Joomla events for maximum speed', false, isFlag: true),
			new ScenarioOption('with-uploads', 'Attach a sample PDF to created dossiers', false, isFlag: true),
		];
	}

	public function generate(array $options, ?callable $onProgress = null): array
	{
		$users = max(1, (int) ($options['users'] ?? self::DEFAULT_USERS));

		$seeder = new SampleDataSeeder($this->db);

		return $seeder->run(
			$users,
			max(1, (int) ($options['files-per-user'] ?? self::DEFAULT_FILES_PER_USER)),
			(bool) ($options['fast'] ?? false),
			(bool) ($options['with-uploads'] ?? false),
			max(1, (int) ($options['batch'] ?? self::DEFAULT_BATCH)),
			$onProgress,
			max(0, (int) ($options['campaigns'] ?? self::DEFAULT_CAMPAIGNS)),
			max(0, (int) ($options['organizations'] ?? self::DEFAULT_ORGANIZATIONS)),
			max(0, (int) ($options['contacts'] ?? self::DEFAULT_CONTACTS)),
			max(0, (int) ($options['programs'] ?? self::DEFAULT_PROGRAMS))
		);
	}
}
