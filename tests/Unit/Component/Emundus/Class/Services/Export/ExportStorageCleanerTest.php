<?php

namespace Unit\Component\Emundus\Class\Services\Export;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Filesystem\Folder;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionExport;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Services\Export\ExportStorageCleaner;

/**
 * @covers \Tchooz\Services\Export\ExportStorageCleaner
 */
class ExportStorageCleanerTest extends UnitTestCase
{
	private ExportRepository $repository;

	private ExportEntity $export;

	/** Fake user folders created under the real exports root, removed in tearDown */
	private array $userDirs = [];

	/** Folder outside the exports root that a symbolic link points to: it must survive */
	private string $victimDir;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new ExportRepository();
		$coordinator      = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$this->export     = new ExportEntity(0, new \DateTime(), $coordinator, '', ExportFormatEnum::ZIP, null, null, 0);
		$this->repository->flush($this->export);

		$this->victimDir = JPATH_SITE . '/tmp/' . uniqid('cleaner_victim_');
		mkdir($this->victimDir, 0755, true);
		file_put_contents($this->victimDir . '/precious.txt', 'keep me');
	}

	protected function tearDown(): void
	{
		foreach ($this->userDirs as $userDir)
		{
			if (is_link($userDir))
			{
				unlink($userDir);
			}
			elseif (is_dir($userDir))
			{
				Folder::delete($userDir);
			}
		}
		Folder::delete($this->victimDir);
		$this->repository->delete($this->export->getId());

		parent::tearDown();
	}

	private function makeUserDir(): string
	{
		$userDir          = JPATH_SITE . '/' . ActionExport::EXPORT_BASE_PATH . random_int(900000000, 999999999);
		$this->userDirs[] = $userDir;
		mkdir($userDir, 0755, true);

		return $userDir . '/';
	}

	/**
	 * @covers \Tchooz\Services\Export\ExportStorageCleaner::deleteOrphans
	 */
	public function testDeleteOrphansKeepsLiveExports(): void
	{
		$userDir  = $this->makeUserDir();
		$liveId   = $this->export->getId();
		$orphanId = $liveId + 100000;

		mkdir($userDir . 'staging_' . $liveId . '/folder', 0755, true);
		file_put_contents($userDir . 'export_' . $liveId . '.json', '{}');
		mkdir($userDir . 'staging_' . $orphanId . '/folder', 0755, true);
		file_put_contents($userDir . 'staging_' . $orphanId . '/folder/file.pdf', 'pdf');
		file_put_contents($userDir . 'export_' . $orphanId . '.json', '{}');
		mkdir($userDir . 'staging_legacy_123456');
		file_put_contents($userDir . '2026-09-24_1234_x2.zip', 'zip');

		(new ExportStorageCleaner($this->repository))->deleteOrphans();

		$this->assertDirectoryDoesNotExist($userDir . 'staging_' . $orphanId);
		$this->assertFileDoesNotExist($userDir . 'export_' . $orphanId . '.json');
		$this->assertDirectoryExists($userDir . 'staging_' . $liveId, 'A running export keeps its staging');
		$this->assertFileExists($userDir . 'export_' . $liveId . '.json', 'A running export keeps its state');
		$this->assertDirectoryExists($userDir . 'staging_legacy_123456', 'Only id-named leftovers are matched');
		$this->assertFileExists($userDir . '2026-09-24_1234_x2.zip', 'Produced archives are left to the export deletion');
	}

	/**
	 * @covers \Tchooz\Services\Export\ExportStorageCleaner::deleteOrphans
	 */
	public function testSymbolicLinksAreNeverFollowed(): void
	{
		$orphanId = $this->export->getId() + 200000;

		// A leftover name that is a link to a folder outside the exports root
		$userDir = $this->makeUserDir();
		symlink($this->victimDir, $userDir . 'staging_' . $orphanId);
		symlink($this->victimDir . '/precious.txt', $userDir . 'export_' . $orphanId . '.json');

		// A user folder that is itself a link, whose target holds a leftover-looking entry
		mkdir($this->victimDir . '/staging_' . $orphanId);
		$linkedUserDir    = JPATH_SITE . '/' . ActionExport::EXPORT_BASE_PATH . random_int(900000000, 999999999);
		$this->userDirs[] = $linkedUserDir;
		symlink($this->victimDir, $linkedUserDir);

		(new ExportStorageCleaner($this->repository))->deleteOrphans();

		$this->assertFileExists($this->victimDir . '/precious.txt', 'The target of a link must never be emptied');
		$this->assertDirectoryExists($this->victimDir . '/staging_' . $orphanId, 'A linked user folder must not be scanned');
		$this->assertTrue(is_link($userDir . 'staging_' . $orphanId), 'The link itself is left alone');
	}
}
