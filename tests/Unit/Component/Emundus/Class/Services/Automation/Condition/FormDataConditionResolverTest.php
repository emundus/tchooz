<?php

namespace Unit\Component\Emundus\Class\Services\Automation\Condition;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Services\Automation\Condition\FormDataConditionResolver;
use Tchooz\Services\Automation\Condition\UserDataConditionResolver;

class FormDataConditionResolverTest extends UnitTestCase
{
	private ActionTargetEntity $context;

	private FormDataConditionResolver $resolver;

	private int $unitTesFormId;

	public function setUp(): void
	{
		parent::setUp();
		$this->resolver = new FormDataConditionResolver();
		$this->context = new ActionTargetEntity(
			Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			(int)$this->dataset['applicant']
		);
		$this->unitTesFormId = $this->h_dataset->getUnitTestFabrikForm();
		$this->h_dataset->insertUnitTestFormData($this->dataset['applicant'], $this->dataset['fnum'],
			[
				$this->dataset['applicant'],
				$this->dataset['fnum'],
				'TEST FIELD',
				'TEST TEXTAREA',
				'["1"]',
				'2',
				'3',
				'65',
				'Ajoutez du texte personnalisé pour vos candidats',
				"<p>S'il vous plait taisez vous</p>",
				'1',
				'2023-01-01',
				'2023-07-13 00:00:00',
				'["0","1"]',
				0,
				''
			]);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValue(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest(102, 'fnum');
		$fieldName = '102.' . $elementId;

		try {
			$fnumValue = $this->resolver->resolveValue($this->context, $fieldName);
			$this->assertEquals($this->dataset['fnum'], $fnumValue);
		} catch (\Exception $e) {
			$this->fail("Exception thrown during resolveValue: " . $e->getMessage());
		}
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueDatabaseJoinElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest(102, 'campaign_id');
		$fieldName = '102.' . $elementId;

		try {
			$campaignIdValue = $this->resolver->resolveValue($this->context, $fieldName);
			$this->assertEquals($this->dataset['campaign'], $campaignIdValue);
		} catch (\Exception $e) {
			$this->fail("Exception thrown during resolveValue for database join element: " . $e->getMessage());
		}
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueFormattedDatabaseJoinElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest(102, 'campaign_id');
		$fieldName = '102.' . $elementId;

		try {
			$campaignLabelValue = $this->resolver->resolveValue($this->context, $fieldName, ValueFormatEnum::FORMATTED);
			$this->assertNotEmpty($campaignLabelValue);
			$this->assertNotEquals($this->dataset['campaign'], $campaignLabelValue);
			$this->assertIsString($campaignLabelValue);
			$this->assertIsNotNumeric($campaignLabelValue);
		} catch (\Exception $e) {
			$this->fail("Exception thrown during resolveValue for database join element: " . $e->getMessage());
		}
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueWithInvalidField(): void
	{
		$invalidField = '102.99999999';
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Field not found: ' . $invalidField);
		$value = $this->resolver->resolveValue($this->context, $invalidField);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueFieldElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_FIELD']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;

		try {
			$fieldValue = $this->resolver->resolveValue($this->context, $fieldName);
			$this->assertEquals('TEST FIELD', $fieldValue);
		} catch (\Exception $e) {
			$this->fail("Exception thrown during resolveValue for field element: " . $e->getMessage());
		}
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueTextAreaElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_TEXTAREA']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;

		try {
			$textareaValue = $this->resolver->resolveValue($this->context, $fieldName);
			$this->assertEquals('TEST TEXTAREA', $textareaValue);
		} catch (\Exception $e) {
			$this->fail("Exception thrown during resolveValue for textarea element: " . $e->getMessage());
		}
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueCheckboxElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_CHECKBOX']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;
		$value = $this->resolver->resolveValue($this->context, $fieldName);
		$this->assertEquals(1, $value);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueRadioElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_RADIO']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;
		$value = $this->resolver->resolveValue($this->context, $fieldName);
		$this->assertEquals('2', $value);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueDropdownElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_DROPDOWN']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;
		$value = $this->resolver->resolveValue($this->context, $fieldName);
		$this->assertEquals('3', $value);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueDbJoinElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_DBJOIN']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;
		$value = $this->resolver->resolveValue($this->context, $fieldName);
		$this->assertEquals('65', $value);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueDisplayElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_DISPLAY']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;
		$value = $this->resolver->resolveValue($this->context, $fieldName);
		$this->assertEquals('Ajoutez du texte personnalisé pour vos candidats', $value);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueDisplay2Element(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_DISPLAY_2']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;
		$value = $this->resolver->resolveValue($this->context, $fieldName);
		$this->assertEquals("<p>S'il vous plait taisez vous</p>", $value);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueYesNoElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_YESNO']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;
		$value = $this->resolver->resolveValue($this->context, $fieldName);
		$this->assertEquals('1', $value);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueBirthdayElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_BIRTHDAY']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;
		$value = $this->resolver->resolveValue($this->context, $fieldName);
		$this->assertEquals('2023-01-01', $value);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueDateElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_DATE']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;
		$value = $this->resolver->resolveValue($this->context, $fieldName);
		$this->assertEquals('2023-07-13 00:00:00', $value);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueDropdownMultiElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_DROPDOWN_MULTI']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;
		$value = $this->resolver->resolveValue($this->context, $fieldName);
		$this->assertEquals([0, 1], $value);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueDbJoinMultiElement(): void
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_DBJOIN_MULTI']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;
		$value = $this->resolver->resolveValue($this->context, $fieldName);
		$this->assertEquals(17, $value);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValueFromUserId(): void
	{
		$resolver = new UserDataConditionResolver();
		$formId = $resolver->getProfileAreaFormId();
		$this->assertIsInt($formId);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();
		$elementId = $this->h_dataset->getFormElementForTest($formId, 'nationality');
		$fieldName = $formId . '.' . $elementId;

		$query->clear()
			->update($db->quoteName('#__fabrik_elements'))
			->set($db->quoteName('published') . ' = 1')
			->where($db->quoteName('id') . ' = ' . $db->quote($elementId));
		$db->setQuery($query);
		$db->execute();

		$userTargetContext = new ActionTargetEntity($this->context->getTriggeredBy(), null, $this->context->getUserId());
		$value = $this->resolver->resolveValue($userTargetContext, $fieldName);
		$this->assertEmpty($value);

		$query->clear()
			->update($db->quoteName('#__emundus_users'))
			->set($db->quoteName('nationality') . ' = ' . $db->quote(65))
			->where($db->quoteName('user_id') . ' = ' . $db->quote($this->dataset['applicant']));

		$db->setQuery($query);
		$db->execute();
		$valueAfterUpdate = $this->resolver->resolveValue($userTargetContext, $fieldName);

		$this->assertEquals(65, $valueAfterUpdate);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FormDataConditionResolver::searchFieldValues
	 * @return void
	 */
	public function testSearchFieldValues()
	{
		$elementId = $this->h_dataset->getFormElementForTest($this->unitTesFormId, $this->h_dataset::FORM_KEYS['ELEMENT_DBJOIN']);
		$fieldName = $this->unitTesFormId . '.' . $elementId;

		$search = '';
		$options = $this->resolver->searchFieldValues($fieldName, $search);
		$this->assertEmpty($options, 'Search term is Required');

		// search through nationality, fra should exist
		$search = 'Fra';
		$filteredOptions = $this->resolver->searchFieldValues($fieldName, $search);
		$this->assertNotEmpty($filteredOptions);
		$this->assertInstanceOf(ChoiceFieldValue::class, $filteredOptions[0]);
		$this->assertStringContainsString('Fra', $filteredOptions[0]->getLabel());
	}
}