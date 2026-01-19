<?php

namespace Unit\Component\Emundus\Class\Transformers\Mapping;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Transformers\Mapping\MapDatabasejoinElementValuesTransformer;

class MapDatabasejoinElementValuesTransformerTest extends UnitTestCase
{
	private MapDatabasejoinElementValuesTransformer $transformer;

	protected function setUp(): void
	{
		parent::setUp();
		require_once (JPATH_ROOT . '/components/com_emundus/helpers/files.php');

		$this->transformer = new MapDatabasejoinElementValuesTransformer();
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\MapDatabasejoinElementValuesTransformer::transform
	 * @return void
	 */
	public function testTransform(): void
	{
		$input = $this->dataset['campaign'];
		$fieldIdFabrikElementId = $this->h_dataset->getFormElementForTest(102, 'campaign_id');
		$this->transformer->setFormId(102)
			->setElementId($fieldIdFabrikElementId)
			->setParametersValues(['column' => 'label']);

		$result = $this->transformer->transform($input);
		$this->assertNotEmpty($result);
		$this->assertStringStartsWith('Campagne test unitaire', $result, 'Transforming campaign ID should yield campaign name when setting column to label');
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\MapDatabasejoinElementValuesTransformer::transform
	 * @return void
	 */
	public function testTransformNoContextGiven(): void
	{
		$input = $this->dataset['campaign'];
		$this->transformer->setParametersValues(['column' => 'label']);

		$result = $this->transformer->transform($input);
		$this->assertEquals($input, $result, 'Transforming without context should return original value');
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\MapDatabasejoinElementValuesTransformer::transform
	 * @return void
	 */
	public function testTransformWithMappingRowContext(): void
	{
		$input = $this->dataset['campaign'];
		$fieldIdFabrikElementId = $this->h_dataset->getFormElementForTest(102, 'campaign_id');
		$this->transformer->setParametersValues(['column' => 'label']);

		$mappingRowEntity = new MappingRowEntity(1, 1, 0, ConditionTargetTypeEnum::FORMDATA, '102.' . $fieldIdFabrikElementId, 'target_field', );

		$result = $this->transformer->with($mappingRowEntity)->transform($input);
		$this->assertNotEmpty($result);
		$this->assertStringStartsWith('Campagne test unitaire', $result, 'Transforming campaign ID with mapping row context should yield campaign name when setting column to label');
	}
}