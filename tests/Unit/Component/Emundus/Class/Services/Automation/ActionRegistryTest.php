<?php

namespace Unit\Component\Emundus\Class\Services\Automation;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateStatus;
use Tchooz\Services\Automation\ActionRegistry;

class ActionRegistryTest extends UnitTestCase
{

	private ActionRegistry $registry;

	public function setUp(): void
	{
		$this->registry = new ActionRegistry();
		parent::setUp();
	}

	/**
	 * @covers \Tchooz\Services\Automation\ActionRegistry::getAvailableActionsSchema
	 * @return void
	 */
	public function testGetActionsSchema(): void
	{
		$schemas = $this->registry->getAvailableActionsSchema();
		$this->assertIsArray($schemas);
		$this->assertNotEmpty($schemas);

		$found = false;
		foreach ($schemas as $schema) {
			if ($schema['type'] === ActionUpdateStatus::getType()) {
				$found = true;
				$this->assertArrayHasKey('label', $schema);
				$this->assertArrayHasKey('description', $schema);
				$this->assertArrayHasKey('parameters', $schema);
				$this->assertIsArray($schema['parameters']);
				$this->assertNotEmpty($schema['parameters']);
			}
		}

		$this->assertTrue($found, 'Action "update_status" found in registered actions.');
	}
}