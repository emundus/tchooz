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
use Tchooz\Entities\ApplicationFile\StatusEntity;
use Tchooz\Entities\Campaigns\CampaignEntity;

/**
 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity
 */
class ApplicationFileEntityTest extends UnitTestCase
{
	private User $user;

	protected function setUp(): void
	{
		parent::setUp();

		$this->user = $this->createMock(User::class);
		$this->user->id = 42;
		$this->user->name = 'John Doe';
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::__construct
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getUser
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getFnum
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getStatus
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getCampaignId
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getPublished
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getData
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getId
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getCampaign
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getDateSubmitted
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getFormProgress
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getAttachmentProgress
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getApplicationChoices
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new ApplicationFileEntity($this->user);

		$this->assertSame($this->user, $entity->getUser());
		$this->assertSame('', $entity->getFnum());
		$this->assertSame(0, $entity->getStatus());
		$this->assertSame(0, $entity->getCampaignId());
		$this->assertSame(1, $entity->getPublished());
		$this->assertSame([], $entity->getData());
		$this->assertSame(0, $entity->getId());
		$this->assertNull($entity->getCampaign());
		$this->assertNull($entity->getDateSubmitted());
		$this->assertSame(0, $entity->getFormProgress());
		$this->assertSame(0, $entity->getAttachmentProgress());
		$this->assertNull($entity->getApplicationChoices());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$campaign = $this->createMock(CampaignEntity::class);
		$status = new StatusEntity(1, 1, 'Submitted', 1, '#00FF00');
		$dateSubmitted = new \DateTime('2025-06-01 10:00:00');
		$updatedAt = new \DateTime('2025-06-02 12:00:00');
		$updatedBy = $this->createMock(User::class);
		$name = 'Applicant Name';

		$entity = new ApplicationFileEntity(
			$this->user, '0001-0001', $status, 10,
			1, ['key' => 'val'], 99,
			$campaign, $dateSubmitted, 50, 75,
			$name, $updatedAt, $updatedBy
		);

		$this->assertSame(99, $entity->getId());
		$this->assertSame('0001-0001', $entity->getFnum());
		$this->assertSame($status, $entity->getStatus());
		$this->assertSame(10, $entity->getCampaignId());
		$this->assertSame(1, $entity->getPublished());
		$this->assertSame(['key' => 'val'], $entity->getData());
		$this->assertSame($campaign, $entity->getCampaign());
		$this->assertSame($dateSubmitted, $entity->getDateSubmitted());
		$this->assertSame(50, $entity->getFormProgress());
		$this->assertSame(75, $entity->getAttachmentProgress());
		$this->assertSame($name, $entity->getName());
		$this->assertSame($updatedAt, $entity->getUpdatedAt());
		$this->assertSame($updatedBy, $entity->getUpdatedBy());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::__construct
	 */
	public function testInstanciationWithIntStatus(): void
	{
		$entity = new ApplicationFileEntity($this->user, '', 5);

		$this->assertSame(5, $entity->getStatus());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::__construct
	 */
	public function testInstanciationWithNullStatus(): void
	{
		$entity = new ApplicationFileEntity($this->user, '', null);

		$this->assertNull($entity->getStatus());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setId
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setUser
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setFnum
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setStatus
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setCampaignId
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setPublished
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setData
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setCampaign
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setDateSubmitted
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setFormProgress
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setAttachmentProgress
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setUpdatedAt
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getUpdatedAt
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setUpdatedBy
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::getUpdatedBy
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setApplicationChoices
	 */
	public function testSetters(): void
	{
		$entity = new ApplicationFileEntity($this->user);

		$newUser = $this->createMock(User::class);
		$campaign = $this->createMock(CampaignEntity::class);
		$status = new StatusEntity(2, 2, 'Accepted', 2, '#0000FF');
		$dateSubmitted = new \DateTime('2025-07-01');
		$updatedAt = new \DateTime('2025-07-02');
		$updatedBy = $this->createMock(User::class);
		$choices = [$this->createMock(ApplicationChoicesEntity::class)];

		$entity->setId(50);
		$entity->setUser($newUser);
		$entity->setFnum('9999-9999');
		$entity->setStatus($status);
		$entity->setCampaignId(20);
		$entity->setPublished(0);
		$entity->setData(['a' => 'b']);
		$entity->setCampaign($campaign);
		$entity->setDateSubmitted($dateSubmitted);
		$entity->setFormProgress(80);
		$entity->setAttachmentProgress(90);
		$entity->setUpdatedAt($updatedAt);
		$entity->setUpdatedBy($updatedBy);
		$entity->setApplicationChoices($choices);

		$this->assertSame(50, $entity->getId());
		$this->assertSame($newUser, $entity->getUser());
		$this->assertSame('9999-9999', $entity->getFnum());
		$this->assertSame($status, $entity->getStatus());
		$this->assertSame(20, $entity->getCampaignId());
		$this->assertSame(0, $entity->getPublished());
		$this->assertSame(['a' => 'b'], $entity->getData());
		$this->assertSame($campaign, $entity->getCampaign());
		$this->assertSame($dateSubmitted, $entity->getDateSubmitted());
		$this->assertSame(80, $entity->getFormProgress());
		$this->assertSame(90, $entity->getAttachmentProgress());
		$this->assertSame($updatedAt, $entity->getUpdatedAt());
		$this->assertSame($updatedBy, $entity->getUpdatedBy());
		$this->assertSame($choices, $entity->getApplicationChoices());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::setStatus
	 */
	public function testSetStatusAcceptsIntAndNull(): void
	{
		$entity = new ApplicationFileEntity($this->user);

		$entity->setStatus(3);
		$this->assertSame(3, $entity->getStatus());

		$entity->setStatus(null);
		$this->assertNull($entity->getStatus());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::generateFnum
	 */
	public function testGenerateFnumWithDefaults(): void
	{
		$entity = new ApplicationFileEntity($this->user, '', 0, 5);

		$fnum = $entity->generateFnum();

		// Format: YYYYMMDDHHmmss + campaign_id (7 chars) + random 7 chars
		$this->assertSame(28, strlen($fnum));
		$campaignId = $entity->getCampaignId();
		$campaignIdStr = str_pad($campaignId, 7, '0', STR_PAD_LEFT);
		$this->assertStringContainsString($campaignIdStr, $fnum);
		$this->assertSame($fnum, $entity->getFnum());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::generateFnum
	 */
	public function testGenerateFnumWithExplicitParams(): void
	{
		$entity = new ApplicationFileEntity($this->user);
		$fnum = $entity->generateFnum(12, 99);

		$this->assertSame(28, strlen($fnum));
		$campaignId = $entity->getCampaignId();
		$campaignIdStr = str_pad($campaignId, 7, '0', STR_PAD_LEFT);
		$this->assertStringContainsString($campaignIdStr, $fnum);
		$this->assertSame($fnum, $entity->getFnum());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::__serialize
	 */
	public function testSerialize(): void
	{
		$dateSubmitted = new \DateTime('2025-06-01 10:00:00');
		$updatedAt = new \DateTime('2025-06-02 12:00:00');
		$updatedBy = $this->createMock(User::class);
		$updatedBy->name = 'Jane Doe';

		$entity = new ApplicationFileEntity(
			$this->user, '0001-0001', 1, 10,
			1, ['key' => 'val'], 99,
			null, $dateSubmitted, 50, 75,
			null, $updatedAt, $updatedBy
		);

		$serialized = $entity->__serialize();

		$this->assertSame(99, $serialized['id']);
		$this->assertSame('John Doe', $serialized['user']);
		$this->assertSame('0001-0001', $serialized['fnum']);
		$this->assertSame(['id' => 1], $serialized['status']);
		$this->assertSame(10, $serialized['campaign_id']);
		$this->assertNull($serialized['campaign']);
		$this->assertSame('2025-06-01 10:00:00', $serialized['date_submitted']);
		$this->assertSame(1, $serialized['published']);
		$this->assertSame(['key' => 'val'], $serialized['data']);
		$this->assertSame(50, $serialized['formProgress']);
		$this->assertSame(75, $serialized['attachmentProgress']);
		$this->assertSame('2025-06-02 12:00:00', $serialized['updated_at']);
		$this->assertSame('Jane Doe', $serialized['updated_by']);
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\ApplicationFileEntity::__serialize
	 */
	public function testSerializeUsesUserName(): void
	{
		$entity = new ApplicationFileEntity($this->user);

		$serialized = $entity->__serialize();

		$this->assertSame('John Doe', $serialized['user']);
	}
}

