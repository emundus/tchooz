<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Handlers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Services\Handlers;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Services\Handlers\AbstractHandlerResolver;

/**
 * @covers \Tchooz\Services\Handlers\AbstractHandlerResolver
 */
class AbstractHandlerResolverTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Services\Handlers\AbstractHandlerResolver::__construct
	 * @covers \Tchooz\Services\Handlers\AbstractHandlerResolver::getBasePath
	 * @covers \Tchooz\Services\Handlers\AbstractHandlerResolver::getNamespacePrefix
	 */
	public function testInvoke(): void
	{
		$resolver = new AbstractHandlerResolver();

		$this->assertInstanceOf(AbstractHandlerResolver::class, $resolver);
		$this->assertSame($resolver->getBasePath(), '');
		$this->assertSame($resolver->getNamespacePrefix(), null);

		$resolver = new AbstractHandlerResolver('path', 'prefix');
		$this->assertInstanceOf(AbstractHandlerResolver::class, $resolver);
		$this->assertSame($resolver->getBasePath(), 'path');
		$this->assertSame($resolver->getNamespacePrefix(), 'prefix');
	}
}