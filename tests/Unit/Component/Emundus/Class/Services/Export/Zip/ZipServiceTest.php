<?php

namespace Unit\Component\Emundus\Class\Services\Export\Zip;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Services\Export\Zip\ZipService;
use ZipArchive;

/**
 * @covers \Tchooz\Services\Export\Zip\ZipService
 */
class ZipServiceTest extends UnitTestCase
{
	private array $fnums = [];

	/**
	 * @var string[] JPATH-relative archives produced by the test
	 */
	private array $zipPaths = [];

	public function setUp(): void
	{
		parent::setUp();
		ob_start();

		if (!defined('EMUNDUS_PATH_ABS'))
		{
			define('EMUNDUS_PATH_ABS', JPATH_ROOT . '/images/emundus/files/');
		}

		$campaignRepository = new CampaignRepository();
		$campaign           = $campaignRepository->getById($this->dataset['campaign']);
		$campaign->setProfileId(1001);
		$campaignRepository->flush($campaign);

		ComponentHelper::getParams('com_emundus')->set('automated_task_user', $this->dataset['coordinator']);

		$this->fnums = [
			$this->dataset['fnum'],
			$this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']),
		];
	}

	protected function tearDown(): void
	{
		foreach ($this->zipPaths as $zipPath)
		{
			if (is_file(JPATH_SITE . '/' . $zipPath))
			{
				unlink(JPATH_SITE . '/' . $zipPath);
			}
		}
		$this->h_dataset->deleteSampleFile($this->fnums[1]);

		ob_end_clean();
		parent::tearDown();
	}

	/**
	 * @covers \Tchooz\Services\Export\Zip\ZipService::export
	 * @covers \Tchooz\Services\Export\Pdf\PdfService::exportFnum
	 */
	public function testExportStoresPdfsWithoutRecompression(): void
	{
		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$service     = new ZipService($this->fnums, $coordinator, ['forms' => 1, 'attachment' => 0, 'lang' => 'fr-FR', 'filename' => '[FNUM]']);

		$applicantDir    = EMUNDUS_PATH_ABS . $this->dataset['applicant'] . '/';
		$applicantBefore = is_dir($applicantDir) ? scandir($applicantDir) : [];

		$result         = $service->export('tmp/', null, 'fr-FR');

		$applicantAfter = is_dir($applicantDir) ? scandir($applicantDir) : [];
		$this->assertSame([], array_values(array_diff($applicantAfter, $applicantBefore)), 'The ZIP export must not leave anything in the applicant folder');
		$this->zipPaths = $result->getResult()['files'] ?? [];

		$this->assertSame(100.0, $result->getProgress());
		$this->assertSame([$result->getFilePath()], $this->zipPaths, 'An export under the volume size should produce a single archive');
		$this->assertStringNotContainsString('_part', $result->getFilePath(), 'A single archive keeps its unsuffixed name');
		$this->assertFileExists(JPATH_SITE . '/' . $result->getFilePath());

		$zip = new ZipArchive();
		$this->assertTrue($zip->open(JPATH_SITE . '/' . $result->getFilePath()));

		$pdfEntries = 0;
		for ($i = 0; $i < $zip->numFiles; $i++)
		{
			$stat = $zip->statIndex($i);
			if (str_ends_with($stat['name'], '.pdf'))
			{
				$pdfEntries++;
				$this->assertSame(ZipArchive::CM_STORE, $stat['comp_method'], $stat['name'] . ' should be stored without recompression');
			}
		}
		$zip->close();

		$this->assertSame(count($this->fnums), $pdfEntries, 'The single reused PdfService should render one PDF per file');
	}

	/**
	 * @covers \Tchooz\Services\Export\Zip\ZipService::export
	 */
	public function testExportSplitsIntoVolumesAboveMaxSize(): void
	{
		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		// One byte: every file overflows the volume and gets an archive of its own
		$service = new ZipService($this->fnums, $coordinator, ['forms' => 1, 'attachment' => 0, 'lang' => 'fr-FR', 'filename' => '[FNUM]'], null, 1);

		$result         = $service->export('tmp/', null, 'fr-FR');
		$this->zipPaths = $result->getResult()['files'] ?? [];

		$this->assertCount(count($this->fnums), $this->zipPaths);
		$this->assertSame($this->zipPaths[0], $result->getFilePath(), 'The first volume stays the export filename');

		foreach ($this->zipPaths as $index => $zipPath)
		{
			$this->assertStringEndsWith(sprintf('_part%02d.zip', $index + 1), $zipPath);

			$zip = new ZipArchive();
			$this->assertTrue($zip->open(JPATH_SITE . '/' . $zipPath));
			$this->assertSame(1, $zip->numFiles, 'Each volume should hold the PDF of a single file');
			$zip->close();
		}
	}

	/**
	 * @covers \Tchooz\Services\Export\Zip\ZipService::export
	 * @dataProvider attachmentSelectionProvider
	 */
	public function testExportOnlyShipsSelectedAttachmentTypes(array $selectedTypes, int $attachmentDefault, array $expectedTypes): void
	{
		$types   = [$this->h_dataset->createSampleAttachment(), $this->h_dataset->createSampleAttachment()];
		$uploads = [];
		foreach ($types as $index => $typeId)
		{
			$uploadId  = $this->h_dataset->createSampleUpload($this->fnums[0], $this->dataset['campaign'], $this->dataset['applicant'], $typeId);
			$filename  = (string) Factory::getContainer()->get('DatabaseDriver')->setQuery('SELECT filename FROM #__emundus_uploads WHERE id = ' . (int) $uploadId)->loadResult();
			$uploads[] = ['id' => $uploadId, 'type' => $index, 'filename' => $filename];

			$path = EMUNDUS_PATH_ABS . $this->dataset['applicant'] . '/' . $filename;
			if (!is_dir(dirname($path)))
			{
				mkdir(dirname($path), 0755, true);
			}
			file_put_contents($path, '%PDF-1.4 attachment ' . $index);
		}

		try
		{
			$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
			$options     = [
				'forms'       => 0,
				'attachment'  => $attachmentDefault,
				'attachments' => implode(',', array_map(fn(int $index) => $types[$index], $selectedTypes)),
				'lang'        => 'fr-FR',
			];

			$result         = (new ZipService([$this->fnums[0]], $coordinator, $options))->export('tmp/', null, 'fr-FR');
			$this->zipPaths = $result->getResult()['files'] ?? [];

			$names = [];
			foreach ($this->zipPaths as $zipPath)
			{
				$zip = new ZipArchive();
				$zip->open(JPATH_SITE . '/' . $zipPath);
				for ($i = 0; $i < $zip->numFiles; $i++)
				{
					$names[] = basename($zip->getNameIndex($i));
				}
				$zip->close();
			}

			foreach ($uploads as $upload)
			{
				$shipped = in_array($upload['filename'], $names, true);
				$this->assertSame(in_array($upload['type'], $expectedTypes, true), $shipped, 'Attachment type #' . $upload['type'] . ' shipped state');
			}
		}
		catch (\Exception $e)
		{
			// Nothing selected and no form: the archive is legitimately empty
			$this->assertSame([], $expectedTypes, $e->getMessage());
		}
		finally
		{
			foreach ($uploads as $upload)
			{
				@unlink(EMUNDUS_PATH_ABS . $this->dataset['applicant'] . '/' . $upload['filename']);
				$this->h_dataset->deleteSampleUpload($upload['id']);
			}
			foreach ($types as $typeId)
			{
				$this->h_dataset->deleteSampleAttachment($typeId);
			}
		}
	}

	public static function attachmentSelectionProvider(): array
	{
		return [
			'export screen, one type selected'  => [[0], 0, [0]],
			'export screen, nothing selected'   => [[], 0, []],
			'legacy caller, no selection sent'  => [[], 1, [0, 1]],
			'legacy default ignored once typed' => [[1], 1, [1]],
		];
	}
}
