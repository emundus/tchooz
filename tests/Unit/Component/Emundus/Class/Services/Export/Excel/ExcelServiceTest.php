<?php

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Services\Export\Excel\ExcelService;

/**
 * @covers \Tchooz\Services\Export\Excel\ExcelService
 */
class ExcelServiceTest extends UnitTestCase
{
	private ?ExcelService $exportService = null;

	protected function setUp(): void
	{
		parent::setUp();

		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$elementId = $this->h_dataset->getFormElementForTest(102, 'campaign_id');

		// 5 synthesis + 1 element = 6 headers au total
		$options = [
			'export_version' => 'next',
			'format' => ExportFormatEnum::XLSX->value,
			'elements' => $elementId,
			'headers' => '',
			'synthesis' => 'fnum,status,lastname,firstname,email',
			'attachments' => '',
			'lang' => 'fr-FR',
		];

		$this->exportService = new ExcelService([], $coord, $options);
	}

	/**
	 * Helper pour construire un JSON d'export avec les paramètres souhaités.
	 */
	private function buildJson(
		int $totalFnums,
		int $batchSize,
		int $processed,
		string $phase,
		int $synthesisIndex,
		int $elementIndex,
		int $headerCount
	): array
	{
		$fnums = [];
		for ($i = 0; $i < $totalFnums; $i++)
		{
			$fnums[] = 'fnum_' . $i;
		}

		$headers = [];
		for ($i = 0; $i < $headerCount; $i++)
		{
			$headers['header_' . $i] = 'Header ' . $i;
		}

		return [
			'meta' => [
				'fnums' => $fnums,
				'batch_size' => $batchSize,
				'processed' => $processed,
				'phase' => $phase,
				'synthesis_index' => $synthesisIndex,
				'element_index' => $elementIndex,
			],
			'headers' => $headers,
			'files' => [],
		];
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 */
	public function testComputeProgressReturnsZeroWhenNoFnums(): void
	{
		$json = $this->buildJson(0, 100, 0, 'synthesis', 0, 0, 6);
		$progress = $this->exportService->computeProgress($json);

		$this->assertEquals(0.0, $progress, 'Le progress doit être 0 quand il n\'y a pas de fnums');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 */
	public function testComputeProgressReturnsZeroAtStart(): void
	{
		// 1000 fnums, batch 200, rien de traité, phase synthesis index 0
		$json = $this->buildJson(1000, 200, 0, 'synthesis', 0, 0, 10);
		$progress = $this->exportService->computeProgress($json);

		$this->assertEquals(0.0, $progress, 'Le progress doit être 0 au démarrage de l\'export');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 */
	public function testComputeProgressDuringFirstChunkSynthesis(): void
	{
		// 1000 fnums, batch 200 = 5 chunks. 10 headers (5 synthesis + 5 elements).
		// Chunk 0, phase synthesis, synthesis_index=3 → 3/10 du chunk 0 fait
		// progress = (0 + 3/10) / 5 * 100 = 6%
		$json = $this->buildJson(1000, 200, 0, 'synthesis', 3, 0, 10);
		$progress = $this->exportService->computeProgress($json);

		$this->assertEquals(6.0, $progress, 'Le progress en phase synthesis du premier chunk doit être correct');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 */
	public function testComputeProgressDuringFirstChunkElements(): void
	{
		// 1000 fnums, batch 200, 5 chunks, 10 headers (5 synthesis + 5 elements).
		// Chunk 0, phase elements, element_index=3 → (5+3)/10 du chunk
		// progress = (0 + 8/10) / 5 * 100 = 16%
		$json = $this->buildJson(1000, 200, 0, 'elements', 0, 3, 10);
		$progress = $this->exportService->computeProgress($json);

		$this->assertEquals(16.0, $progress, 'Le progress en phase elements du premier chunk doit être correct');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 */
	public function testComputeProgressAfterFirstChunkComplete(): void
	{
		// 1000 fnums, batch 200, processed=200 = 1 chunk terminé
		// Nouveau chunk démarre : phase synthesis, index 0
		// progress = (1 + 0) / 5 * 100 = 20%
		$json = $this->buildJson(1000, 200, 200, 'synthesis', 0, 0, 10);
		$progress = $this->exportService->computeProgress($json);

		$this->assertEquals(20.0, $progress, 'Le progress après un chunk complet doit être 20%');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 * Le cas de régression : le progress ne doit jamais régresser entre la fin d'un chunk et le début du suivant.
	 */
	public function testComputeProgressIsMonotoneBetweenChunkTransitions(): void
	{
		// 1000 fnums, batch 200, 10 headers (5 synthesis + 5 elements)
		// Simuler la fin du chunk 0, phase elements, element_index=4 (dernière colonne en cours)
		$jsonEndOfChunk = $this->buildJson(1000, 200, 0, 'elements', 0, 4, 10);
		$progressEndOfChunk = $this->exportService->computeProgress($jsonEndOfChunk);

		// Simuler le début du chunk 1, phase synthesis, index 0
		$jsonStartOfNextChunk = $this->buildJson(1000, 200, 200, 'synthesis', 0, 0, 10);
		$progressStartOfNextChunk = $this->exportService->computeProgress($jsonStartOfNextChunk);

		$this->assertGreaterThanOrEqual(
			$progressEndOfChunk,
			$progressStartOfNextChunk,
			sprintf(
				'Le progress ne doit pas régresser entre la fin du chunk (%s%%) et le début du suivant (%s%%)',
				$progressEndOfChunk,
				$progressStartOfNextChunk
			)
		);
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 * Vérifier la monotonie sur toute une séquence simulée de transitions.
	 */
	public function testComputeProgressIsStrictlyMonotoneAcrossFullExport(): void
	{
		// 500 fnums, batch 200 = 3 chunks (200, 200, 100), 10 headers (5 synthesis + 5 elements)
		$totalFnums = 500;
		$batchSize = 200;
		$headerCount = 10;
		$synthesisCount = 5; // doit correspondre aux options du service (5 synthesis dans setUp)
		$elementCount = $headerCount - $synthesisCount;

		$previousProgress = -1.0;
		$processed = 0;

		$totalChunks = (int) ceil($totalFnums / $batchSize);

		for ($chunk = 0; $chunk < $totalChunks; $chunk++)
		{
			// Phase synthesis
			for ($si = 0; $si <= $synthesisCount; $si++)
			{
				$json = $this->buildJson($totalFnums, $batchSize, $processed, 'synthesis', $si, 0, $headerCount);
				$progress = $this->exportService->computeProgress($json);

				$this->assertGreaterThanOrEqual(
					$previousProgress,
					$progress,
					"Régression détectée chunk=$chunk, synthesis_index=$si: $previousProgress -> $progress"
				);
				$previousProgress = $progress;
			}

			// Phase elements
			for ($ei = 0; $ei <= $elementCount; $ei++)
			{
				$json = $this->buildJson($totalFnums, $batchSize, $processed, 'elements', 0, $ei, $headerCount);
				$progress = $this->exportService->computeProgress($json);

				$this->assertGreaterThanOrEqual(
					$previousProgress,
					$progress,
					"Régression détectée chunk=$chunk, element_index=$ei: $previousProgress -> $progress"
				);
				$previousProgress = $progress;
			}

			// Chunk terminé
			$chunkSize = min($batchSize, $totalFnums - $processed);
			$processed += $chunkSize;
		}

		// Vérifie que le dernier état est bien à ~100% (tous les chunks terminés, aucune colonne en cours)
		$jsonFinal = $this->buildJson($totalFnums, $batchSize, $processed, 'synthesis', 0, 0, $headerCount);
		$finalProgress = $this->exportService->computeProgress($jsonFinal);

		// Quand tous les chunks sont terminés, doneChunks = totalChunks, chunkFraction = 0 (car doneChunks >= totalChunks)
		$this->assertEquals(100.0, $finalProgress, 'Le progress doit être 100% quand tous les fnums sont traités');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 */
	public function testComputeProgressWithLastChunkSmaller(): void
	{
		// 500 fnums, batch 200 = 3 chunks (200, 200, 100)
		// Après 2 chunks (400 traités), début du chunk 3 (le dernier, 100 fnums)
		// progress = (2 + 0) / 3 * 100 = 66.67%
		$json = $this->buildJson(500, 200, 400, 'synthesis', 0, 0, 10);
		$progress = $this->exportService->computeProgress($json);

		$this->assertEquals(66.67, $progress, 'Le progress avec un dernier chunk plus petit doit être correct');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 */
	public function testComputeProgressReturnsZeroWithNoHeaders(): void
	{
		$json = $this->buildJson(1000, 200, 0, 'synthesis', 0, 0, 0);
		$progress = $this->exportService->computeProgress($json);

		// Sans headers, chunkFraction = 0, progress = (0 + 0) / 5 * 100 = 0
		$this->assertEquals(0.0, $progress, 'Le progress sans headers doit être 0 au démarrage');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 */
	public function testComputeProgressWithNoHeadersButProcessedChunks(): void
	{
		// Même sans headers, les chunks terminés doivent compter
		$json = $this->buildJson(1000, 200, 400, 'synthesis', 0, 0, 0);
		$progress = $this->exportService->computeProgress($json);

		// 2 chunks terminés sur 5, pas de fraction car pas de headers
		// progress = (2 + 0) / 5 * 100 = 40
		$this->assertEquals(40.0, $progress, 'Le progress sans headers avec des chunks traités doit refléter les chunks terminés');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 */
	public function testComputeProgressNeverExceeds100(): void
	{
		// Cas limite : processed > totalFnums (ne devrait pas arriver mais on se protège)
		$json = $this->buildJson(100, 200, 200, 'elements', 0, 5, 10);
		$progress = $this->exportService->computeProgress($json);

		$this->assertLessThanOrEqual(100.0, $progress, 'Le progress ne doit jamais dépasser 100%%');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 */
	public function testComputeProgressSingleChunkComplete(): void
	{
		// 50 fnums, batch 200 = 1 seul chunk. processed=50, tout est fait.
		$json = $this->buildJson(50, 200, 50, 'synthesis', 0, 0, 10);
		$progress = $this->exportService->computeProgress($json);

		$this->assertEquals(100.0, $progress, 'Le progress doit être 100%% quand le seul chunk est terminé');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress
	 */
	public function testComputeProgressSingleChunkMidway(): void
	{
		// 50 fnums, batch 200 = 1 seul chunk. En cours de traitement, phase elements, element_index=2
		// 10 headers (5 synthesis + 5 elements), doneColumns = 5+2 = 7
		// progress = (0 + 7/10) / 1 * 100 = 70%
		$json = $this->buildJson(50, 200, 0, 'elements', 0, 2, 10);
		$progress = $this->exportService->computeProgress($json);

		$this->assertEquals(70.0, $progress, 'Le progress pour un chunk unique à mi-parcours doit être correct');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::export()
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::__construct()
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::computeProgress()
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::convertToXlsx()
	 * @return void
	 */
	public function testExport(): void
	{
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$exportEntity = new ExportEntity(0, new \DateTime(), $coord, '', ExportFormatEnum::XLSX, null, null, 0);
		$exportRepository = new ExportRepository();
		$exportRepository->flush($exportEntity);
		$exportEntity = $exportRepository->getById($exportEntity->getId());
		$elementId = $this->h_dataset->getFormElementForTest(102, 'campaign_id');

		$options = [
			'export_version' => 'next',
			'format' => ExportFormatEnum::XLSX->value,
			'elements' => $elementId,
			'headers' => '',
			'synthesis' => 'fnum,status,lastname,firstname,email',
			'attachments' => '',
			'lang' => 'fr-FR',
		];

		try {
			$service = new ExcelService([$this->dataset['fnum']], $coord, $options, $exportEntity);
			$result = $service->export('tmp/', null, 'fr-FR');
			$this->assertTrue($result->isStatus(), 'The export should complete successfully');
			$this->assertNotEmpty($result->getFilePath(), 'The export result should contain a file path');
			$this->assertFileExists($result->getFilePath(), 'The exported file should exist at the specified path');
			$this->assertEquals(100.00, $result->getProgress(), 'The export progress should be 100%');
		} catch (\Exception $e) {
			$this->fail('The excel service export should not throw an exception: ' . $e->getMessage());
		}
	}

	/**
	 * Assert that the ExcelService can successfully export data using the default export version.
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::export()
	 * @return void
	 * @throws Exception
	 */
	public function testDefaultExport(): void
	{
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$exportEntity = new ExportEntity(0, new \DateTime(), $coord, '', ExportFormatEnum::XLSX, null, null, 0);
		$exportRepository = new ExportRepository();
		$exportRepository->flush($exportEntity);
		$exportEntity = $exportRepository->getById($exportEntity->getId());
		$elementId = $this->h_dataset->getFormElementForTest(102, 'fnum');

		$oldOptions = [
			'export_version' => 'default',
			'format' => ExportFormatEnum::XLSX->value,
			'tmp_file' => 'test_old_export_xls.csv',
			'totalfile' => 1,
			'start' => 0,
			'limit' => 100,
			'nbcol' => 0,
			'methode' => 0,
			'elts' => '{"0":"' . $elementId . '"}',
			'objs' => '{}',
			'opts' => '{}',
			'excelfilename' => 'test_old_export_xls',
			'campaign' => $this->dataset['campaign'],
			'async' => false,
		];

		$service = new ExcelService([$this->dataset['fnum']], $coord, $oldOptions, $exportEntity);
		$result  = $service->export('tmp/', null);
		$this->assertTrue($result->isStatus(), 'The export should complete successfully');
		$this->assertNotEmpty($result->getFilePath(), 'The export result should contain a file path');
		$this->assertFileExists($result->getFilePath(), 'The exported file should exist at the specified path');
		$this->assertEquals(100.00, $result->getProgress(), 'The export progress should be 100%');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::export()
	 * @return void
	 */
	public function testExportCannotUploadToAnyDirectory(): void
	{
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$exportEntity = new ExportEntity(0, new \DateTime(), $coord, '', ExportFormatEnum::XLSX, null, null, 0);
		$exportRepository = new ExportRepository();
		$exportRepository->flush($exportEntity);
		$exportEntity = $exportRepository->getById($exportEntity->getId());
		$elementId = $this->h_dataset->getFormElementForTest(102, 'campaign_id');

		$options = [
			'export_version' => 'next',
			'format' => ExportFormatEnum::XLSX->value,
			'elements' => $elementId,
			'headers' => '',
			'synthesis' => 'fnum,status,lastname,firstname,email',
			'attachments' => '',
			'lang' => 'fr-FR',
		];

		try {
			$service = new ExcelService([$this->dataset['fnum']], $coord, $options, $exportEntity);
			$service->export('invalid_directory/', null, 'fr-FR');
			$this->fail('The excel service export should throw an exception when the directory is not writable');
		} catch (\Exception $e) {
			$this->assertStringContainsString('Forbidden export path', $e->getMessage(), 'The exception message should indicate a failure to save the file');
		}
	}

	/**
	 * Assert that an applicant user cannot use the ExcelService to export data.
	 * @covers \Tchooz\Services\Export\Excel\ExcelService::export()
	 * @return void
	 */
	public function testApplicantCannotUseExcelService(): void
	{
		$applicant        = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);
		$exportEntity     = new ExportEntity(0, new \DateTime(), $applicant, '', ExportFormatEnum::XLSX, null, null, 0);
		$exportRepository = new ExportRepository();
		$exportRepository->flush($exportEntity);
		$exportEntity = $exportRepository->getById($exportEntity->getId());
		$elementId    = $this->h_dataset->getFormElementForTest(102, 'campaign_id');

		$options = [
			'export_version' => 'next',
			'format'         => ExportFormatEnum::XLSX->value,
			'elements'       => $elementId,
			'headers'        => '',
			'synthesis'      => 'fnum,status,lastname,firstname,email',
			'attachments'    => '',
			'lang'           => 'fr-FR',
		];

		$this->expectException(\Exception::class);
		$service = new ExcelService([$this->dataset['fnum']], $applicant, $options, $exportEntity);
		$service->export('tmp/', null, 'fr-FR');
	}
}