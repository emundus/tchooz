<?php

namespace Unit\Component\Emundus\Class\Services\Mapping;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Entities\Mapping\MappingTransformEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Mapping\MappingTransformersEnum;
use Tchooz\Services\Mapping\MappingService;

class MappingServiceTest extends UnitTestCase
{
	private MappingService $service;

	private ActionTargetEntity $context;

	public function setUp(): void
	{
		parent::setUp();
		$this->service = new MappingService();
		$this->context = new ActionTargetEntity(
			Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			(int) $this->dataset['applicant']
		);
	}

	/**
	 * @covers \Tchooz\Services\Mapping\MappingService::getJsonFromMapping
	 * @return void
	 */
	public function testGetJsonFromMapping(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest(102, 'fnum');
		$fieldName = '102.' . $elementId;

		$mapping = new MappingEntity(1, 'Test Mapping', 0, 'test', []);
		$rows[]  = new MappingRowEntity(1, 1, 1, ConditionTargetTypeEnum::FORMDATA, $fieldName, 'json_fnum_entry', []);
		$mapping->setRows($rows);

		$jsonArray = $this->service->getJsonFromMapping($mapping, $this->context);
		$this->assertIsArray($jsonArray);
		$this->assertArrayHasKey('json_fnum_entry', $jsonArray);
		$this->assertEquals($this->dataset['fnum'], $jsonArray['json_fnum_entry'], 'The fnum value should match the dataset fnum.');
	}

	/**
	 * @covers \Tchooz\Services\Mapping\MappingService::getJsonFromMapping
	 * @return void
	 */
	public function testGetJsonFromMappingWithTransformation(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest(102, 'status');
		$fieldName = '102.' . $elementId;

		$mapping = new MappingEntity(1, 'Test Mapping', 0, 'test', []);
		$rows[]  = new MappingRowEntity(1, 1, 1, ConditionTargetTypeEnum::FORMDATA, $fieldName, 'json_status_entry', [
			new MappingTransformEntity(1, 1, 1, MappingTransformersEnum::MAP_VALUES, [
				'mapping' => [['map_from' => '0', 'map_to' => 'Brouillon'], ['map_from' => '1', 'map_to' => 'Publié']]
			])
		]);
		$mapping->setRows($rows);

		$jsonArray = $this->service->getJsonFromMapping($mapping, $this->context);
		$this->assertIsArray($jsonArray);
		$this->assertArrayHasKey('json_status_entry', $jsonArray);
		$this->assertEquals('Brouillon', $jsonArray['json_status_entry'], 'Mapped value should be "Brouillon" for status "0".');
	}


	/**
	 * @covers \Tchooz\Services\Mapping\MappingService::getJsonFromMapping
	 * @return void
	 */
	public function testGetJsonFromMappingWithOrderedTransformations(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest(102, 'status');
		$fieldName = '102.' . $elementId;

		$mapping = new MappingEntity(1, 'Test Mapping', 0, 'test', []);
		$rows[]  = new MappingRowEntity(1, 1, 1, ConditionTargetTypeEnum::FORMDATA, $fieldName, 'json_status_entry', [
			new MappingTransformEntity(1, 1, 1, MappingTransformersEnum::MAP_VALUES, [
				'mapping' => [['map_from' => '0', 'map_to' => 'brouillon'], ['map_from' => '1', 'map_to' => 'publié']]
			]),
			new MappingTransformEntity(2, 1, 2, MappingTransformersEnum::CAPITALIZE)
		]);
		$mapping->setRows($rows);

		$jsonArray = $this->service->getJsonFromMapping($mapping, $this->context);
		$this->assertIsArray($jsonArray);
		$this->assertArrayHasKey('json_status_entry', $jsonArray);
		$this->assertEquals('Brouillon', $jsonArray['json_status_entry'], 'Transformations should be applied in order, resulting in capitalized mapped value.');
	}
}