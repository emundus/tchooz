<?php

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Services\Export\Excel\ExcelService;

class ExcelServiceTest extends UnitTestCase
{
	/**
	 * @covers ExcelService::export()
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
	 * @covers ExcelService::export()
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
	 * @covers ExcelService::export()
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
	 * @covers ExcelService::export()
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