<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Poll
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Poll;

use Joomla\CMS\User\User;
use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Entities\Poll\PollParticipantsEntity;
use Tchooz\Enums\Campaigns\StatusEnum;
use Tchooz\Enums\ColorEnum;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Poll
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Poll\PollParticipantsEntity
 */
class PollParticipantsEntityTest extends TestCase
{
	private function makePoll(int $id = 5): PollEntity
	{
		return new PollEntity($id, 'Sondage', '', ColorEnum::BLUE, StatusEnum::OPEN);
	}

	private function makeUser(int $id = 99): User
	{
		$user     = new User();
		$user->id = $id;

		return $user;
	}

	private function makeParticipant(
		int $id = 1,
		?PollEntity $poll = null,
		string $email = 'p@example.com',
		string $firstname = 'First',
		string $lastname = 'Last',
		?User $user = null
	): PollParticipantsEntity
	{
		return new PollParticipantsEntity($id, $poll, $email, $firstname, $lastname, $user);
	}

	// -------------------------------------------------------------------------
	// Constructor / getters
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::__construct
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getId
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getPoll
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getEmail
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getFirstname
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getLastname
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getUser
	 * @return void
	 */
	public function testConstructorInitializesAllProperties(): void
	{
		$poll = $this->makePoll(5);
		$user = $this->makeUser(42);

		$participant = $this->makeParticipant(7, $poll, 'jean@example.com', 'Jean', 'Dupont', $user);

		$this->assertSame(7, $participant->getId(), 'Constructor should set the id');
		$this->assertSame($poll, $participant->getPoll(), 'Constructor should set the poll');
		$this->assertSame('jean@example.com', $participant->getEmail(), 'Constructor should set the email');
		$this->assertSame('Jean', $participant->getFirstname(), 'Constructor should set the firstname');
		$this->assertSame('Dupont', $participant->getLastname(), 'Constructor should set the lastname');
		$this->assertSame($user, $participant->getUser(), 'Constructor should set the user');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::__construct
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getPoll
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getUser
	 * @return void
	 */
	public function testConstructorAcceptsNullPollAndUser(): void
	{
		$participant = $this->makeParticipant(poll: null, user: null);

		$this->assertNull($participant->getPoll(), 'Poll should accept null');
		$this->assertNull($participant->getUser(), 'User should accept null');
	}

	// -------------------------------------------------------------------------
	// Setters — value updated
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setId
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getId
	 * @return void
	 */
	public function testSetIdUpdatesId(): void
	{
		$participant = $this->makeParticipant(id: 1);
		$participant->setId(99);
		$this->assertSame(99, $participant->getId(), 'setId should update the id');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setPoll
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getPoll
	 * @return void
	 */
	public function testSetPollUpdatesPoll(): void
	{
		$participant = $this->makeParticipant();
		$poll        = $this->makePoll(123);
		$participant->setPoll($poll);
		$this->assertSame($poll, $participant->getPoll(), 'setPoll should update the poll');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setPoll
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getPoll
	 * @return void
	 */
	public function testSetPollAcceptsNull(): void
	{
		$participant = $this->makeParticipant(poll: $this->makePoll());
		$participant->setPoll(null);
		$this->assertNull($participant->getPoll(), 'setPoll should accept null');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setEmail
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getEmail
	 * @return void
	 */
	public function testSetEmailUpdatesEmail(): void
	{
		$participant = $this->makeParticipant(email: 'old@example.com');
		$participant->setEmail('new@example.com');
		$this->assertSame('new@example.com', $participant->getEmail(), 'setEmail should update the email');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setFirstname
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getFirstname
	 * @return void
	 */
	public function testSetFirstnameUpdatesFirstname(): void
	{
		$participant = $this->makeParticipant(firstname: 'Old');
		$participant->setFirstname('New');
		$this->assertSame('New', $participant->getFirstname(), 'setFirstname should update the firstname');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setLastname
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getLastname
	 * @return void
	 */
	public function testSetLastnameUpdatesLastname(): void
	{
		$participant = $this->makeParticipant(lastname: 'Old');
		$participant->setLastname('New');
		$this->assertSame('New', $participant->getLastname(), 'setLastname should update the lastname');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setUser
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getUser
	 * @return void
	 */
	public function testSetUserUpdatesUser(): void
	{
		$participant = $this->makeParticipant();
		$user        = $this->makeUser(777);
		$participant->setUser($user);
		$this->assertSame($user, $participant->getUser(), 'setUser should update the user');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setUser
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::getUser
	 * @return void
	 */
	public function testSetUserAcceptsNull(): void
	{
		$participant = $this->makeParticipant(user: $this->makeUser());
		$participant->setUser(null);
		$this->assertNull($participant->getUser(), 'setUser should accept null');
	}

	// -------------------------------------------------------------------------
	// Fluent interface — every setter must return $this
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setId
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setPoll
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setEmail
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setFirstname
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setLastname
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::setUser
	 * @return void
	 */
	public function testAllSettersReturnSelf(): void
	{
		$participant = $this->makeParticipant();

		$this->assertSame($participant, $participant->setId(2), 'setId should return $this');
		$this->assertSame($participant, $participant->setPoll(null), 'setPoll should return $this');
		$this->assertSame($participant, $participant->setEmail('x@example.com'), 'setEmail should return $this');
		$this->assertSame($participant, $participant->setFirstname('x'), 'setFirstname should return $this');
		$this->assertSame($participant, $participant->setLastname('y'), 'setLastname should return $this');
		$this->assertSame($participant, $participant->setUser(null), 'setUser should return $this');
	}

	// -------------------------------------------------------------------------
	// __serialize — shape stability
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::__serialize
	 * @return void
	 */
	public function testSerializeProducesExpectedShapeWithUser(): void
	{
		$user        = $this->makeUser(321);
		$participant = $this->makeParticipant(7, $this->makePoll(), 'jean@example.com', 'Jean', 'Dupont', $user);

		$serialized = $participant->__serialize();

		$this->assertSame(7, $serialized['id'], 'Serialized id should match');
		$this->assertSame('jean@example.com', $serialized['email'], 'Serialized email should match');
		$this->assertSame('Jean', $serialized['firstname'], 'Serialized firstname should match');
		$this->assertSame('Dupont', $serialized['lastname'], 'Serialized lastname should match');
		$this->assertSame(321, $serialized['user'], 'Serialized user should expose the joomla user id');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollParticipantsEntity::__serialize
	 * @return void
	 */
	public function testSerializeNullUserSerializesToNull(): void
	{
		$participant = $this->makeParticipant(user: null);

		$serialized = $participant->__serialize();

		$this->assertNull($serialized['user'], 'Null user should serialize to null');
	}
}
