<?php

namespace Unit\Component\Emundus\Class\Services\Export;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionPrintApplication;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Repositories\Automation\EventsRepository;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Services\Export\ExportTemplateAccess;

/**
 * @package     Unit
 * @subpackage  Services
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */
class ExportTemplateAccessTest extends UnitTestCase
{
	private ExportRepository $repository;

	private ExportTemplateAccess $access;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new ExportRepository();
		$this->access     = new ExportTemplateAccess($this->repository);
	}

	/**
	 * @covers \Tchooz\Services\Export\ExportTemplateAccess::getReadable
	 * @covers \Tchooz\Services\Export\ExportTemplateAccess::getWritable
	 * @return void
	 */
	public function testOwnTemplateIsReadableAndWritable(): void
	{
		$owner = (int) $this->dataset['coordinator'];
		$id    = $this->saveTemplate($owner);

		$this->assertNotNull($this->access->getReadable($id, $owner), 'A user should be able to load their own template');
		$this->assertNotNull($this->access->getWritable($id, $owner), 'A user should be able to overwrite their own template');

		$this->repository->deleteExportTemplate($id);
	}

	/**
	 * @covers \Tchooz\Services\Export\ExportTemplateAccess::getReadable
	 * @covers \Tchooz\Services\Export\ExportTemplateAccess::getWritable
	 * @return void
	 */
	public function testPersonalTemplateOfSomebodyElseIsNeitherReadableNorWritable(): void
	{
		$id = $this->saveTemplate((int) $this->dataset['applicant']);

		$other = (int) $this->dataset['coordinator'];
		$this->assertNull($this->access->getReadable($id, $other), 'A personal template must stay invisible to other users');
		$this->assertNull($this->access->getWritable($id, $other), 'A personal template must not be writable by other users');

		$this->repository->deleteExportTemplate($id);
	}

	/**
	 * A system template is shared with every user allowed to export, but sharing it does not hand its
	 * deletion to everyone who can see it.
	 *
	 * @covers \Tchooz\Services\Export\ExportTemplateAccess::getReadable
	 * @covers \Tchooz\Services\Export\ExportTemplateAccess::getWritable
	 * @return void
	 */
	public function testSystemTemplateIsReadableByAnyoneButWritableByItsOwnerOnly(): void
	{
		$id = $this->saveTemplate((int) $this->dataset['applicant'], true);

		$nonSysadmin = (int) $this->dataset['coordinator'];
		$this->assertFalse($this->access->canFlagAsSystem($nonSysadmin), 'A coordinator is not a sysadmin');

		$this->assertNotNull($this->access->getReadable($id, $nonSysadmin), 'A system template should be readable by any user');
		$this->assertNull($this->access->getWritable($id, $nonSysadmin), 'A system template should not be writable by a non-sysadmin');

		$this->repository->deleteExportTemplate($id);
	}

	/**
	 * @covers \Tchooz\Services\Export\ExportTemplateAccess::getReadable
	 * @covers \Tchooz\Services\Export\ExportTemplateAccess::getWritable
	 * @return void
	 */
	public function testMissingTemplateIsNeitherReadableNorWritable(): void
	{
		$this->assertNull($this->access->getReadable(0, (int) $this->dataset['coordinator']));
		$this->assertNull($this->access->getWritable(0, (int) $this->dataset['coordinator']));
	}

	/**
	 * Deleting or unflagging a referenced template would leave the automation printing the default
	 * layout without saying anything, so both are refused while it is still in use.
	 *
	 * @covers \Tchooz\Services\Export\ExportTemplateAccess::getAutomationsUsing
	 * @covers \Tchooz\Repositories\Automation\ActionRepository::getAutomationNamesByActionParameter
	 * @return void
	 */
	public function testTemplateReferencedByAnAutomationIsReportedAsUsed(): void
	{
		$id    = $this->saveTemplate((int) $this->dataset['coordinator'], true);
		$other = $this->saveTemplate((int) $this->dataset['coordinator'], true);

		$automationId = $this->createPrintAutomation('Print automation under test', $id);

		$this->assertSame(
			['Print automation under test'],
			$this->access->getAutomationsUsing($id),
			'The automation printing with the template should be reported'
		);
		$this->assertEmpty($this->access->getAutomationsUsing($other), 'A template no automation points at is free to delete');

		$this->deleteAutomation($automationId);
		$this->repository->deleteExportTemplate($id);
		$this->repository->deleteExportTemplate($other);
	}

	/**
	 * The print action stores its template id as a string when saved from the automation builder and as
	 * an int when saved from a test or a script: both must match.
	 *
	 * @covers \Tchooz\Services\Export\ExportTemplateAccess::getAutomationsUsing
	 * @return void
	 */
	public function testTemplateIsFoundWhateverTheJsonTypeOfTheStoredId(): void
	{
		$id = $this->saveTemplate((int) $this->dataset['coordinator'], true);

		$automationId = $this->createPrintAutomation('Print automation string id', (string) $id);

		$this->assertSame(['Print automation string id'], $this->access->getAutomationsUsing($id));

		$this->deleteAutomation($automationId);
		$this->repository->deleteExportTemplate($id);
	}

	private function createPrintAutomation(string $name, string|int $templateId): int
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$event = (new EventsRepository())->getEventByName('onAfterStatusChange');

		$automation = (object) ['name' => $name, 'event_id' => $event->getId()];
		$db->insertObject('#__emundus_automation', $automation);
		$automationId = (int) $db->insertid();

		$action = (object) [
			'name'          => ActionPrintApplication::getType(),
			'automation_id' => $automationId,
			'params'        => json_encode([ActionPrintApplication::TEMPLATE_PARAMETER => $templateId]),
		];
		$db->insertObject('#__emundus_action', $action);

		return $automationId;
	}

	private function deleteAutomation(int $automationId): void
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__emundus_automation'))
			->where($db->quoteName('id') . ' = ' . $automationId);
		$db->setQuery($query);
		$db->execute();
	}

	private function saveTemplate(int $userId, bool $system = false): int
	{
		return $this->repository->saveExportTemplate(
			'Access test template',
			ExportFormatEnum::PDF,
			[],
			[],
			[],
			[],
			$userId,
			0,
			[],
			$system
		);
	}
}
