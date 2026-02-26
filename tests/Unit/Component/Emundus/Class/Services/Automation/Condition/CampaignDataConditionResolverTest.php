<?php

namespace Unit\Component\Emundus\Class\Services\Automation\Condition;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\Field;
use Tchooz\Services\Automation\Condition\CampaignDataConditionResolver;

class CampaignDataConditionResolverTest extends UnitTestCase
{

	private CampaignDataConditionResolver $resolver;

	private ActionTargetEntity $context;

	public function setUp(): void
	{
		parent::setUp();
		$this->resolver = new CampaignDataConditionResolver();
		$this->context = new ActionTargetEntity(
			Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			(int)$this->dataset['applicant']
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\CampaignDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValue(): void
	{
		$campaignId = $this->resolver->resolveValue($this->context, 'id');
		$this->assertEquals($this->dataset['campaign'], $campaignId);

		$campaignLabel = $this->resolver->resolveValue($this->context, 'label');
		$this->assertNotEmpty($campaignLabel);

		$campaignCode = $this->resolver->resolveValue($this->context, 'training');
		$this->assertEquals($this->dataset['program']['programme_code'], $campaignCode);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\CampaignDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueWithInvalidField()
	{
		$invalidField = 'non_existent_field';
		$value = $this->resolver->resolveValue($this->context, $invalidField);
		$this->assertNull($value, "Expected null for invalid field name");
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\CampaignDataConditionResolver::getAvailableFields
	 * @return void
	 */
	public function testGetAvailableFields(): void
	{
		$fields = $this->resolver->getAvailableFields([]);
		$this->assertIsArray($fields);
		$this->assertNotEmpty($fields);
		$this->assertContainsOnlyInstancesOf(Field::class, $fields);

		$idField = null;
		foreach ($fields as $field) {
			if ($field->getName() === 'id') {
				$idField = $field;
				break;
			}
		}

		$this->assertNotNull($idField, 'ID field should be present in available fields');
		$this->assertInstanceOf(ChoiceField::class, $idField);
		$this->assertNotEmpty($idField->getChoices(), 'Campaign ID field should have choices as there is at least one campaign in the dataset');
	}
}