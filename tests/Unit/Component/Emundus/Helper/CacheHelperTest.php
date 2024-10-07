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
	/**
	 * @var    EmundusHelperCache
	 * @since  4.2.0
	 */
	private $h_cache;
	
	private $config;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->config = Factory::getConfig();
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

		// cache isEnabled should be false if context is not component and cache is only conservative
		$this->h_cache = new EmundusHelperCache('mod_emundus_testunit', '', 0, 'module');
		$this->assertSame(false, $this->h_cache->isEnabled(), 'When cache is conservative, isEnabled() should return false if context is not component');

		$this->config->set('caching', 2); // cache is now progressive
		$this->h_cache = new EmundusHelperCache('mod_emundus_testunit', '', 0, 'module');
		$this->assertSame(true, $this->h_cache->isEnabled(), 'When cache is progressive, isEnabled() should return true even if context is not component');
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
		$this->assertSame(false, $this->h_cache->get('foo'), 'When cache is enabled, get() should return false if key is not set');

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

		$this->h_cache->clean();
		$this->assertSame(false, $this->h_cache->get('foo'), 'clean() should remove all keys');
	}

	/**
	 * @return void
	 * @covers EmundusHelperCache::getCurrentGitHash
	 */
	public function testGetCurrentGitHash()
	{
		$this->config->set('caching', 1);

		$this->h_cache = new EmundusHelperCache();
		$this->assertNotEmpty($this->h_cache->getCurrentGitHash(), 'When cache is enabled, getCurrentGitHash() should return false if key is not set');
	}
}