<?php

namespace Unit\Component\Emundus\Class\Transformers\Mapping;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Transformers\Mapping\UseFormattedValueTransformer;

class UseFormattedValueTransformerTest extends UnitTestCase
{
	private UseFormattedValueTransformer $transformer;

	public function setUp(): void
	{
		parent::setUp();
		$this->transformer = new UseFormattedValueTransformer();
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\UseFormattedValueTransformer::transform
	 * @return void
	 */
	public function testTransform(): void
	{
		$status = 0;
		$mappingRowEntity = new MappingRowEntity(
			1,
			1,
			0,
			ConditionTargetTypeEnum::FILEDATA,
			'status',
			'extern_api_status',
		);
		$actionTargetEntity = new ActionTargetEntity(
			Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			$this->dataset['applicant']
		);

		$formattedValue = $this->transformer
			->with($mappingRowEntity)
			->with($actionTargetEntity)
			->transform($status);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->select($db->quoteName('value'))
			->from($db->quoteName('#__emundus_setup_status'))
			->where($db->quoteName('step') . ' = ' . $db->quote($status));

		$db->setQuery($query);
		$statusLabel = $db->loadResult();

		$this->assertEquals($statusLabel, $formattedValue);
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\UseFormattedValueTransformer::transform
	 * @return void
	 */
	public function testTransformFormField(): void
	{

		$elementId = $this->h_dataset->getFormElementForTest(102, 'campaign_id');
		$fieldName = '102.' . $elementId;
		$mappingRowEntity = new MappingRowEntity(
			1,
			1,
			0,
			ConditionTargetTypeEnum::FORMDATA,
			$fieldName,
			'campaignLabel',
		);

		$actionTargetEntity = new ActionTargetEntity(
			Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			$this->dataset['applicant']
		);

		$formattedValue = $this->transformer
			->with($mappingRowEntity)
			->with($actionTargetEntity)
			->transform($this->dataset['campaign']);

		$campaignRepository = new CampaignRepository();
		$campaign = $campaignRepository->getById($this->dataset['campaign']);

		$this->assertEquals($campaign->getLabel(), $formattedValue);
	}
}