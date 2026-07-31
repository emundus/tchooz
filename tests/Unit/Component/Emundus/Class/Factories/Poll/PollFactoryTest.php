<?php
/**
 * @package     Unit\Component\Emundus\Class\Factories\Poll
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Factories\Poll;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\DateField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Fields\TextAreaField;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Enums\Campaigns\StatusEnum;
use Tchooz\Enums\ColorEnum;
use Tchooz\Factories\Poll\PollFactory;

/**
 * @package     Unit\Component\Emundus\Class\Factories\Poll
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\Poll\PollFactory
 */
class PollFactoryTest extends UnitTestCase
{
	private PollFactory $factory;

	protected function setUp(): void
	{
		parent::setUp();
		$this->factory = new PollFactory();
	}

	private function createDbObject(array $overrides = []): object
	{
		return (object) array_merge([
			'id'               => 1,
			'name'             => 'Sondage A',
			'description'      => 'Description A',
			'color'            => ColorEnum::BLUE->value,
			'status'           => StatusEnum::OPEN->value,
			'start_date'       => '2026-01-01 09:00:00',
			'end_date'         => '2026-01-31 18:00:00',
			'can_edit_answers' => 0,
			'created_by'       => null,
			'slots'            => '',
			'participants'     => '',
			'programs'         => '',
		], $overrides);
	}

	// -------------------------------------------------------------------------
	// fromDbObject — happy path
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Factories\Poll\PollFactory::fromDbObject
	 * @return void
	 */
	public function testFromDbObjectWithObjectReturnsPollEntity(): void
	{
		$entity = $this->factory->fromDbObject($this->createDbObject());

		$this->assertInstanceOf(PollEntity::class, $entity, 'fromDbObject should return a PollEntity');
	}

	/**
	 * @covers \Tchooz\Factories\Poll\PollFactory::fromDbObject
	 * @return void
	 */
	public function testFromDbObjectWithArrayReturnsPollEntity(): void
	{
		$dbArray = [
			'id'               => 5,
			'name'             => 'Sondage Array',
			'description'      => 'Description Array',
			'color'            => ColorEnum::BLUE->value,
			'status'           => StatusEnum::UPCCOMING->value,
			'start_date'       => null,
			'end_date'         => null,
			'can_edit_answers' => 1,
			'created_by'       => 7,
			'slots'            => '',
			'participants'     => '',
		];

		$entity = $this->factory->fromDbObject($dbArray);

		$this->assertInstanceOf(PollEntity::class, $entity, 'fromDbObject should accept an array input');
		$this->assertSame(5, $entity->getId(), 'fromDbObject should map id');
		$this->assertSame('Sondage Array', $entity->getName(), 'fromDbObject should map name');
		$this->assertSame(StatusEnum::UPCCOMING, $entity->getStatus(), 'fromDbObject should map status enum');
		$this->assertTrue($entity->canEditAnswers(), 'fromDbObject should map can_edit_answers to true');
		$this->assertSame(7, $entity->getCreatedBy(), 'fromDbObject should map created_by');
	}

	/**
	 * @covers \Tchooz\Factories\Poll\PollFactory::fromDbObject
	 * @return void
	 */
	public function testFromDbObjectMapsAllScalarFields(): void
	{
		$dbObject = $this->createDbObject([
			'id'               => 42,
			'name'             => 'Comité',
			'description'      => 'Choix date',
			'color'            => ColorEnum::DARK_BLUE->value,
			'status'           => StatusEnum::CLOSED->value,
			'start_date'       => '2026-03-01 09:00:00',
			'end_date'         => '2026-03-31 18:00:00',
			'can_edit_answers' => 0,
			'created_by'       => 11,
		]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertSame(42, $entity->getId(), 'id should match');
		$this->assertSame('Comité', $entity->getName(), 'name should match');
		$this->assertSame('Choix date', $entity->getDescription(), 'description should match');
		$this->assertSame(ColorEnum::DARK_BLUE, $entity->getColor(), 'color enum should match');
		$this->assertSame(StatusEnum::CLOSED, $entity->getStatus(), 'status enum should match');
		$this->assertInstanceOf(\DateTime::class, $entity->getStartDate(), 'start date should be a DateTime');
		$this->assertSame('2026-03-01 09:00:00', $entity->getStartDate()->format('Y-m-d H:i:s'), 'start date should be parsed');
		$this->assertSame('2026-03-31 18:00:00', $entity->getEndDate()->format('Y-m-d H:i:s'), 'end date should be parsed');
		$this->assertFalse($entity->canEditAnswers(), 'can_edit_answers 0 should map to false');
		$this->assertSame(11, $entity->getCreatedBy(), 'created_by should match');
	}

	// -------------------------------------------------------------------------
	// fromDbObject — fallbacks
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Factories\Poll\PollFactory::fromDbObject
	 * @return void
	 */
	public function testFromDbObjectFallsBackToBlueWhenColorInvalid(): void
	{
		$dbObject = $this->createDbObject(['color' => 'not-a-color']);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertSame(ColorEnum::BLUE, $entity->getColor(), 'Invalid color should fall back to BLUE');
	}

	/**
	 * @covers \Tchooz\Factories\Poll\PollFactory::fromDbObject
	 * @return void
	 */
	public function testFromDbObjectFallsBackToOpenWhenStatusInvalid(): void
	{
		$dbObject = $this->createDbObject(['status' => 'not-a-status']);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertSame(StatusEnum::OPEN, $entity->getStatus(), 'Invalid status should fall back to OPEN');
	}

	/**
	 * @covers \Tchooz\Factories\Poll\PollFactory::fromDbObject
	 * @return void
	 */
	public function testFromDbObjectNullDatesMapToNull(): void
	{
		$dbObject = $this->createDbObject(['start_date' => null, 'end_date' => null]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertNull($entity->getStartDate(), 'Null start_date should map to null');
		$this->assertNull($entity->getEndDate(), 'Null end_date should map to null');
	}

	/**
	 * @covers \Tchooz\Factories\Poll\PollFactory::fromDbObject
	 * @return void
	 */
	public function testFromDbObjectEmptyCreatedByMapsToNull(): void
	{
		$dbObject = $this->createDbObject(['created_by' => null]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertNull($entity->getCreatedBy(), 'Empty created_by should map to null');
	}

	/**
	 * @covers \Tchooz\Factories\Poll\PollFactory::fromDbObject
	 * @return void
	 */
	public function testFromDbObjectEmptySlotsAndParticipantsReturnEmptyArrays(): void
	{
		$entity = $this->factory->fromDbObject($this->createDbObject());

		$this->assertSame([], $entity->getSlots(), 'Empty slots column should produce an empty array');
		$this->assertSame([], $entity->getParticipants(), 'Empty participants column should produce an empty array');
		$this->assertSame([], $entity->getPrograms(), 'Empty programs column should produce an empty array');
	}

	/**
	 * @covers \Tchooz\Factories\Poll\PollFactory::fromDbObject
	 * @return void
	 */
	public function testFromDbObjectParsesProgramsColumn(): void
	{
		$entity = $this->factory->fromDbObject($this->createDbObject(['programs' => '3,7,3']));

		$this->assertSame([3, 7], $entity->getPrograms(), 'Comma-joined programs column should map to a de-duplicated int array');
	}

	// -------------------------------------------------------------------------
	// fromDbObjects
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Factories\Poll\PollFactory::fromDbObjects
	 * @return void
	 */
	public function testFromDbObjectsWithEmptyArrayReturnsEmptyArray(): void
	{
		$entities = $this->factory->fromDbObjects([]);

		$this->assertSame([], $entities, 'fromDbObjects should return [] for an empty input');
	}

	/**
	 * @covers \Tchooz\Factories\Poll\PollFactory::fromDbObjects
	 * @return void
	 */
	public function testFromDbObjectsPreservesOrderAndCount(): void
	{
		$entities = $this->factory->fromDbObjects([
			$this->createDbObject(['id' => 10, 'name' => 'Premier']),
			$this->createDbObject(['id' => 20, 'name' => 'Deuxième']),
		]);

		$this->assertCount(2, $entities, 'fromDbObjects should return one entity per row');
		$this->assertSame(10, $entities[0]->getId(), 'First entity id should match');
		$this->assertSame('Premier', $entities[0]->getName(), 'First entity name should match');
		$this->assertSame(20, $entities[1]->getId(), 'Second entity id should match');
		$this->assertSame('Deuxième', $entities[1]->getName(), 'Second entity name should match');
	}

	// -------------------------------------------------------------------------
	// getFormFields — declarative form contract
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Factories\Poll\PollFactory::getFormFields
	 * @return void
	 */
	public function testGetFormFieldsReturnsExpectedFields(): void
	{
		$fields = $this->factory->getFormFields();

		$this->assertIsArray($fields, 'getFormFields should return an array');
		$this->assertNotEmpty($fields, 'getFormFields should not be empty');

		$byClass = [];
		foreach ($fields as $field)
		{
			$byClass[] = get_class($field);
		}

		$this->assertContains(StringField::class, $byClass, 'Form should expose a StringField (name)');
		$this->assertContains(TextAreaField::class, $byClass, 'Form should expose a TextAreaField (description)');
		$this->assertContains(DateField::class, $byClass, 'Form should expose at least one DateField');
		$this->assertContains(ChoiceField::class, $byClass, 'Form should expose a ChoiceField (status / participants)');
		$this->assertContains(BooleanField::class, $byClass, 'Form should expose a BooleanField (can_edit_answers)');
	}
}
