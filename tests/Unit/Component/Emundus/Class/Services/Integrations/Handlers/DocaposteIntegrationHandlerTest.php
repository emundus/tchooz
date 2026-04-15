<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Integrations\Handlers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Services\Integrations\Handlers;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use Tchooz\Services\Integrations\Handlers\DocaposteIntegrationHandler;

/**
 * @covers \Tchooz\Services\Integrations\Handlers\DocaposteIntegrationHandler
 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler
 */
class DocaposteIntegrationHandlerTest extends UnitTestCase
{
	private DocaposteIntegrationHandler $handler;

	protected function setUp(): void
	{
		parent::setUp();

		$synchronizer  = new SynchronizerEntity(1, 'docaposte', 'Docaposte', 'Docaposte synchronizer');
		// DocaposteIntegrationConfiguration queries the DB for emails; pass null to keep setUp lightweight
		$this->handler = new DocaposteIntegrationHandler($synchronizer, null);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::__construct
	 */
	public function testInvoke(): void
	{
		$this->assertInstanceOf(AbstractIntegrationHandler::class, $this->handler);
		$this->assertInstanceOf(DocaposteIntegrationHandler::class, $this->handler);
		$this->assertSame('docaposte', $this->handler->getSynchronizer()->getType());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\DocaposteIntegrationHandler::getRequiredAddons
	 */
	public function testGetRequiredAddons(): void
	{
		$requiredAddons = $this->handler->getRequiredAddons();
		$this->assertIsArray($requiredAddons);
		$this->assertCount(1, $requiredAddons);
		$this->assertContainsOnlyInstancesOf(AddonEnum::class, $requiredAddons);
		$this->assertSame(AddonEnum::NUMERIC_SIGN, $requiredAddons[0]);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\DocaposteIntegrationHandler::onActivate
	 */
	public function testOnActivateReturnsTrue(): void
	{
		$result = $this->handler->onActivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\DocaposteIntegrationHandler::onDeactivate
	 */
	public function testOnDeactivateReturnsTrue(): void
	{
		$result = $this->handler->onDeactivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\DocaposteIntegrationHandler::onAfterSetup
	 */
	public function testOnAfterSetupReturnsFalseWhenAuthFails(): void
	{
		$setup = (object) [];

		// DocaposteSynchronizer constructor throws when credentials are invalid; onAfterSetup catches it and returns false
		$result = $this->handler->onAfterSetup($setup);

		$this->assertFalse($result);
	}
}
