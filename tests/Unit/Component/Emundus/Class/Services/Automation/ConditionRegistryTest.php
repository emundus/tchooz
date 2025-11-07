<?php

namespace Unit\Component\Emundus\Class\Services\Automation;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Services\Automation\Condition\CampaignDataConditionResolver;
use Tchooz\Services\Automation\Condition\FormDataConditionResolver;
use Tchooz\Services\Automation\ConditionRegistry;

class ConditionRegistryTest extends UnitTestCase
{

	private ConditionRegistry $registry;

	public function setUp(): void
	{
		$this->registry = new ConditionRegistry();
		parent::setUp();
	}

	/**
	 * @covers \Tchooz\Services\Automation\ConditionRegistry::getAvailableConditionSchemas
	 * @return void
	 */
	public function testgetAvailableConditionSchemas(): void
	{
		$schemas = $this->registry->getAvailableConditionSchemas();
		$this->assertIsArray($schemas);
		$this->assertNotEmpty($schemas);

		$found = false;
		foreach ($schemas as $schema) {
			if ($schema['targetType'] === FormDataConditionResolver::getTargetType()) {
				$found = true;
				$this->assertArrayHasKey('fields', $schema);
				$this->assertIsArray($schema['fields']);
			}

			if ($schema['targetType'] === CampaignDataConditionResolver::getTargetType()) {
				$this->assertArrayHasKey('fields', $schema);
				$this->assertIsArray($schema['fields']);
				$this->assertNotEmpty($schema['fields']);
			}

		}

		$this->assertTrue($found, 'Resolver for formData found in registered actions.');
	}
}