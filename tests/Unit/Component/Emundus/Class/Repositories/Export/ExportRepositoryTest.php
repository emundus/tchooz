<?php

namespace Unit\Component\Emundus\Class\Repositories\Export;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Repositories\Export\ExportRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Export
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Export\ExportRepository
 */
class ExportRepositoryTest extends UnitTestCase
{
	private ExportRepository $repository;

	private User $coord;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new ExportRepository();
		$this->coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
	}

	/**
	 * @covers \Tchooz\Repositories\Export\ExportRepository::flush
	 * @return void
	 */
	public function testFlush(): void
	{
		$export = new ExportEntity(0, new \DateTime(), $this->coord, '', ExportFormatEnum::XLSX, null, null, 0);
		$flushed = $this->repository->flush($export);
		$this->assertTrue($flushed, 'The flush method should return true on success');
		$this->assertGreaterThan(0, $export->getId(), 'The export entity should have been saved and assigned an ID');
	}

	/**
	 * @covers \Tchooz\Repositories\Export\ExportRepository::getById()
	 * @return void
	 */
	public function testGetById(): void
	{
		$export = new ExportEntity(0, new \DateTime(), $this->coord, '', ExportFormatEnum::XLSX, null, null, 0);
		$this->repository->flush($export);

		$fetchedExport = $this->repository->getById($export->getId());
		$this->assertNotNull($fetchedExport, 'The getById method should return an export entity');
		$this->assertEquals($export->getId(), $fetchedExport->getId(), 'The fetched export entity should have the same ID as the original');
	}

	/**
	 * @covers \Tchooz\Repositories\Export\ExportRepository::isCancelled()
	 * @return void
	 */
	public function testVerifyIsCancelled(): void
	{
		$export = new ExportEntity(0, new \DateTime(), $this->coord, '', ExportFormatEnum::XLSX, null, null, 0);
		$export->setCancelled(true);
		$this->repository->flush($export);

		$isCancelled = $this->repository->isCancelled($export->getId());
		$this->assertTrue($isCancelled, 'The isCancelled method should return true for a cancelled export');

		$export->setCancelled(false);
		$this->repository->flush($export);

		$isCancelled = $this->repository->isCancelled($export->getId());
		$this->assertFalse($isCancelled, 'The isCancelled method should return false for a non-cancelled export');
	}

	/**
	 * A system template is shared with everyone allowed to export, and it is the only kind of template
	 * an automation may print with.
	 *
	 * @covers \Tchooz\Repositories\Export\ExportRepository::getSystemExportTemplates
	 * @covers \Tchooz\Repositories\Export\ExportRepository::getAllExportTemplates
	 * @return void
	 */
	public function testExportTemplatesScoping(): void
	{
		$mine   = $this->saveTemplate('Mine', (int) $this->dataset['coordinator']);
		$others = $this->saveTemplate('Someone else', (int) $this->dataset['applicant']);
		$system = $this->saveTemplate('System', (int) $this->dataset['applicant'], true);

		$systemIds = $this->templateIds($this->repository->getSystemExportTemplates());
		$this->assertContains($system, $systemIds, 'A system template should be listed as such');
		$this->assertNotContains($mine, $systemIds, 'A personal template must never be listed as a system one');
		$this->assertNotContains($others, $systemIds, 'A personal template must never be listed as a system one');

		$visibleIds = $this->templateIds($this->repository->getAllExportTemplates((int) $this->dataset['coordinator']));
		$this->assertContains($mine, $visibleIds, 'A user should see their own templates');
		$this->assertContains($system, $visibleIds, 'A user should see the system templates, whoever saved them');
		$this->assertNotContains($others, $visibleIds, 'A user should not see the personal templates of somebody else');

		$this->repository->deleteExportTemplate($mine);
		$this->repository->deleteExportTemplate($others);
		$this->repository->deleteExportTemplate($system);
	}

	/**
	 * A sysadmin can take the system flag back off a template.
	 *
	 * @covers \Tchooz\Repositories\Export\ExportRepository::saveExportTemplate
	 * @return void
	 */
	public function testExportTemplateSystemFlagIsReversible(): void
	{
		$id = $this->saveTemplate('Promoted', (int) $this->dataset['coordinator'], true);
		$this->assertContains($id, $this->templateIds($this->repository->getSystemExportTemplates()));

		$this->saveTemplate('Promoted', (int) $this->dataset['coordinator'], false, $id);
		$this->assertNotContains($id, $this->templateIds($this->repository->getSystemExportTemplates()), 'Unflagging a template should remove it from the system ones');

		$this->repository->deleteExportTemplate($id);
	}

	private function saveTemplate(string $name, int $userId, bool $system = false, int $id = 0): int
	{
		return $this->repository->saveExportTemplate(
			$name,
			ExportFormatEnum::PDF,
			[],
			[],
			[],
			[],
			$userId,
			$id,
			[],
			$system
		);
	}

	private function templateIds(array $templates): array
	{
		return array_map(static fn ($template) => (int) $template->id, $templates);
	}
}
