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
use Tchooz\Services\Integrations\Configurations\DocusignIntegrationConfiguration;
use Tchooz\Services\Integrations\Handlers\DocusignIntegrationHandler;

/**
 * @covers \Tchooz\Services\Integrations\Handlers\DocusignIntegrationHandler
 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler
 */
class DocusignIntegrationHandlerTest extends UnitTestCase
{
	private DocusignIntegrationHandler $handler;

	protected function setUp(): void
	{
		parent::setUp();

		$synchronizer  = new SynchronizerEntity(1, 'docusign', 'DocuSign', 'DocuSign synchronizer');
		$this->handler = new DocusignIntegrationHandler(
			$synchronizer,
			new DocusignIntegrationConfiguration()
		);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::__construct
	 */
	public function testInvoke(): void
	{
		$this->assertInstanceOf(AbstractIntegrationHandler::class, $this->handler);
		$this->assertInstanceOf(DocusignIntegrationHandler::class, $this->handler);
		$this->assertSame('docusign', $this->handler->getSynchronizer()->getType());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::getConfiguration
	 * @covers \Tchooz\Services\Integrations\Configurations\DocusignIntegrationConfiguration::getParameters
	 */
	public function testGetConfiguration(): void
	{
		$configuration = $this->handler->getConfiguration();
		$this->assertInstanceOf(DocusignIntegrationConfiguration::class, $configuration);

		$parameters = $configuration->getParameters();
		$this->assertIsArray($parameters);
		$this->assertNotEmpty($parameters);

		$paramNames = array_map(fn($p) => $p->getName(), $parameters);
		$this->assertContains('user_guid', $paramNames);
		$this->assertContains('integration_key', $paramNames);
		$this->assertContains('secret_key', $paramNames);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\DocusignIntegrationHandler::getRequiredAddons
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
	 * @covers \Tchooz\Services\Integrations\Handlers\DocusignIntegrationHandler::onActivate
	 */
	public function testOnActivateReturnsTrue(): void
	{
		$result = $this->handler->onActivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\DocusignIntegrationHandler::onDeactivate
	 */
	public function testOnDeactivateReturnsTrue(): void
	{
		$result = $this->handler->onDeactivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\DocusignIntegrationHandler::onAfterSetup
	 */
	public function testOnAfterSetupReturnsFalseWhenAuthFails(): void
	{
		$setup = (object) [];

		// DocuSignSynchronizer throws when credentials are invalid, so onAfterSetup returns false
		$result = $this->handler->onAfterSetup($setup);

		$this->assertFalse($result);
	}
}
