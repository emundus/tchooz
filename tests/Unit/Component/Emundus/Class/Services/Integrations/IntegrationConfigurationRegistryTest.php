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
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;
use Tchooz\Services\Integrations\IntegrationConfigurationRegistry;

/**
 * @covers \Tchooz\Services\Integrations\IntegrationConfigurationRegistry
 */
class IntegrationConfigurationRegistryTest extends UnitTestCase
{
	private IntegrationConfigurationRegistry $registry;

	protected function setUp(): void
	{
		parent::setUp();

		$this->registry = new IntegrationConfigurationRegistry();
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationConfigurationRegistry::__construct
	 * @covers \Tchooz\Services\Integrations\IntegrationConfigurationRegistry::getConfigurations
	 * @covers \Tchooz\Services\Integrations\IntegrationConfigurationRegistry::registerConfiguration
	 */
	public function testInvoke(): void
	{
		$configurations = $this->registry->getConfigurations();
		$this->assertNotEmpty($configurations);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationConfigurationRegistry::getConfiguration
	 */
	public function testGetConfiguration(): void
	{
		$docaposteConfiguration = $this->registry->getConfiguration('docaposte');
		$this->assertNotEmpty($docaposteConfiguration);
		$this->assertInstanceOf(EmundusIntegrationConfiguration::class, $docaposteConfiguration);
	}
}