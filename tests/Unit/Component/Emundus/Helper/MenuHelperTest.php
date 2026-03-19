<?php
/**
 * @package     Unit\Component\Emundus\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Helper;

use EmundusHelperCache;
use EmundusHelperMenu;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_BASE . '/components/com_emundus/helpers/menu.php';
require_once JPATH_BASE . '/components/com_emundus/helpers/cache.php';

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      EmundusHelperMenu
 */
class MenuHelperTest extends UnitTestCase
{
	private Registry $config;

	/**
	 * @var int Profile ID created for tests
	 */
	private int $testProfileId = 0;

	protected function setUp(): void
	{
		parent::setUp();

		$this->config = Factory::getApplication()->getConfig();
		$this->config->set('site_uri', 'https://example.com');
		$this->config->set('cache_handler', 'file');

		// Clean any menu cache before each test
		$hCache = new EmundusHelperCache('com_emundus.menus');
		$hCache->clean();

		// Get existing profile
		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__emundus_setup_profiles'))
			->where($this->db->quoteName('published') . ' = 1')
			->where($this->db->quoteName('status') . ' = 1');
		$this->db->setQuery($query);
		$this->testProfileId = (int) $this->db->loadResult();
	}

	/**
	 * @covers EmundusHelperMenu::buildMenuQuery
	 */
	public function testBuildMenuQueryReturnsFalseOnEmptyProfile(): void
	{
		$this->assertFalse(
			EmundusHelperMenu::buildMenuQuery(0),
			'buildMenuQuery should return false when profile is 0'
		);

		$this->assertFalse(
			EmundusHelperMenu::buildMenuQuery(null),
			'buildMenuQuery should return false when profile is null'
		);

		$this->assertFalse(
			EmundusHelperMenu::buildMenuQuery(''),
			'buildMenuQuery should return false when profile is empty string'
		);
	}

	/**
	 * @covers EmundusHelperMenu::buildMenuQuery
	 */
	public function testBuildMenuQueryReturnsEmptyArrayForNonexistentProfile(): void
	{
		$result = EmundusHelperMenu::buildMenuQuery(999999);
		$this->assertIsArray($result, 'buildMenuQuery should return an array');
		$this->assertEmpty($result, 'buildMenuQuery should return empty array for a profile with no menus');
	}

	/**
	 * @covers EmundusHelperMenu::buildMenuQuery
	 */
	public function testBuildMenuQueryReturnsMenusForValidProfile(): void
	{
		$result = EmundusHelperMenu::buildMenuQuery($this->testProfileId, null, false);
		$this->assertIsArray($result);
		$this->assertNotEmpty($result, 'buildMenuQuery should return menus for a valid profile with menus');

		// Check the returned items have expected properties
		$firstItem = $result[0];
		$this->assertObjectHasProperty('table_id', $firstItem);
		$this->assertObjectHasProperty('form_id', $firstItem);
		$this->assertObjectHasProperty('label', $firstItem);
		$this->assertObjectHasProperty('db_table_name', $firstItem);
		$this->assertObjectHasProperty('link', $firstItem);
		$this->assertObjectHasProperty('id', $firstItem);
		$this->assertObjectHasProperty('title', $firstItem);
		$this->assertObjectHasProperty('access', $firstItem);
		$this->assertObjectHasProperty('menutype', $firstItem);
	}

	/**
	 * @covers EmundusHelperMenu::buildMenuQuery
	 */
	public function testBuildMenuQueryReturnsBothMenus(): void
	{
		$result = EmundusHelperMenu::buildMenuQuery($this->testProfileId, null, false);
		$this->assertIsArray($result);
		$this->assertNotEmpty($result, 'buildMenuQuery should return menus for a valid profile');
	}

	/**
	 * @covers EmundusHelperMenu::buildMenuQuery
	 */
	public function testBuildMenuQueryFormidsEmptyStringIgnored(): void
	{
		$result = EmundusHelperMenu::buildMenuQuery($this->testProfileId, [''], false);
		$this->assertNotEmpty($result, 'buildMenuQuery should return menus for a valid profile');
	}

	/**
	 * @covers EmundusHelperMenu::buildMenuQuery
	 */
	public function testBuildMenuQueryFiltersByAccessLevels(): void
	{
		// With checklevel=true and a valid coordinator user, access=1 (public) should be accessible
		$result = EmundusHelperMenu::buildMenuQuery($this->testProfileId, null, true, $this->dataset['coordinator']);
		$this->assertNotEmpty($result, 'buildMenuQuery with checklevel=true should return menus with appropriate access levels');

		foreach ($result as $item) {
			$this->assertEquals(1, (int) $item->access, 'All returned items should have access level 1 (public)');
		}
	}

	/**
	 * @covers EmundusHelperMenu::buildMenuQuery
	 */
	public function testBuildMenuQueryWithCheckLevelFalseSkipsAccessFilter(): void
	{
		$resultNoCheck = EmundusHelperMenu::buildMenuQuery($this->testProfileId, null, false);
		$resultWithCheck = EmundusHelperMenu::buildMenuQuery($this->testProfileId, null, true, $this->dataset['coordinator']);

		// Since our test menus have access=1 (public), both should return same results
		$this->assertCount(count($resultNoCheck), $resultWithCheck,
			'With public access menus, checklevel should not change the result count');
	}

	/**
	 * @covers EmundusHelperMenu::buildMenuQuery
	 */
	public function testBuildMenuQueryUsesCache(): void
	{
		// First call populates cache
		$this->config->set('caching', 1);
		$result1 = EmundusHelperMenu::buildMenuQuery($this->testProfileId, null, false);
		$this->assertNotEmpty($result1);

		// Second call should use cache and return same data
		$result2 = EmundusHelperMenu::buildMenuQuery($this->testProfileId, null, false);
		$this->assertEquals($result1, $result2, 'Second call should return same data from cache');
	}

	/**
	 * @covers EmundusHelperMenu::buildMenuQuery
	 */
	public function testBuildMenuQueryCacheIsNotAffectedByContextualFilters(): void
	{
		$this->config->set('caching', 1);

		// First call with formids filter
		$filtered = EmundusHelperMenu::buildMenuQuery($this->testProfileId, [999], false);
		$this->assertCount(0, $filtered);

		// Cache should still contain all menus (not filtered)
		$hCache = new EmundusHelperCache('com_emundus.menus');
		$cached = $hCache->get('menus_' . $this->testProfileId);
		$this->assertNotEmpty($cached, 'Cache should contain all menus for the profile');
	}
}

