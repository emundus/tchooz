<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionGenerateContact;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Repositories\Contacts\ContactRepository;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Automation\Actions
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Automation\Actions\ActionGenerateContact
 */
class ActionGenerateContactTest extends UnitTestCase
{
	private const CONTACT_EMAIL = 'generated_contact_unit_test@emundus.fr';

	private const CONTACT_LASTNAME = 'DOE';

	private ContactRepository $repository;

	private int $unitTestFormId;

	private string $emailFieldReference;

	private string $nameFieldReference;

	/**
	 * Emails of contacts created/used during a test, deleted on tearDown.
	 *
	 * @var string[]
	 */
	private array $createdContactEmails = [];

	public function setUp(): void
	{
		$this->refreshDataset();

		$this->repository     = new ContactRepository();
		$this->unitTestFormId = $this->h_dataset->getUnitTestFabrikForm();

		// Store the contact email and lastname in real form data so the action can resolve them.
		$this->h_dataset->insertUnitTestFormData($this->dataset['applicant'], $this->dataset['fnum'],
			[
				$this->dataset['applicant'],
				$this->dataset['fnum'],
				self::CONTACT_EMAIL,
				self::CONTACT_LASTNAME,
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

		$emailElementId = $this->h_dataset->getFormElementForTest($this->unitTestFormId, $this->h_dataset::FORM_KEYS['ELEMENT_FIELD']);
		$nameElementId  = $this->h_dataset->getFormElementForTest($this->unitTestFormId, $this->h_dataset::FORM_KEYS['ELEMENT_TEXTAREA']);

		$this->emailFieldReference = $this->unitTestFormId . '.' . $emailElementId;
		$this->nameFieldReference  = $this->unitTestFormId . '.' . $nameElementId;

		$this->createdContactEmails[] = self::CONTACT_EMAIL;

		// Defensive cleanup in case a previous failed run left the contact behind.
		$this->deleteContactByEmail(self::CONTACT_EMAIL);
	}

	protected function tearDown(): void
	{
		foreach ($this->createdContactEmails as $email)
		{
			$this->deleteContactByEmail($email);
		}

		parent::tearDown();
	}

	private function deleteContactByEmail(string $email): void
	{
		$contact = $this->repository->getByEmail($email);
		if (!empty($contact))
		{
			$this->repository->delete($contact->getId());
		}
	}

	private function buildAction(string $duplicateContactAction): ActionGenerateContact
	{
		$action = new ActionGenerateContact();
		$action->setParametersValuesFromArray([
			'lastname_field'           => $this->nameFieldReference,
			'firstname_field'          => $this->nameFieldReference,
			'email_field'              => $this->emailFieldReference,
			'duplicate_contact_action' => $duplicateContactAction,
		]);

		return $action;
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionGenerateContact::execute
	 * @return void
	 * @throws \Exception
	 */
	public function testExecuteCreatesNewContact(): void
	{
		$this->assertNull($this->repository->getByEmail(self::CONTACT_EMAIL), 'No contact with the email defined should exist before the action runs');

		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context     = new ActionTargetEntity($coordinator, $this->dataset['fnum'], (int) $this->dataset['applicant']);

		$action = $this->buildAction('ignore');
		$result = $action->execute($context);

		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);

		$contact = $this->repository->getByEmail(self::CONTACT_EMAIL);
		$this->assertNotEmpty($contact, 'A new contact should have been created');
		$this->assertEquals(self::CONTACT_EMAIL, $contact->getEmail());
		$this->assertEquals(self::CONTACT_LASTNAME, $contact->getLastname());
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionGenerateContact::execute
	 * @return void
	 * @throws \Exception
	 */
	public function testExecuteUpdatesExistingContactWhenDuplicateActionIsUpdate(): void
	{
		$existingId = $this->h_dataset->createSampleContact(self::CONTACT_EMAIL, 'OLD_FIRSTNAME', 'OLD_LASTNAME');
		$this->assertNotEmpty($existingId, 'The existing contact should have been created for the test');

		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context     = new ActionTargetEntity($coordinator, $this->dataset['fnum'], (int) $this->dataset['applicant']);

		$action = $this->buildAction('update_existing_one');
		$result = $action->execute($context);

		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);

		$contact = $this->repository->getByEmail(self::CONTACT_EMAIL);
		$this->assertNotEmpty($contact);
		$this->assertEquals($existingId, $contact->getId(), 'The existing contact should have been updated, not duplicated');
		$this->assertEquals(self::CONTACT_LASTNAME, $contact->getLastname(), 'The contact lastname should have been updated');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionGenerateContact::execute
	 * @return void
	 * @throws \Exception
	 */
	public function testExecuteIgnoresExistingContactWhenDuplicateActionIsIgnore(): void
	{
		$existingId = $this->h_dataset->createSampleContact(self::CONTACT_EMAIL, 'OLD_FIRSTNAME', 'OLD_LASTNAME');
		$this->assertNotEmpty($existingId, 'The existing contact should have been created for the test');

		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context     = new ActionTargetEntity($coordinator, $this->dataset['fnum'], (int) $this->dataset['applicant']);

		$action = $this->buildAction('ignore');
		$result = $action->execute($context);

		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);

		$contact = $this->repository->getByEmail(self::CONTACT_EMAIL);
		$this->assertNotEmpty($contact);
		$this->assertEquals($existingId, $contact->getId(), 'The existing contact should have been kept as is');
		$this->assertEquals('OLD_LASTNAME', $contact->getLastname(), 'The existing contact should not have been modified');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionGenerateContact::setParametersOptionsWithValues
	 * @return void
	 */
	public function testSetParametersOptionsWithValuesResolvesStoredFieldReferences(): void
	{
		$action = $this->buildAction('ignore');
		$action->setParametersOptionsWithValues();

		$expectedReferences = [
			'email_field'     => $this->emailFieldReference,
			'lastname_field'  => $this->nameFieldReference,
			'firstname_field' => $this->nameFieldReference,
		];

		foreach ($expectedReferences as $fieldName => $expectedReference)
		{
			$parameter = null;
			foreach ($action->getParameters() as $candidate)
			{
				if ($candidate->getName() === $fieldName)
				{
					$parameter = $candidate;
					break;
				}
			}

			$this->assertNotNull($parameter, "Parameter \"$fieldName\" should exist");

			$choices = $parameter->getChoices();
			$this->assertCount(1, $choices, "Parameter \"$fieldName\" should have exactly one resolved choice");
			$this->assertEquals($expectedReference, $choices[0]->getValue(), "Parameter \"$fieldName\" choice value should match the stored reference");
			$this->assertNotEmpty($choices[0]->getLabel(), "Parameter \"$fieldName\" choice should have a resolved label");
		}
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionGenerateContact::execute
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
		$this->assertNull($this->repository->getByEmail(self::CONTACT_EMAIL), 'No contact should have been created without a file');
	}
}