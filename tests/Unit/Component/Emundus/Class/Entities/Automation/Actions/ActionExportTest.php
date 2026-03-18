<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionExport;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Repositories\Export\ExportRepository;

class ActionExportTest extends UnitTestCase
{
	public function setUp(): void
	{
		parent::setUp();
	}

	/**
	 * Assert XLSX export is created successfully with valid parameters.
	 * @covers \Tchooz\Entities\Automation\Actions\ActionExport::execute()
	 */
	public function testExecute(): void
	{
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context = new ActionTargetEntity($coord, $this->dataset['fnum'], $this->dataset['applicant']);
		$elementId = $this->h_dataset->getFormElementForTest(102, 'campaign_id');

		$exportEntity = new ExportEntity(
			0,
			new \DateTime(),
			$coord,
			'',
			ExportFormatEnum::XLSX,
			null,
			null,
			0,
		);

		$parameters = [
			'export_version' => 'next',
			'format' => ExportFormatEnum::XLSX->value,
			'elements' => $elementId,
			'headers' => '',
			'synthesis' => 'fnum,status,lastname,firstname,email',
			'attachments' => '',
			'lang' => 'fr-FR',
		];

		$exportAction = new ActionExport($parameters);
		$result = $exportAction->with($exportEntity)->execute($context);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result, 'The export action should complete successfully');
		$this->assertGreaterThan(0, $exportEntity->getId(), 'The export entity should have been created');

		$exportRepository = new ExportRepository();
		$exportEntity = $exportRepository->getById($exportEntity->getId());
		$this->assertNotEmpty($exportEntity->getFilename(), 'The export entity filename should not be empty');
		// assert file exists in the expected location
		$expectedFilePath = JPATH_ROOT . '/' . $exportEntity->getFilename();
		$this->assertFileExists($expectedFilePath, 'The exported file should exist at the expected location');
	}
}