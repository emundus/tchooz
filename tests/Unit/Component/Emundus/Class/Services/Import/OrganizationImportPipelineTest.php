<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import;

use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Entities\Country;
use Tchooz\Enums\Import\RowStatusEnum;
use Tchooz\Repositories\Contacts\OrganizationRepository;
use Tchooz\Repositories\CountryRepository;
use Tchooz\Services\Import\Entity\OrganizationImporter;
use Tchooz\Services\Import\ImportPipeline;
use Tchooz\Services\Import\Source\ArraySource;
use Tchooz\Services\Import\Source\CsvSource;
use Tchooz\Services\Import\Source\ImportSourceInterface;
use Tchooz\Services\Import\Source\JsonSource;

/**
 * End-to-end pipeline tests against a real OrganizationImporter wired to
 * mocked repositories. Each scenario is exercised across the three concrete
 * sources (Array / CSV / JSON) to make sure source format does not leak into
 * downstream behaviour.
 *
 * @covers \Tchooz\Services\Import\ImportPipeline
 * @covers \Tchooz\Services\Import\Entity\OrganizationImporter
 * @covers \Tchooz\Services\Import\Source\ArraySource
 * @covers \Tchooz\Services\Import\Source\CsvSource
 * @covers \Tchooz\Services\Import\Source\JsonSource
 */
class OrganizationImportPipelineTest extends TestCase
{
	private const HEADERS = ['Nom', 'Description', 'Site web', 'Code postal', 'Ville', 'Pays'];

	private const ROWS = [
		['Acme',     'World leader in anvils', 'https://acme.example',  '75001', 'Paris',     'FR'],
		['Globex',   'Cogswell rival',         'https://globex.example','69001', 'Lyon',      'FR'],
		['Initech',  'Office space',           'https://initech.example','EC1A 1BB','London',  'GB'],
	];

	/** @var string[]  paths of temp files to clean up in tearDown */
	private array $tempFiles = [];

	private OrganizationRepository $orgRepo;
	private CountryRepository      $countryRepo;
	private DatabaseInterface      $db;
	private ImportPipeline         $pipeline;
	private OrganizationImporter   $importer;

	protected function setUp(): void
	{
		$this->orgRepo     = $this->createMock(OrganizationRepository::class);
		$this->countryRepo = $this->createMock(CountryRepository::class);
		$this->db          = $this->createMock(DatabaseInterface::class);

		$this->orgRepo->method('getByIdentifierCode')->willReturn(null);
		$this->orgRepo->method('getByName')->willReturn(null);
		$this->countryRepo->method('getByIso2')->willReturnCallback(
			static fn (string $iso) => new Country(id: 1, label: $iso, iso2: $iso, iso3: $iso . 'X', country_nb: 0)
		);

		$this->importer = new OrganizationImporter($this->orgRepo, $this->countryRepo);
		$this->pipeline = new ImportPipeline($this->db);
	}

	protected function tearDown(): void
	{
		foreach ($this->tempFiles as $path)
		{
			if (file_exists($path))
			{
				unlink($path);
			}
		}
		$this->tempFiles = [];
	}

	// --------------------------------------------------------------------
	// Happy path: same expectations for the three concrete sources
	// --------------------------------------------------------------------

	public function testHappyPathArraySource(): void
	{
		$this->expectFlushCalls(3);
		$this->assertHappyPath($this->arraySource(self::HEADERS, self::ROWS));
	}

	public function testHappyPathCsvSource(): void
	{
		$this->expectFlushCalls(3);
		$this->assertHappyPath($this->csvSource(self::HEADERS, self::ROWS, ','));
	}

	public function testHappyPathJsonSource(): void
	{
		$this->expectFlushCalls(3);
		$this->assertHappyPath($this->jsonSource(self::HEADERS, self::ROWS));
	}

	private function assertHappyPath(ImportSourceInterface $source): void
	{
		$report = $this->pipeline->run($source, $this->importer);

		$this->assertSame(3, $report->count(RowStatusEnum::CREATED), $report->toArray()['summary']['failed'] . ' failed rows');
		$this->assertSame(0, $report->count(RowStatusEnum::SKIPPED));
		$this->assertSame(0, $report->count(RowStatusEnum::FAILED));
	}

	// --------------------------------------------------------------------
	// Source-specific quirks
	// --------------------------------------------------------------------

	public function testCsvSourceWithSemicolonDelimiterIsAutoDetected(): void
	{
		$this->expectFlushCalls(2);

		$path = $this->writeCsv(['Nom;Pays', 'Acme;FR', 'Globex;FR'], 'semi.csv');
		$report = $this->pipeline->run(new CsvSource($path), $this->importer);

		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
	}

	public function testJsonSourceFillsMissingKeysWithNull(): void
	{
		$this->expectFlushCalls(2);

		$source = JsonSource::fromString(json_encode([
			['Nom' => 'Acme', 'Pays' => 'FR'],
			['Nom' => 'Globex'],   // missing Pays — must not crash, must still create
		]));

		$report = $this->pipeline->run($source, $this->importer);

		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
	}

	public function testHeaderAliasesAreResolvedAcrossCaseAndAccents(): void
	{
		$this->expectFlushCalls(1);

		$source = new ArraySource([
			['NOM' => 'Acme', 'région' => 'Île-de-France', 'PAYS' => 'FR'],
		]);

		$report = $this->pipeline->run($source, $this->importer);

		$this->assertSame(1, $report->count(RowStatusEnum::CREATED));
	}

	// --------------------------------------------------------------------
	// Pipeline guard rails
	// --------------------------------------------------------------------

	public function testRowMissingRequiredNameIsReportedAsFailed(): void
	{
		// flush must never fire for a row that fails the required-fields check
		$this->orgRepo->expects($this->never())->method('flush');
		// neither must any transaction be started
		$this->db->expects($this->never())->method('transactionStart');

		$source = new ArraySource([
			['Nom' => '', 'Description' => 'no name', 'Pays' => 'FR'],
		]);

		$report = $this->pipeline->run($source, $this->importer);

		$this->assertSame(0, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(1, $report->count(RowStatusEnum::FAILED));
	}

	public function testRowWithInvalidCountryCodeIsReportedAsFailed(): void
	{
		$this->orgRepo->expects($this->never())->method('flush');
		$this->db->expects($this->never())->method('transactionStart');

		$source = new ArraySource([
			['Nom' => 'Acme', 'Pays' => 'FRA'],
		]);

		$report = $this->pipeline->run($source, $this->importer);

		$this->assertSame(1, $report->count(RowStatusEnum::FAILED));
	}

	/**
	 * Regression guard for the bug we hit in production: a Contact-shaped file
	 * uploaded against the Organization importer. The Nom/Code postal/Ville/
	 * Adresse/Pays columns all matched (false-positive overlap on the address
	 * vocabulary), but the foreign Prénom/Email/Téléphone/Date de naissance/
	 * Sexe columns should now tip the unknown-header ratio over the threshold
	 * and abort with a global error before any row is processed.
	 */
	public function testContactShapedFileIsRejectedWhenImportingOrganizations(): void
	{
		$this->orgRepo->expects($this->never())->method('flush');
		$this->db->expects($this->never())->method('transactionStart');

		$source = new ArraySource([
			[
				'Nom'               => 'Dupont',
				'Code postal'       => '75001',
				'Ville'             => 'Paris',
				'Adresse'           => '1 rue de la Paix',
				'Pays'              => 'FR',
				// Foreign columns from a contact file
				'Prénom'            => 'Jean',
				'Email'             => 'jean.dupont@example.com',
				'Téléphone'         => '+33612345678',
				'Date de naissance' => '1990-01-15',
				'Sexe'              => 'man',
			],
		]);

		$report = $this->pipeline->run($source, $this->importer);

		$this->assertTrue($report->hasGlobalErrors(), 'Expected a global error rejecting the wrong-entity file.');
		$this->assertSame(0, $report->count(RowStatusEnum::CREATED));
		$this->assertContains('Prénom', $report->getUnknownHeaders());
		$this->assertContains('Email',  $report->getUnknownHeaders());
	}

	public function testExistingOrganizationByIdentifierCodeIsSkipped(): void
	{
		// re-bind getByIdentifierCode to return an existing entity for SIRET-1
		$repo = $this->createMock(OrganizationRepository::class);
		$repo->method('getByIdentifierCode')->willReturnCallback(
			static fn (string $code) => $code === 'SIRET-1'
				? new OrganizationEntity(id: 42, name: 'Existing')
				: null
		);
		$repo->method('getByName')->willReturn(null);
		$repo->expects($this->once())->method('flush');   // only the second row should flush

		$importer = new OrganizationImporter($repo, $this->countryRepo);

		$source = new ArraySource([
			['Nom' => 'Acme',   'Code identifiant' => 'SIRET-1'],
			['Nom' => 'Globex', 'Code identifiant' => 'SIRET-2'],
		]);

		$report = $this->pipeline->run($source, $importer);

		$this->assertSame(1, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(1, $report->count(RowStatusEnum::SKIPPED));
	}

	public function testRepositoryExceptionRollsBackAndContinuesOnNextRow(): void
	{
		$callIndex = 0;
		$this->orgRepo->method('flush')->willReturnCallback(function () use (&$callIndex) {
			$callIndex++;
			if ($callIndex === 1)
			{
				throw new \RuntimeException('insert failed');
			}
			return true;
		});

		// transaction lifecycle: 2 rows × (start + commit|rollback) = 2 starts, 1 rollback, 1 commit
		$this->db->expects($this->exactly(2))->method('transactionStart');
		$this->db->expects($this->exactly(1))->method('transactionRollback');
		$this->db->expects($this->exactly(1))->method('transactionCommit');

		$source = new ArraySource([
			['Nom' => 'Acme'],
			['Nom' => 'Globex'],
		]);

		$report = $this->pipeline->run($source, $this->importer);

		$this->assertSame(1, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(1, $report->count(RowStatusEnum::FAILED));

		$failed = $report->getRowsByStatus(RowStatusEnum::FAILED);
		$this->assertCount(1, $failed);
		$this->assertSame(['insert failed'], $failed[0]->reasons);
	}

	public function testEmptyRowsAreIgnored(): void
	{
		$this->expectFlushCalls(2);

		$source = new ArraySource([
			['Nom' => 'Acme'],
			['Nom' => '',   'Description' => '', 'Pays' => ''],   // fully blank row
			['Nom' => 'Globex'],
		]);

		$report = $this->pipeline->run($source, $this->importer);

		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(0, $report->count(RowStatusEnum::SKIPPED));
		$this->assertSame(0, $report->count(RowStatusEnum::FAILED));
	}

	public function testDryRunCallsPersistButRollsBack(): void
	{
		$this->orgRepo->expects($this->exactly(2))->method('flush')->willReturn(true);
		$this->db->expects($this->exactly(2))->method('transactionStart');
		$this->db->expects($this->never())->method('transactionCommit');
		$this->db->expects($this->exactly(2))->method('transactionRollback');

		$source = new ArraySource([
			['Nom' => 'Acme'],
			['Nom' => 'Globex'],
		]);

		$report = $this->pipeline->run(
			$source,
			$this->importer,
			new \Tchooz\Services\Import\ImportOptions(dryRun: true)
		);

		// dry-run rows still report CREATED — the assertion is about persistence layer behaviour
		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
	}

	// --------------------------------------------------------------------
	// Helpers
	// --------------------------------------------------------------------

	private function expectFlushCalls(int $count): void
	{
		$this->orgRepo->expects($this->exactly($count))->method('flush')->willReturn(true);
	}

	/**
	 * @param string[]            $headers
	 * @param array<int, string[]> $rows
	 */
	private function arraySource(array $headers, array $rows): ArraySource
	{
		return new ArraySource($rows, $headers);
	}

	/**
	 * @param string[]            $headers
	 * @param array<int, string[]> $rows
	 */
	private function csvSource(array $headers, array $rows, string $delimiter): CsvSource
	{
		$lines   = [implode($delimiter, $headers)];
		foreach ($rows as $row)
		{
			$lines[] = implode($delimiter, $row);
		}
		$path = $this->writeCsv($lines, 'pipeline.csv');

		return new CsvSource($path);
	}

	/**
	 * @param string[]            $headers
	 * @param array<int, string[]> $rows
	 */
	private function jsonSource(array $headers, array $rows): JsonSource
	{
		$objects = array_map(
			static fn (array $row) => array_combine($headers, $row),
			$rows
		);

		return JsonSource::fromString(json_encode($objects));
	}

	/**
	 * @param string[] $lines
	 */
	private function writeCsv(array $lines, string $name): string
	{
		$path = sys_get_temp_dir() . '/' . uniqid('import_test_', true) . '_' . $name;
		file_put_contents($path, implode("\n", $lines));
		$this->tempFiles[] = $path;

		return $path;
	}
}
