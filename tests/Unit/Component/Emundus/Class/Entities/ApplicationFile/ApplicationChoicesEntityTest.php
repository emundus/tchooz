<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\ApplicationFile;

use Joomla\CMS\User\User;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;

/**
 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity
 */
class ApplicationChoicesEntityTest extends UnitTestCase
{
	private User $user;
	private CampaignEntity $campaign;

	protected function setUp(): void
	{
		parent::setUp();

		$this->user = $this->createMock(User::class);
		$this->user->id = 42;
		$this->user->name = 'John Doe';
		$this->user->email = 'john@example.com';

		$this->campaign = $this->createMock(CampaignEntity::class);
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::__construct
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::getFnum
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::getUser
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::getCampaign
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::getCampaignId
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::getOrder
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::getState
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::getId
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::getMoreProperties
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::getApplicationFile
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new ApplicationChoicesEntity('0001-0001', $this->user, $this->campaign, 10);

		$this->assertSame('0001-0001', $entity->getFnum());
		$this->assertSame($this->user, $entity->getUser());
		$this->assertSame($this->campaign, $entity->getCampaign());
		$this->assertSame(10, $entity->getCampaignId());
		$this->assertSame(0, $entity->getOrder());
		$this->assertSame(ChoicesStateEnum::DRAFT, $entity->getState());
		$this->assertSame(0, $entity->getId());
		$this->assertSame([], $entity->getMoreProperties());
		$this->assertNull($entity->getApplicationFile());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$appFile = $this->createMock(ApplicationFileEntity::class);

		$entity = new ApplicationChoicesEntity(
			'0002-0002', $this->user, $this->campaign, 20,
			3, ChoicesStateEnum::ACCEPTED, 99,
			['key' => 'value'], $appFile
		);

		$this->assertSame('0002-0002', $entity->getFnum());
		$this->assertSame(20, $entity->getCampaignId());
		$this->assertSame(3, $entity->getOrder());
		$this->assertSame(ChoicesStateEnum::ACCEPTED, $entity->getState());
		$this->assertSame(99, $entity->getId());
		$this->assertSame(['key' => 'value'], $entity->getMoreProperties());
		$this->assertSame($appFile, $entity->getApplicationFile());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::__construct
	 */
	public function testInstanciationWithNullCampaign(): void
	{
		$entity = new ApplicationChoicesEntity('0003-0003', $this->user, null, 5);

		$this->assertNull($entity->getCampaign());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::setId
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::setFnum
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::setUser
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::setCampaign
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::setCampaignId
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::setState
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::setOrder
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::setMoreProperties
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::setApplicationFile
	 */
	public function testSetters(): void
	{
		$entity = new ApplicationChoicesEntity('0001-0001', $this->user, $this->campaign, 10);

		$newUser = $this->createMock(User::class);
		$newCampaign = $this->createMock(CampaignEntity::class);
		$appFile = $this->createMock(ApplicationFileEntity::class);

		$entity->setId(50);
		$entity->setFnum('9999-9999');
		$entity->setUser($newUser);
		$entity->setCampaign($newCampaign);
		$entity->setCampaignId(30);
		$entity->setState(ChoicesStateEnum::CONFIRMED);
		$entity->setOrder(5);
		$entity->setMoreProperties(['prop' => 'val']);
		$entity->setApplicationFile($appFile);

		$this->assertSame(50, $entity->getId());
		$this->assertSame('9999-9999', $entity->getFnum());
		$this->assertSame($newUser, $entity->getUser());
		$this->assertSame($newCampaign, $entity->getCampaign());
		$this->assertSame(30, $entity->getCampaignId());
		$this->assertSame(ChoicesStateEnum::CONFIRMED, $entity->getState());
		$this->assertSame(5, $entity->getOrder());
		$this->assertSame(['prop' => 'val'], $entity->getMoreProperties());
		$this->assertSame($appFile, $entity->getApplicationFile());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::setCampaignId
	 */
	public function testSetCampaignIdReturnsFluent(): void
	{
		$entity = new ApplicationChoicesEntity('0001-0001', $this->user, $this->campaign, 10);

		$result = $entity->setCampaignId(99);

		$this->assertSame($entity, $result);
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity::__serialize
	 */
	public function testSerialize(): void
	{
		$this->campaign
			->method('__serialize')
			->willReturn(['id' => 10, 'label' => 'Campaign Test']);

		$entity = new ApplicationChoicesEntity(
			'0001-0001', $this->user, $this->campaign, 10,
			2, ChoicesStateEnum::ACCEPTED, 1, ['extra' => 'data']
		);

		$serialized = $entity->__serialize();

		$this->assertSame(1, $serialized['id']);
		$this->assertSame('0001-0001', $serialized['fnum']);
		$this->assertSame(10, $serialized['campaign_id']);
		$this->assertSame(2, $serialized['order']);

		// Campaign is serialized
		$this->assertSame(['id' => 10, 'label' => 'Campaign Test'], $serialized['campaign']);

		// State is serialized as name/value pair
		$this->assertSame('ACCEPTED', $serialized['state']['name']);
		$this->assertSame(1, $serialized['state']['value']);

		// User is serialized as id/name/email
		$this->assertSame(42, $serialized['user']['id']);
		$this->assertSame('John Doe', $serialized['user']['name']);
		$this->assertSame('john@example.com', $serialized['user']['email']);
	}
}

