<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Integrations
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Services\Integrations;

use Joomla\Tests\Unit\UnitTestCase;
use RuntimeException;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Services\Handlers\AbstractHandlerResolver;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use Tchooz\Services\Integrations\IntegrationHandlerResolver;

/**
 * @covers \Tchooz\Services\Integrations\IntegrationHandlerResolver
 */
class IntegrationHandlerResolverTest extends UnitTestCase
{
	private IntegrationHandlerResolver $resolver;

	protected function setUp(): void
	{
		parent::setUp();

		$this->resolver = new IntegrationHandlerResolver();
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationHandlerResolver::__construct
	 */
	public function testInvoke(): void
	{
		$this->assertInstanceOf(AbstractHandlerResolver::class, $this->resolver);
		$this->assertInstanceOf(IntegrationHandlerResolver::class, $this->resolver);
		$this->assertSame($this->resolver->getBasePath(), JPATH_SITE . '/components/com_emundus/classes/Services/Integrations/Handlers/');
		$this->assertSame($this->resolver->getNamespacePrefix(), '\\Tchooz\\Services\\Integrations\\Handlers\\');
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationHandlerResolver::resolve
	 */
	public function testResolveReturnsHandlerInstance(): void
	{
		$synchronizer = $this->createSynchronizer('ovh');

		$handler = $this->resolver->resolve($synchronizer);

		$this->assertInstanceOf(AbstractIntegrationHandler::class, $handler);
		$this->assertSame('Tchooz\\Services\\Integrations\\Handlers\\OvhIntegrationHandler', get_class($handler));
		$this->assertSame($synchronizer, $handler->getSynchronizer());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationHandlerResolver::resolve
	 */
	public function testResolveSupportsUnderscoreTypes(): void
	{
		$synchronizer = $this->createSynchronizer('microsoft_dynamics');

		$handler = $this->resolver->resolve($synchronizer);

		$this->assertInstanceOf(AbstractIntegrationHandler::class, $handler);
		$this->assertSame('Tchooz\\Services\\Integrations\\Handlers\\MicrosoftDynamicsIntegrationHandler', get_class($handler));
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationHandlerResolver::resolve
	 */
	public function testResolveThrowsWhenFileMissing(): void
	{
		$synchronizer = $this->createSynchronizer('missing');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Handler class MissingIntegrationHandler not found');

		$this->resolver->resolve($synchronizer);
	}

	private function createSynchronizer(string $type): SynchronizerEntity
	{
		return new SynchronizerEntity(1, $type, 'Test', 'Test synchronizer');
	}
}