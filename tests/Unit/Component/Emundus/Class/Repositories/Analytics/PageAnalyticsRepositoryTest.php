<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Analytics;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Analytics\PageAnalyticsEntity;
use Tchooz\Repositories\Analytics\PageAnalyticsRepository;
use Tchooz\Repositories\NumericSign\RequestRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\NumericSign\Request
 */
class PageAnalyticsRepositoryTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();

		$this->model = new PageAnalyticsRepository();
	}

	private function createFixtures()
	{
		// Create 1000 page analytics entries for testing on different dates and links
		for ($i = 1; $i <= 1000; $i++)
		{
			$date = new \DateTime();
			$date->modify('-' . rand(0, 365) . ' days');
			$link = 'http://example.com/page' . rand(1, 50);
			$count = rand(1, 100);

			$entity = new PageAnalyticsEntity(0, $date, $count, $link);
			$this->model->flush($entity);
		}
	}

	private function clearFixtures()
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__emundus_page_analytics'));
		$this->db->setQuery($query);
		$this->db->execute();
	}

	public function testGet()
	{
		$this->createFixtures();

		$page_analytics = $this->model->get();
		$total = $page_analytics->getCount();
		$this->assertNotEmpty($total);

		// Filter by date range
		$startDate = new \DateTime();
		$startDate->modify('-90 days');
		$endDate = new \DateTime();
		$page_analytics = $this->model->get(0, '', null, $startDate, $endDate);
		$this->assertNotEmpty($page_analytics->getCount());
		$this->assertLessThanOrEqual($total, $page_analytics->getCount());

		$this->clearFixtures();
	}

	public function testFlush()
	{
		$entity = new PageAnalyticsEntity(0, new \DateTime(), 10, 'http://example.com/test');
		$result = $this->model->flush($entity);
		$this->assertTrue($result);

		// Update the same entity
		$entity->setCount(20);
		$result = $this->model->flush($entity);
		$this->assertTrue($result);
	}
}