<?php

namespace Unit\Component\Emundus\Class\Factories\Field;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Enums\Time\TimeUnitEnum;
use Tchooz\Factories\Field\ChoiceFieldFactory;

class ChoiceFieldFactoryTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Factories\Field\ChoiceFieldFactory::makeOptionsFromEnum
	 * @return void
	 */
	public function testMakeOptionsFromEnum(): void
	{
		$options = ChoiceFieldFactory::makeOptionsFromEnum(TimeUnitEnum::cases());
		$this->assertIsArray($options);
		$this->assertNotEmpty($options);

		$timeUnits = TimeUnitEnum::cases();
		$this->assertCount(count($timeUnits), $options);
		foreach ($options as $option)
		{
			$this->assertContains($option->getValue(), array_column($timeUnits, 'value'));
			$this->assertNotEmpty($option->getLabel());
		}
	}
}
