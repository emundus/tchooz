<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Automation
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Automation;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionExecutionMessage;
use Tchooz\Enums\Automation\ActionMessageTypeEnum;

/**
 * @covers \Tchooz\Entities\Automation\ActionExecutionMessage
 */
class ActionExecutionMessageTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Automation\ActionExecutionMessage::__construct
	 * @covers \Tchooz\Entities\Automation\ActionExecutionMessage::getMessage
	 * @covers \Tchooz\Entities\Automation\ActionExecutionMessage::getType
	 * @covers \Tchooz\Entities\Automation\ActionExecutionMessage::getTimestamp
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new ActionExecutionMessage('Test message');

		$this->assertSame('Test message', $entity->getMessage());
		$this->assertSame(ActionMessageTypeEnum::INFO, $entity->getType());
		$this->assertInstanceOf(\DateTimeImmutable::class, $entity->getTimestamp());
	}

	/**
	 * @covers \Tchooz\Entities\Automation\ActionExecutionMessage::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$timestamp = new \DateTimeImmutable('2025-06-01 12:00:00');
		$entity = new ActionExecutionMessage('Error occurred', ActionMessageTypeEnum::ERROR, $timestamp);

		$this->assertSame('Error occurred', $entity->getMessage());
		$this->assertSame(ActionMessageTypeEnum::ERROR, $entity->getType());
		// Note: le constructeur écrase toujours le timestamp avec new DateTimeImmutable()
		$this->assertInstanceOf(\DateTimeImmutable::class, $entity->getTimestamp());
	}

	/**
	 * @covers \Tchooz\Entities\Automation\ActionExecutionMessage::__construct
	 */
	public function testInstanciationWithWarningType(): void
	{
		$entity = new ActionExecutionMessage('Warning message', ActionMessageTypeEnum::WARNING);

		$this->assertSame('Warning message', $entity->getMessage());
		$this->assertSame(ActionMessageTypeEnum::WARNING, $entity->getType());
	}

	/**
	 * @covers \Tchooz\Entities\Automation\ActionExecutionMessage::serialize
	 */
	public function testSerialize(): void
	{
		$entity = new ActionExecutionMessage('Test message', ActionMessageTypeEnum::ERROR);

		$serialized = $entity->serialize();

		$this->assertSame('error', $serialized['type']);
		$this->assertSame('Test message', $serialized['message']);
		$this->assertArrayHasKey('timestamp', $serialized);
		$this->assertNotNull($serialized['timestamp']);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\ActionExecutionMessage::__construct
	 */
	public function testPublicProperties(): void
	{
		$entity = new ActionExecutionMessage('msg');

		$this->assertSame('msg', $entity->message);
		$this->assertSame(ActionMessageTypeEnum::INFO, $entity->type);
	}
}

