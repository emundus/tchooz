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
use Tchooz\Transformers\Mapping\ExtractValueAtIndexTransformer;

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

		$mapping = new MappingEntity(1, 'Test Mapping', 0, 'test', [], []);
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

		$mapping = new MappingEntity(1, 'Test Mapping', 0, 'test', [], []);
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

		$mapping = new MappingEntity(1, 'Test Mapping', 0, 'test', [], []);
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

	/**
	 * @covers \Tchooz\Services\Mapping\MappingService::getJsonFromMapping
	 * @return void
	 */
	public function testGetJsonFromMappingExtractAtIndex()
	{
		$unitTestForm = $this->h_dataset->getUnitTestFabrikForm();
		$this->assertNotEmpty($unitTestForm);
		$this->h_dataset->insertUnitTestFormData($this->dataset['applicant'], $this->dataset['fnum']);

		/**
		 * values are 1,2,3
		 * labels are "Star Wars","Harry Potter","Le seigneur des anneaux"
		 * In fnum, the order of the values is 3, 2, 1
		 */

		$elementId = $this->h_dataset->getFormElementForTest($unitTestForm, $this->h_dataset::FORM_KEYS['ELEMENT_ORDERLIST']);
		$fieldName = $unitTestForm . '.' . $elementId;
		$mapping = new MappingEntity(1, 'Test ExtractValueAtIndex', 0, 'test', [], []);
		$rows[]  = new MappingRowEntity(1, 1, 1, ConditionTargetTypeEnum::FORMDATA, $fieldName, 'json_extracted_entry', [
			new MappingTransformEntity(1, 1, 1, MappingTransformersEnum::EXTRACT_VALUE_AT_INDEX, [ExtractValueAtIndexTransformer::PARAMETER_INDEX => 1])
		]);

		$mapping->setRows($rows);
		$jsonArray = $this->service->getJsonFromMapping($mapping, $this->context);

		$this->assertIsArray($jsonArray);
		$this->assertArrayHasKey('json_extracted_entry', $jsonArray);
		$this->assertEquals('3', $jsonArray['json_extracted_entry'], 'The transformation should extract the first value from the list, which is "3".');
	}

	/**
	 * @covers \Tchooz\Services\Mapping\MappingService::getJsonFromMapping
	 * @covers \Tchooz\Entities\Mapping\MappingEntity::__construct
	 * @covers \Tchooz\Entities\Mapping\MappingEntity::setRows
	 * @covers \Tchooz\Entities\Mapping\MappingRowEntity::__construct
	 * @covers \Tchooz\Entities\Mapping\MappingTransformEntity::__construct
	 * @return void
	 */
	public function testGetJsonFromMappingExtractAtIndexThenFormatTransformations(): void
	{
		$unitTestForm = $this->h_dataset->getUnitTestFabrikForm();
		$this->assertNotEmpty($unitTestForm);
		$this->h_dataset->insertUnitTestFormData($this->dataset['applicant'], $this->dataset['fnum']);

		/**
		 * values are 1,2,3
		 * labels are "Star Wars","Harry Potter","Le seigneur des anneaux"
		 * In fnum, the order of the values is 3, 2, 1
		 */

		$elementId = $this->h_dataset->getFormElementForTest($unitTestForm, $this->h_dataset::FORM_KEYS['ELEMENT_ORDERLIST']);
		$fieldName = $unitTestForm . '.' . $elementId;
		$mapping = new MappingEntity(1, 'Test Ordering values', 0, 'test', [], []);
		$rows[]  = new MappingRowEntity(1, 1, 1, ConditionTargetTypeEnum::FORMDATA, $fieldName, 'json_ordered_entry', [
			new MappingTransformEntity(1, 1, 1, MappingTransformersEnum::EXTRACT_VALUE_AT_INDEX, [ExtractValueAtIndexTransformer::PARAMETER_INDEX => 1]),
			new MappingTransformEntity(2, 1, 2, MappingTransformersEnum::USE_FORMATTED_VALUE)
		]);

		$mapping->setRows($rows);
		$jsonArray = $this->service->getJsonFromMapping($mapping, $this->context);

		$this->assertIsArray($jsonArray);
		$this->assertArrayHasKey('json_ordered_entry', $jsonArray);
		$this->assertEquals('Le seigneur des anneaux', $jsonArray['json_ordered_entry'], 'The transformations should extract the first value (which is "3") and then format it to get the corresponding label "Le seigneur des anneaux".');
	}
}