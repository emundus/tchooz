<?php

namespace Unit\Component\Emundus\Class\Services\Field;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Services\Field\FieldOptionProvider;
use Tchooz\Entities\Fields\ChoiceField;

/**
 * @package     Unit\Component\Emundus\Class\Services\Field
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Field\FieldOptionProvider
 */
class FieldOptionProviderTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Services\Field\FieldOptionProvider::provideOptions
	 * @return void
	 */
	public function testProvideOptions(): void
	{
		$optionProvider = new FieldOptionProvider('fabrik', 'getelements', [],
			new FabrikRepository(),
			'getElements',
			[
				'filters' => [
					'plugin' => [
						ElementPluginEnum::NUMERIC->value,
						ElementPluginEnum::CURRENCY->value,
						ElementPluginEnum::AVERAGE->value,
						ElementPluginEnum::EMUNDUS_CALCULATION->value,
						ElementPluginEnum::FIELD->value
					]
				]
			],
			'getName'
		);

		$choiceField = new ChoiceField('element', 'Test Field', [], true, true);
		$choiceField->setOptionsProvider($optionProvider);
		$this->assertEmpty($choiceField->getChoices(), 'Choices should be empty before providing options.');

		try {
			$choiceField->provideOptions();
		} catch (\Exception $e) {
			$this->fail('Providing options threw an exception: ' . $e->getMessage());
		}

		$choices = $choiceField->getChoices();
		$this->assertNotEmpty($choices, 'Choices should be empty before providing options.');
		$this->assertNotEmpty($choices[0]->getLabel(), 'Choice label should not be empty.');
		$this->assertNotEmpty($choices[0]->getValue(), 'Choice value should not be empty.');
	}
}