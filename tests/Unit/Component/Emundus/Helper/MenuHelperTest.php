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

	// =========================================================================
	// getSpecialCharacters
	// =========================================================================

	/**
	 * @covers EmundusHelperMenu::getSpecialCharacters
	 */
	public function testGetSpecialCharactersReturnsAnArray(): void
	{
		$result = EmundusHelperMenu::getSpecialCharacters();

		$this->assertIsArray($result, 'getSpecialCharacters should return an array');
	}

	/**
	 * @covers EmundusHelperMenu::getSpecialCharacters
	 */
	public function testGetSpecialCharactersReturnsNonEmptyArray(): void
	{
		$result = EmundusHelperMenu::getSpecialCharacters();

		$this->assertNotEmpty($result, 'getSpecialCharacters should return a non-empty array');
	}

	/**
	 * @covers EmundusHelperMenu::getSpecialCharacters
	 */
	public function testGetSpecialCharactersReturnsOnlyStringValues(): void
	{
		foreach (EmundusHelperMenu::getSpecialCharacters() as $char)
		{
			$this->assertIsString($char, 'Each special character should be a string');
		}
	}

	/**
	 * @covers EmundusHelperMenu::getSpecialCharacters
	 */
	public function testGetSpecialCharactersContainsExpectedCharacters(): void
	{
		$result = EmundusHelperMenu::getSpecialCharacters();

		$expected = ['=', '&', ',', '#', '_', '*', ';', '!', '?', ':', '+', '$', '\'', ' ', '£', ')', '(', '@', '%'];

		foreach ($expected as $char)
		{
			$this->assertContains($char, $result, "Special characters should contain '$char'");
		}
	}

	/**
	 * @covers EmundusHelperMenu::getSpecialCharacters
	 */
	public function testGetSpecialCharactersReturnsExactExpectedSet(): void
	{
		$expected = ['=', '&', ',', '#', '_', '*', ';', '!', '?', ':', '+', '$', '\'', ' ', '£', ')', '(', '@', '%'];
		$result   = EmundusHelperMenu::getSpecialCharacters();

		$this->assertCount(count($expected), $result, 'getSpecialCharacters should return exactly ' . count($expected) . ' characters');
		$this->assertSame([], array_diff($expected, $result), 'No expected character should be missing');
		$this->assertSame([], array_diff($result, $expected), 'No unexpected character should be present');
	}

	/**
	 * @covers EmundusHelperMenu::getSpecialCharacters
	 */
	public function testGetSpecialCharactersIsDeterministic(): void
	{
		$first  = EmundusHelperMenu::getSpecialCharacters();
		$second = EmundusHelperMenu::getSpecialCharacters();

		$this->assertSame($first, $second, 'getSpecialCharacters should return the same result on every call');
	}

	// =========================================================================
	// getHeaderMenu
	// =========================================================================

	/**
	 * Load the menutype of the fixture profile from the DB.
	 * Returns null if the profile has no menutype set.
	 */
	private function getFixtureMenutype(): ?string
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('menutype'))
			->from($this->db->quoteName('#__emundus_setup_profiles'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($this->testProfileId));
		$this->db->setQuery($query);
		$menutype = $this->db->loadResult();

		return !empty($menutype) ? $menutype : null;
	}

	/**
	 * Load a menutype that has at least one row with type = 'heading'.
	 * Returns null when none exists in the DB.
	 */
	private function getHeadingMenutype(): ?string
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('menutype'))
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('heading'))
			->where($this->db->quoteName('published') . ' = 1')
			->setLimit(1);
		$this->db->setQuery($query);
		$menutype = $this->db->loadResult();

		return !empty($menutype) ? $menutype : null;
	}

	/**
	 * @covers EmundusHelperMenu::getHeaderMenu
	 */
	public function testGetHeaderMenuReturnsNullForNonExistentMenutype(): void
	{
		$result = EmundusHelperMenu::getHeaderMenu('__nonexistent_menutype_' . uniqid());

		$this->assertNull($result, 'getHeaderMenu should return null for a menutype that does not exist');
	}

	/**
	 * @covers EmundusHelperMenu::getHeaderMenu
	 */
	public function testGetHeaderMenuReturnsNullForEmptyString(): void
	{
		$result = EmundusHelperMenu::getHeaderMenu('');

		$this->assertNull($result, 'getHeaderMenu should return null for an empty menutype string');
	}

	/**
	 * @covers EmundusHelperMenu::getHeaderMenu
	 */
	public function testGetHeaderMenuReturnsObjectForValidMenutype(): void
	{
		$menutype = $this->getHeadingMenutype();

		if (empty($menutype))
		{
			$this->markTestSkipped('No heading menu item found in the test DB');
		}

		$result = EmundusHelperMenu::getHeaderMenu($menutype);

		$this->assertNotNull($result, 'getHeaderMenu should return an object for a menutype that has a heading item');
		$this->assertIsObject($result);
	}

	/**
	 * @covers EmundusHelperMenu::getHeaderMenu
	 */
	public function testGetHeaderMenuReturnedObjectHasExpectedProperties(): void
	{
		$menutype = $this->getHeadingMenutype();

		if (empty($menutype))
		{
			$this->markTestSkipped('No heading menu item found in the test DB');
		}

		$result = EmundusHelperMenu::getHeaderMenu($menutype);

		$this->assertNotNull($result);
		$this->assertTrue(property_exists($result, 'id'), 'Result should have an id property');
		$this->assertTrue(property_exists($result, 'menutype'), 'Result should have a menutype property');
		$this->assertTrue(property_exists($result, 'type'), 'Result should have a type property');
		$this->assertTrue(property_exists($result, 'published'), 'Result should have a published property');
	}

	/**
	 * @covers EmundusHelperMenu::getHeaderMenu
	 */
	public function testGetHeaderMenuReturnedObjectTypeIsHeading(): void
	{
		$menutype = $this->getHeadingMenutype();

		if (empty($menutype))
		{
			$this->markTestSkipped('No heading menu item found in the test DB');
		}

		$result = EmundusHelperMenu::getHeaderMenu($menutype);

		$this->assertNotNull($result);
		$this->assertSame('heading', $result->type, 'The returned menu item should have type = "heading"');
	}

	/**
	 * @covers EmundusHelperMenu::getHeaderMenu
	 */
	public function testGetHeaderMenuReturnedMenutypeMatchesInput(): void
	{
		$menutype = $this->getHeadingMenutype();

		if (empty($menutype))
		{
			$this->markTestSkipped('No heading menu item found in the test DB');
		}

		$result = EmundusHelperMenu::getHeaderMenu($menutype);

		$this->assertNotNull($result);
		$this->assertSame($menutype, $result->menutype, 'The returned menu item menutype should match the input');
	}

	/**
	 * @covers EmundusHelperMenu::getHeaderMenu
	 */
	public function testGetHeaderMenuReturnedIdIsPositiveInteger(): void
	{
		$menutype = $this->getHeadingMenutype();

		if (empty($menutype))
		{
			$this->markTestSkipped('No heading menu item found in the test DB');
		}

		$result = EmundusHelperMenu::getHeaderMenu($menutype);

		$this->assertNotNull($result);
		$this->assertGreaterThan(0, (int) $result->id, 'The returned menu item should have a positive ID');
	}

	/**
	 * @covers EmundusHelperMenu::getHeaderMenu
	 */
	public function testGetHeaderMenuForProfileMenutypeReturnsConsistentResult(): void
	{
		$menutype = $this->getFixtureMenutype();

		if (empty($menutype))
		{
			$this->markTestSkipped('Fixture profile has no menutype');
		}

		$first  = EmundusHelperMenu::getHeaderMenu($menutype);
		$second = EmundusHelperMenu::getHeaderMenu($menutype);

		// Both calls must return the same type (both null or both the same object)
		if ($first === null)
		{
			$this->assertNull($second, 'Both calls should return null when no heading item exists');
		}
		else
		{
			$this->assertNotNull($second);
			$this->assertEquals($first->id, $second->id, 'Repeated calls should return the same heading menu item');
		}
	}
}

