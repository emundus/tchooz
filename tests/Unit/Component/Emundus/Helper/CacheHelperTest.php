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
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_BASE . '/components/com_emundus/helpers/cache.php';

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      EmundusHelperCache
 */
class CacheHelperTest extends UnitTestCase
{
	private EmundusHelperCache $h_cache;
	
	private Registry $config;

	protected function setUp(): void
	{
		parent::setUp();

		$this->config = Factory::getApplication()->getConfig();
		$this->config->set('site_uri', 'https://example.com');
		$this->config->set('cache_handler', 'file');
	}

	/**
	 * @return void
	 * @covers EmundusHelperCache::__construct
	 */
	public function testConstruct()
	{
		$this->config->set('caching', 0);

		$this->h_cache = new EmundusHelperCache();
		$this->assertSame(false, $this->h_cache->isEnabled(), 'When cache is disabled, isEnabled() should return false');

		$this->config->set('caching', 1);
		$this->h_cache = new EmundusHelperCache();
		$this->assertSame(true, $this->h_cache->isEnabled(), 'When cache is enabled, isEnabled() should return true');

		$this->config->set('caching', 2); // cache is now progressive
		$this->h_cache = new EmundusHelperCache('mod_emundus_testunit');
		$this->assertSame(true, $this->h_cache->isEnabled(), 'When cache is progressive, isEnabled() should return true');
	}

	/**
	 * @return void
	 * @covers EmundusHelperCache::get
	 */
	public function testGetter()
	{
		$this->config->set('caching', 0);

		$this->h_cache = new EmundusHelperCache();
		$this->assertSame(null, $this->h_cache->get('foo'), 'When cache is disabled, get() should return null');

		$this->config->set('caching', 1);
		$this->h_cache = new EmundusHelperCache();
		$this->h_cache->clean();
		$this->assertSame(null, $this->h_cache->get('foo'), 'When cache is enabled, get() should return null if key is not set');

		$this->h_cache->set('foo', 'bar');
		$this->assertSame('bar', $this->h_cache->get('foo'), 'When cache is enabled, get() should return value if key is set');
	}

	/**
	 * @return void
	 * @covers EmundusHelperCache::set
	 */
	public function testSetter()
	{
		$this->config->set('caching', 0);

		$this->h_cache = new EmundusHelperCache();
		$this->assertSame(false, $this->h_cache->set('foo', 'bar'), 'When cache is disabled, set() should return false');

		$this->config->set('caching', 1);
		$this->h_cache = new EmundusHelperCache();
		$this->assertSame(true, $this->h_cache->set('foo', 'bar'), 'When cache is enabled, set() should return true');
		$this->assertSame('bar', $this->h_cache->get('foo'), 'When cache is enabled, set() should set value for key');
	}

	/**
	 * @return void
	 * @covers EmundusHelperCache::clean
	 */
	public function testClean()
	{
		$this->config->set('caching', 1);

		$this->h_cache = new EmundusHelperCache();
		$this->h_cache->set('foo', 'bar');
		$this->assertSame('bar', $this->h_cache->get('foo'));

		$cleaned = $this->h_cache->clean();
		$this->assertSame(true, $cleaned, 'clean() should return true on success');
		$this->assertSame(null, $this->h_cache->get('foo'), 'clean() should remove all keys');
	}
}