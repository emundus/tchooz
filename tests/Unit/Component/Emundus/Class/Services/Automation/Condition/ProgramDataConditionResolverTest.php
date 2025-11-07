<?php

namespace Unit\Component\Emundus\Class\Services\Automation\Condition;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\Field;
use Tchooz\Services\Automation\Condition\ProgramDataConditionResolver;

class ProgramDataConditionResolverTest extends UnitTestCase
{

	private ProgramDataConditionResolver $resolver;

	private ActionTargetEntity $context;

	public function setUp(): void
	{
		parent::setUp();
		$this->resolver = new ProgramDataConditionResolver();
		$this->context = new ActionTargetEntity(
			Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			(int)$this->dataset['applicant']
		);
	}

	/**
	 * @covers ProgramDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValue()
	{
		$programId = $this->resolver->resolveValue($this->context, 'id');
		$this->assertEquals($this->dataset['program']['programme_id'], $programId);

		$programLabel = $this->resolver->resolveValue($this->context, 'label');
		$this->assertNotEmpty($programLabel);

		$programCode = $this->resolver->resolveValue($this->context, 'code');
		$this->assertEquals($this->dataset['program']['programme_code'], $programCode);
	}

	/**
	 * @covers ProgramDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueWithInvalidField()
	{
		$invalidField = 'non_existent_field';
		$value = $this->resolver->resolveValue($this->context, $invalidField);
		$this->assertNull($value, "Expected null for invalid field name");
	}

	/**
	 * @covers ProgramDataConditionResolver::getAvailableFields
	 * @return void
	 */
	public function testGetAvailableFields()
	{
		$fields = $this->resolver->getAvailableFields([]);
		$this->assertNotEmpty($fields, 'Available fields should not be empty for program resolver');

		$idField = null;
		foreach ($fields as $field) {
			assert($field instanceof Field);

			if ($field->getName() === 'id') {
				$idField = $field;
				break;
			}
		}
		$this->assertNotNull($idField, "Field 'id' not found in available fields");
		$this->assertInstanceOf(ChoiceField::class, $idField, "Field 'id' should be an instance of ChoiceField");
		$this->assertNotEmpty($idField->getChoices(), "Program's list should not be empty, as there are programs in the dataset");
	}
}