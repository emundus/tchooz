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
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Enums\Fabrik\ElementPluginEnum;

/**
 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity
 */
class FabrikElementEntityTest extends UnitTestCase
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
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::__construct
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getId
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getName
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getGroupId
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getPlugin
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getCreated
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getCreatedBy
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getParamsRaw
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getDbTableName
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getTableJoin
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getGroupParamsRaw
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getAlias
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getDefault
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getEval
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::isPublished
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getHidden
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getWidth
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getHeight
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getOrdering
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getShowInListSummary
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getFilterType
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getFilterExactMatch
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getLinkToDetail
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getPrimaryKey
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getAutoIncrement
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getAccess
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getUseInPageTitle
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getParentId
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getModifiedBy
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new FabrikElementEntity(
			1, 'element_name', 10, ElementPluginEnum::FIELD,
			'Element Label', $this->created, $this->user
		);

		$this->assertSame(1, $entity->getId());
		$this->assertSame('element_name', $entity->getName());
		$this->assertSame(10, $entity->getGroupId());
		$this->assertSame(ElementPluginEnum::FIELD, $entity->getPlugin());
		$this->assertSame('Element Label', $entity->getLabel());
		$this->assertSame($this->created, $entity->getCreated());
		$this->assertSame($this->user, $entity->getCreatedBy());

		// Default values
		$this->assertSame('', $entity->getParamsRaw());
		$this->assertSame('', $entity->getDbTableName());
		$this->assertSame('', $entity->getTableJoin());
		$this->assertSame('', $entity->getGroupParamsRaw());
		$this->assertSame('', $entity->getAlias());
		$this->assertSame('', $entity->getDefault());
		$this->assertSame(0, $entity->getEval());
		$this->assertTrue($entity->isPublished());
		$this->assertSame(0, $entity->getHidden());

		// Non-constructor defaults
		$this->assertSame(0, $entity->getWidth());
		$this->assertSame(0, $entity->getHeight());
		$this->assertSame(0, $entity->getOrdering());
		$this->assertSame(0, $entity->getShowInListSummary());
		$this->assertSame('', $entity->getFilterType());
		$this->assertSame(0, $entity->getFilterExactMatch());
		$this->assertSame(0, $entity->getLinkToDetail());
		$this->assertSame(0, $entity->getPrimaryKey());
		$this->assertSame(0, $entity->getAutoIncrement());
		$this->assertSame(0, $entity->getAccess());
		$this->assertSame(0, $entity->getUseInPageTitle());
		$this->assertSame(0, $entity->getParentId());
		$this->assertNull($entity->getModifiedBy());
	}

	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$entity = new FabrikElementEntity(
			2, 'el', 5, ElementPluginEnum::TEXTAREA,
			'Label', $this->created, $this->user,
			'{"k":"v"}', 'jos_table', 'join_table',
			'{"g":"p"}', 'my_alias', 'default_val',
			1, false, 1
		);

		$this->assertSame(2, $entity->getId());
		$this->assertSame(ElementPluginEnum::TEXTAREA, $entity->getPlugin());
		$this->assertSame('{"k":"v"}', $entity->getParamsRaw());
		$this->assertSame('jos_table', $entity->getDbTableName());
		$this->assertSame('join_table', $entity->getTableJoin());
		$this->assertSame('{"g":"p"}', $entity->getGroupParamsRaw());
		$this->assertSame('my_alias', $entity->getAlias());
		$this->assertSame('default_val', $entity->getDefault());
		$this->assertSame(1, $entity->getEval());
		$this->assertFalse($entity->isPublished());
		$this->assertSame(1, $entity->getHidden());
	}

	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setId
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setName
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setGroupId
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setPlugin
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setCreated
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setCreatedBy
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setModified
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getModified
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setModifiedBy
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setWidth
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setHeight
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setDefault
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setHidden
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setEval
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setOrdering
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setShowInListSummary
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setFilterType
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setFilterExactMatch
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setPublished
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setLinkToDetail
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setPrimaryKey
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setAutoIncrement
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setAccess
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setUseInPageTitle
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setParentId
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setParamsRaw
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setDbTableName
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setTableJoin
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setGroupParamsRaw
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::setAlias
	 */
	public function testSetters(): void
	{
		$entity = new FabrikElementEntity(
			1, 'name', 10, ElementPluginEnum::FIELD,
			'Label', $this->created, $this->user
		);
		$newUser = $this->createMock(User::class);
		$newDate = new \DateTime('2025-06-01');

		$entity->setId(99);
		$entity->setName('new_name');
		$entity->setGroupId(20);
		$entity->setPlugin(ElementPluginEnum::DATE);
		$entity->setLabel('New Label');
		$entity->setCreated($newDate);
		$entity->setCreatedBy($newUser);
		$entity->setModified($newDate);
		$entity->setModifiedBy($newUser);
		$entity->setWidth(200);
		$entity->setHeight(100);
		$entity->setDefault('def');
		$entity->setHidden(1);
		$entity->setEval(2);
		$entity->setOrdering(5);
		$entity->setShowInListSummary(1);
		$entity->setFilterType('dropdown');
		$entity->setFilterExactMatch(1);
		$entity->setPublished(false);
		$entity->setLinkToDetail(1);
		$entity->setPrimaryKey(1);
		$entity->setAutoIncrement(1);
		$entity->setAccess(2);
		$entity->setUseInPageTitle(1);
		$entity->setParentId(3);
		$entity->setParamsRaw('{"p":"v"}');
		$entity->setDbTableName('new_table');
		$entity->setTableJoin('new_join');
		$entity->setGroupParamsRaw('{"gp":"gv"}');
		$entity->setAlias('new_alias');

		$this->assertSame(99, $entity->getId());
		$this->assertSame('new_name', $entity->getName());
		$this->assertSame(20, $entity->getGroupId());
		$this->assertSame(ElementPluginEnum::DATE, $entity->getPlugin());
		$this->assertSame('New Label', $entity->getLabel());
		$this->assertSame($newDate, $entity->getCreated());
		$this->assertSame($newUser, $entity->getCreatedBy());
		$this->assertSame($newDate, $entity->getModified());
		$this->assertSame($newUser, $entity->getModifiedBy());
		$this->assertSame(200, $entity->getWidth());
		$this->assertSame(100, $entity->getHeight());
		$this->assertSame('def', $entity->getDefault());
		$this->assertSame(1, $entity->getHidden());
		$this->assertSame(2, $entity->getEval());
		$this->assertSame(5, $entity->getOrdering());
		$this->assertSame(1, $entity->getShowInListSummary());
		$this->assertSame('dropdown', $entity->getFilterType());
		$this->assertSame(1, $entity->getFilterExactMatch());
		$this->assertFalse($entity->isPublished());
		$this->assertSame(1, $entity->getLinkToDetail());
		$this->assertSame(1, $entity->getPrimaryKey());
		$this->assertSame(1, $entity->getAutoIncrement());
		$this->assertSame(2, $entity->getAccess());
		$this->assertSame(1, $entity->getUseInPageTitle());
		$this->assertSame(3, $entity->getParentId());
		$this->assertSame('{"p":"v"}', $entity->getParamsRaw());
		$this->assertSame('new_table', $entity->getDbTableName());
		$this->assertSame('new_join', $entity->getTableJoin());
		$this->assertSame('{"gp":"gv"}', $entity->getGroupParamsRaw());
		$this->assertSame('new_alias', $entity->getAlias());
	}

	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getParams
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getParamsArray
	 */
	public function testGetParamsDecodesJson(): void
	{
		$entity = new FabrikElementEntity(
			1, 'name', 10, ElementPluginEnum::FIELD,
			'Label', $this->created, $this->user,
			'{"key":"value","num":42}'
		);

		$paramsObj = $entity->getParams();
		$this->assertSame('value', $paramsObj->key);
		$this->assertSame(42, $paramsObj->num);

		$paramsArr = $entity->getParamsArray();
		$this->assertSame('value', $paramsArr['key']);
		$this->assertSame(42, $paramsArr['num']);
	}

	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getParams
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getParamsArray
	 */
	public function testGetParamsWithEmptyStringReturnsDefaults(): void
	{
		$entity = new FabrikElementEntity(
			1, 'name', 10, ElementPluginEnum::FIELD,
			'Label', $this->created, $this->user
		);

		$this->assertInstanceOf(\stdClass::class, $entity->getParams());
		$this->assertSame([], $entity->getParamsArray());
	}

	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getGroupParams
	 * @covers \Tchooz\Entities\Fabrik\FabrikElementEntity::getGroupParamsArray
	 */
	public function testGetGroupParamsDecodesJson(): void
	{
		$entity = new FabrikElementEntity(
			1, 'name', 10, ElementPluginEnum::FIELD,
			'Label', $this->created, $this->user,
			'', '', '', '{"gkey":"gval"}'
		);

		$gParamsObj = $entity->getGroupParams();
		$this->assertSame('gval', $gParamsObj->gkey);

		$gParamsArr = $entity->getGroupParamsArray();
		$this->assertSame('gval', $gParamsArr['gkey']);
	}
}

