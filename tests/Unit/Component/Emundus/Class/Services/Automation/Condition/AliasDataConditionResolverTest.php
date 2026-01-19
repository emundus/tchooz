<?php

namespace Unit\Component\Emundus\Class\Services\Automation\Condition;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Services\Automation\Condition\AliasDataConditionResolver;

class AliasDataConditionResolverTest extends UnitTestCase
{
	private AliasDataConditionResolver $resolver;

	private ActionTargetEntity $context;

	public function setUp(): void
	{
		parent::setUp();
		$this->resolver = new AliasDataConditionResolver();
		$this->context = new ActionTargetEntity(
			Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			(int)$this->dataset['applicant']
		);

		$fnumElementId = $this->h_dataset->getFormElementForTest(102, 'fnum');
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->clear()
			->update($db->quoteName('#__fabrik_elements'))
			->set($db->quoteName('alias') . ' = ' . $db->quote('fnum'))
			->where($db->quoteName('id') . ' = ' . (int)$fnumElementId);
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * @covers AliasDataConditionResolver::getAvailableFields
	 * @return void
	 */
	public function testGetAvailableFields(): void
	{
		$fields = $this->resolver->getAvailableFields([]);
		$this->assertIsArray($fields, 'Expected an array of fields');
		$this->assertNotEmpty($fields, 'Expected at least one field to be available');

		$foundAliasField = false;
		foreach ($fields as $field) {
			if ($field->getName() === 'fnum') {
				$foundAliasField = true;
				break;
			}
		}
		$this->assertTrue($foundAliasField, 'Expected to find the alias field "fnum" in the available fields');
	}

	/**
	 * @covers AliasDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValue(): void
	{
		$aliasField = 'fnum';
		$aliasValue = $this->resolver->resolveValue($this->context, $aliasField);
		$this->assertEquals($this->dataset['fnum'], $aliasValue);
	}
}