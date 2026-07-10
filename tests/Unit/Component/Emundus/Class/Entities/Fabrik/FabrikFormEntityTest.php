<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Fabrik;

use Joomla\CMS\User\User;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Fabrik\FabrikFormEntity;
use Tchooz\Entities\Fabrik\FabrikFormParams;

/**
 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity
 */
class FabrikFormEntityTest extends UnitTestCase
{
	private \DateTime $created;
	private User $user;

	protected function setUp(): void
	{
		parent::setUp();
		$this->created = new \DateTime('2025-01-15 10:00:00');
		$this->user = $this->createMock(User::class);
	}

	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::__construct
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getId
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getIntro
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getCreated
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getCreatedBy
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::isRecordInDatabase
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getErrorMessage
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getModifiedBy
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getResetButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getSubmitButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getFormTemplate
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getViewOnlyTemplate
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::isPublished
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getPrivate
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getParams
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getParamsRaw
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getGroups
	 */
	public function testInstanciation(): void
	{
		$entity = new FabrikFormEntity(1, 'Form Label', 'Intro text', $this->created, $this->user);

		$this->assertSame(1, $entity->getId());
		$this->assertSame('Form Label', $entity->getLabel());
		$this->assertSame('Intro text', $entity->getIntro());
		$this->assertSame($this->created, $entity->getCreated());
		$this->assertSame($this->user, $entity->getCreatedBy());
		$this->assertTrue($entity->isRecordInDatabase());
		$this->assertSame('FORM_ERROR', $entity->getErrorMessage());
		$this->assertNull($entity->getModifiedBy());
		$this->assertSame('RESET', $entity->getResetButtonLabel());
		$this->assertSame('SAVE_CONTINUE', $entity->getSubmitButtonLabel());
		$this->assertSame('emundus', $entity->getFormTemplate());
		$this->assertSame('emundus', $entity->getViewOnlyTemplate());
		$this->assertTrue($entity->isPublished());
		$this->assertSame(0, $entity->getPrivate());
		$this->assertNull($entity->getParams());
		$this->assertSame('', $entity->getParamsRaw());
		$this->assertSame([], $entity->getGroups());
	}

	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setId
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setRecordInDatabase
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setErrorMessage
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setIntro
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setCreated
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setCreatedBy
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setModified
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::getModified
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setModifiedBy
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setResetButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setSubmitButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setFormTemplate
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setViewOnlyTemplate
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setPublished
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setPrivate
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setParams
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setParamsRaw
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormEntity::setGroups
	 */
	public function testSetters(): void
	{
		$entity = new FabrikFormEntity(1, 'Label', 'Intro', $this->created, $this->user);
		$modified = new \DateTime('2025-06-01');
		$newUser = $this->createMock(User::class);
		$params = new FabrikFormParams();

		$entity->setId(99);
		$entity->setLabel('New Label');
		$entity->setRecordInDatabase(false);
		$entity->setErrorMessage('CUSTOM_ERROR');
		$entity->setIntro('New Intro');
		$entity->setCreated($modified);
		$entity->setCreatedBy($newUser);
		$entity->setModified($modified);
		$entity->setModifiedBy($newUser);
		$entity->setResetButtonLabel('CUSTOM_RESET');
		$entity->setSubmitButtonLabel('CUSTOM_SUBMIT');
		$entity->setFormTemplate('custom_tpl');
		$entity->setViewOnlyTemplate('custom_view');
		$entity->setPublished(false);
		$entity->setPrivate(1);
		$entity->setParams($params);
		$entity->setParamsRaw('{"key":"val"}');
		$entity->setGroups(['group1']);

		$this->assertSame(99, $entity->getId());
		$this->assertSame('New Label', $entity->getLabel());
		$this->assertFalse($entity->isRecordInDatabase());
		$this->assertSame('CUSTOM_ERROR', $entity->getErrorMessage());
		$this->assertSame('New Intro', $entity->getIntro());
		$this->assertSame($modified, $entity->getCreated());
		$this->assertSame($newUser, $entity->getCreatedBy());
		$this->assertSame($modified, $entity->getModified());
		$this->assertSame($newUser, $entity->getModifiedBy());
		$this->assertSame('CUSTOM_RESET', $entity->getResetButtonLabel());
		$this->assertSame('CUSTOM_SUBMIT', $entity->getSubmitButtonLabel());
		$this->assertSame('custom_tpl', $entity->getFormTemplate());
		$this->assertSame('custom_view', $entity->getViewOnlyTemplate());
		$this->assertFalse($entity->isPublished());
		$this->assertSame(1, $entity->getPrivate());
		$this->assertSame($params, $entity->getParams());
		$this->assertSame('{"key":"val"}', $entity->getParamsRaw());
		$this->assertSame(['group1'], $entity->getGroups());
	}
}

