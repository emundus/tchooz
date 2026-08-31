<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionGenerateOrganization;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Repositories\Contacts\OrganizationRepository;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Automation\Actions
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Automation\Actions\ActionGenerateOrganization
 */
class ActionGenerateOrganizationTest extends UnitTestCase
{
	private const ORGANIZATION_NAME = 'Generated Organization Unit Test';

	private OrganizationRepository $repository;

	private int $unitTestFormId;

	private string $nameFieldReference;

	/**
	 * Names of organizations created/used during a test, deleted on tearDown.
	 *
	 * @var string[]
	 */
	private array $createdOrganizationNames = [];

	public function setUp(): void
	{
		$this->refreshDataset();

		$this->repository     = new OrganizationRepository();
		$this->unitTestFormId = $this->h_dataset->getUnitTestFabrikForm();

		// Store the organization name in real form data so the action can resolve it.
		$this->h_dataset->insertUnitTestFormData($this->dataset['applicant'], $this->dataset['fnum'],
			[
				$this->dataset['applicant'],
				$this->dataset['fnum'],
				self::ORGANIZATION_NAME,
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
				'',
				'"3","2","1"',
			]);

		$nameElementId            = $this->h_dataset->getFormElementForTest($this->unitTestFormId, $this->h_dataset::FORM_KEYS['ELEMENT_FIELD']);
		$this->nameFieldReference = $this->unitTestFormId . '.' . $nameElementId;

		$this->createdOrganizationNames[] = self::ORGANIZATION_NAME;

		// Defensive cleanup in case a previous failed run left organizations behind.
		$this->deleteOrganizationsByName(self::ORGANIZATION_NAME);
	}

	protected function tearDown(): void
	{
		foreach ($this->createdOrganizationNames as $name)
		{
			$this->deleteOrganizationsByName($name);
		}

		parent::tearDown();
	}

	private function deleteOrganizationsByName(string $name): void
	{
		foreach ($this->repository->get(['name' => $name]) as $organization)
		{
			$this->repository->delete($organization->id);
		}
	}

	private function buildAction(string $duplicateOrganizationAction): ActionGenerateOrganization
	{
		$action = new ActionGenerateOrganization();
		$action->setParametersValuesFromArray([
			'name_field'                    => $this->nameFieldReference,
			'duplicate_organization_action' => $duplicateOrganizationAction,
		]);

		return $action;
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionGenerateOrganization::execute
	 * @return void
	 * @throws \Exception
	 */
	public function testExecuteCreatesNewOrganization(): void
	{
		$this->assertEmpty($this->repository->get(['name' => self::ORGANIZATION_NAME]), 'No organization should exist before the action runs');

		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context     = new ActionTargetEntity($coordinator, $this->dataset['fnum'], (int) $this->dataset['applicant']);

		$action = $this->buildAction('ignore');
		$result = $action->execute($context);

		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);

		$organizations = $this->repository->get(['name' => self::ORGANIZATION_NAME]);
		$this->assertCount(1, $organizations, 'A new organization should have been created');
		$this->assertEquals(self::ORGANIZATION_NAME, $organizations[0]->name);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionGenerateOrganization::execute
	 * @return void
	 * @throws \Exception
	 */
	public function testExecuteCreatesAnotherOrganizationWhenDuplicateActionIsCreateNew(): void
	{
		$existing = new OrganizationEntity(0, self::ORGANIZATION_NAME);
		$this->assertTrue($this->repository->flush($existing), 'The existing organization should have been created for the test');

		$countBefore = count($this->repository->get(['name' => self::ORGANIZATION_NAME]));

		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context     = new ActionTargetEntity($coordinator, $this->dataset['fnum'], (int) $this->dataset['applicant']);

		$action = $this->buildAction('create_new_one');
		$result = $action->execute($context);

		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);

		$countAfter = count($this->repository->get(['name' => self::ORGANIZATION_NAME]));
		$this->assertEquals($countBefore + 1, $countAfter, 'A second organization with the same name should have been created');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionGenerateOrganization::execute
	 * @return void
	 * @throws \Exception
	 */
	public function testExecuteIgnoresExistingOrganizationWhenDuplicateActionIsIgnore(): void
	{
		$existing = new OrganizationEntity(0, self::ORGANIZATION_NAME);
		$this->assertTrue($this->repository->flush($existing), 'The existing organization should have been created for the test');

		$countBefore = count($this->repository->get(['name' => self::ORGANIZATION_NAME]));

		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context     = new ActionTargetEntity($coordinator, $this->dataset['fnum'], (int) $this->dataset['applicant']);

		$action = $this->buildAction('ignore');
		$result = $action->execute($context);

		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);

		$countAfter = count($this->repository->get(['name' => self::ORGANIZATION_NAME]));
		$this->assertEquals($countBefore, $countAfter, 'No new organization should have been created');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionGenerateOrganization::setParametersOptionsWithValues
	 * @return void
	 */
	public function testSetParametersOptionsWithValuesResolvesStoredFieldReference(): void
	{
		$action = $this->buildAction('ignore');
		$action->setParametersOptionsWithValues();

		$parameter = null;
		foreach ($action->getParameters() as $candidate)
		{
			if ($candidate->getName() === 'name_field')
			{
				$parameter = $candidate;
				break;
			}
		}

		$this->assertNotNull($parameter, 'Parameter "name_field" should exist');

		$choices = $parameter->getChoices();
		$this->assertCount(1, $choices, 'Parameter "name_field" should have exactly one resolved choice');
		$this->assertEquals($this->nameFieldReference, $choices[0]->getValue(), 'Parameter "name_field" choice value should match the stored reference');
		$this->assertNotEmpty($choices[0]->getLabel(), 'Parameter "name_field" choice should have a resolved label');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionGenerateOrganization::execute
	 * @return void
	 * @throws \Exception
	 */
	public function testExecuteWithoutFileReturnsFailed(): void
	{
		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context     = new ActionTargetEntity($coordinator, '', 0);

		$action = $this->buildAction('ignore');
		$result = $action->execute($context);

		$this->assertEquals(ActionExecutionStatusEnum::FAILED, $result);
		$this->assertEmpty($this->repository->get(['name' => self::ORGANIZATION_NAME]), 'No organization should have been created without a file');
	}
}