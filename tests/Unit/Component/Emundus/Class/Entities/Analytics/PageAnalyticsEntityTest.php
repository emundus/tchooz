<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Analytics
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Analytics;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Analytics\PageAnalyticsEntity;

/**
 * @covers \Tchooz\Entities\Analytics\PageAnalyticsEntity
 */
class PageAnalyticsEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Analytics\PageAnalyticsEntity::__construct
	 * @covers \Tchooz\Entities\Analytics\PageAnalyticsEntity::getId
	 * @covers \Tchooz\Entities\Analytics\PageAnalyticsEntity::getDate
	 * @covers \Tchooz\Entities\Analytics\PageAnalyticsEntity::getCount
	 * @covers \Tchooz\Entities\Analytics\PageAnalyticsEntity::getLink
	 */
	public function testInstanciation(): void
	{
		$date = new \DateTime('2025-01-15 10:00:00');
		$entity = new PageAnalyticsEntity(1, $date, 42, '/index.php');

		$this->assertSame(1, $entity->getId());
		$this->assertSame($date, $entity->getDate());
		$this->assertSame(42, $entity->getCount());
		$this->assertSame('/index.php', $entity->getLink());
	}

	/**
	 * @covers \Tchooz\Entities\Analytics\PageAnalyticsEntity::setId
	 * @covers \Tchooz\Entities\Analytics\PageAnalyticsEntity::setDate
	 * @covers \Tchooz\Entities\Analytics\PageAnalyticsEntity::setCount
	 * @covers \Tchooz\Entities\Analytics\PageAnalyticsEntity::setLink
	 */
	public function testSetters(): void
	{
		$date = new \DateTime('2025-01-15 10:00:00');
		$entity = new PageAnalyticsEntity(1, $date, 42, '/index.php');

		$newDate = new \DateTime('2025-06-01 12:00:00');
		$entity->setId(99);
		$entity->setDate($newDate);
		$entity->setCount(100);
		$entity->setLink('/admin.php');

		$this->assertSame(99, $entity->getId());
		$this->assertSame($newDate, $entity->getDate());
		$this->assertSame(100, $entity->getCount());
		$this->assertSame('/admin.php', $entity->getLink());
	}
}

