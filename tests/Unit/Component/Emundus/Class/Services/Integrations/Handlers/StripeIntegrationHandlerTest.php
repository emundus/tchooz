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
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use Tchooz\Services\Integrations\Handlers\StripeIntegrationHandler;

/**
 * @covers \Tchooz\Services\Integrations\Handlers\StripeIntegrationHandler
 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler
 */
class StripeIntegrationHandlerTest extends UnitTestCase
{
	private StripeIntegrationHandler $handler;

	protected function setUp(): void
	{
		parent::setUp();

		// Stripe has no configuration class; pass null
		$synchronizer  = new SynchronizerEntity(1, 'stripe', 'Stripe', 'Stripe synchronizer');
		$this->handler = $this->getMockBuilder(StripeIntegrationHandler::class)
			->setConstructorArgs([$synchronizer, null])
			->onlyMethods(['encrypt'])
			->getMock();
		$this->handler->method('encrypt')->willReturnCallback(static fn(string $v): string => 'encrypted_' . $v);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::__construct
	 */
	public function testInvoke(): void
	{
		$this->assertInstanceOf(AbstractIntegrationHandler::class, $this->handler);
		$this->assertInstanceOf(StripeIntegrationHandler::class, $this->handler);
		$this->assertSame('stripe', $this->handler->getSynchronizer()->getType());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\StripeIntegrationHandler::getRequiredAddons
	 */
	public function testGetRequiredAddons(): void
	{
		$requiredAddons = $this->handler->getRequiredAddons();
		$this->assertIsArray($requiredAddons);
		$this->assertCount(1, $requiredAddons);
		$this->assertContainsOnlyInstancesOf(AddonEnum::class, $requiredAddons);
		$this->assertSame(AddonEnum::PAYMENT, $requiredAddons[0]);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\StripeIntegrationHandler::onActivate
	 */
	public function testOnActivateReturnsTrue(): void
	{
		$result = $this->handler->onActivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\StripeIntegrationHandler::onDeactivate
	 */
	public function testOnDeactivateReturnsTrue(): void
	{
		$result = $this->handler->onDeactivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\StripeIntegrationHandler::onSetup
	 */
	public function testOnSetupCreatesConfigWhenEmpty(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$plainClientSecret  = 'stripe_client_secret';
		$plainWebhookSecret = 'stripe_webhook_secret';
		$setup              = (object) [
			'authentication' => (object) [
				'client_secret'  => $plainClientSecret,
				'webhook_secret' => $plainWebhookSecret,
			],
		];

		$result = $this->handler->onSetup($setup, $repositoryMock);

		$this->assertTrue($result);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertArrayHasKey('authentication', $config);
		$this->assertArrayHasKey('client_secret', $config['authentication']);
		$this->assertArrayHasKey('webhook_secret', $config['authentication']);

		// Secrets must be encrypted, not plain text
		$this->assertNotEmpty($config['authentication']['client_secret']);
		$this->assertNotSame($plainClientSecret, $config['authentication']['client_secret']);
		$this->assertNotEmpty($config['authentication']['webhook_secret']);
		$this->assertNotSame($plainWebhookSecret, $config['authentication']['webhook_secret']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\StripeIntegrationHandler::onSetup
	 */
	public function testOnSetupUpdatesExistingConfig(): void
	{
		$existingConfig = [
			'authentication' => [
				'client_secret'  => 'old_encrypted_client_secret',
				'webhook_secret' => 'old_encrypted_webhook_secret',
			],
		];
		$synchronizer   = new SynchronizerEntity(1, 'stripe', 'Stripe', 'Stripe sync', [], $existingConfig);
		$handler        = $this->getMockBuilder(StripeIntegrationHandler::class)
			->setConstructorArgs([$synchronizer, null])
			->onlyMethods(['encrypt'])
			->getMock();
		$handler->method('encrypt')->willReturnCallback(static fn(string $v): string => 'encrypted_' . $v);

		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$newClientSecret  = 'new_stripe_client_secret';
		$newWebhookSecret = 'new_stripe_webhook_secret';
		$setup            = (object) [
			'authentication' => (object) [
				'client_secret'  => $newClientSecret,
				'webhook_secret' => $newWebhookSecret,
			],
		];

		$handler->onSetup($setup, $repositoryMock);

		$config = $handler->getSynchronizer()->getConfig();
		// Secrets are re-encrypted; they differ from plain text and from old encrypted values
		$this->assertNotSame($newClientSecret, $config['authentication']['client_secret']);
		$this->assertNotSame('old_encrypted_client_secret', $config['authentication']['client_secret']);
		$this->assertNotSame($newWebhookSecret, $config['authentication']['webhook_secret']);
		$this->assertNotSame('old_encrypted_webhook_secret', $config['authentication']['webhook_secret']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\StripeIntegrationHandler::onSetup
	 */
	public function testOnSetupSetsEmptySecretsWhenNotProvided(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		// Setup with no secrets provided
		$setup = (object) ['authentication' => (object) []];

		$this->handler->onSetup($setup, $repositoryMock);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertArrayHasKey('authentication', $config);
		$this->assertSame('', $config['authentication']['client_secret']);
		$this->assertSame('', $config['authentication']['webhook_secret']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\StripeIntegrationHandler::onSetup
	 */
	public function testOnSetupEnablesSync(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$setup = (object) [
			'authentication' => (object) [
				'client_secret'  => 'some_secret',
				'webhook_secret' => 'some_webhook',
			],
		];

		$this->assertFalse($this->handler->getSynchronizer()->isEnabled());

		$this->handler->onSetup($setup, $repositoryMock);

		$this->assertTrue($this->handler->getSynchronizer()->isEnabled());
	}
}
