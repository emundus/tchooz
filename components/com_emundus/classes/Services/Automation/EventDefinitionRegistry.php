<?php

namespace Tchooz\Services\Automation;

use EmundusHelperCache;
use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;

class EventDefinitionRegistry
{
	private CONST EVENT_DEFINITIONS_DIRECTORY = JPATH_ROOT . '/components/com_emundus/classes/Entities/Automation/EventsDefinitions';

	private array $eventDefinitions = [];

	private EmundusHelperCache $cache;

	public function __construct()
	{
		$this->cache = new EmundusHelperCache();
		$this->autoRegisterEventDefinitions();
	}

	private function autoRegisterEventDefinitions(): void
	{
		$eventDefinitions = $this->cache->get('automation_event_definitions');

		if (empty($eventDefinitions)) {
			$files = glob(self::EVENT_DEFINITIONS_DIRECTORY . '/*Definition.php');
			if ($files) {
				foreach ($files as $file) {
					if ($file === self::EVENT_DEFINITIONS_DIRECTORY . '/EventDefinition.php') {
						continue; // Skip the base class file
					}

					$className = 'Tchooz\\Entities\\Automation\\EventsDefinitions\\' . pathinfo($file, PATHINFO_FILENAME);
					$this->register($className);
				}

				$this->cache->set('automation_event_definitions', $this->eventDefinitions);
			}
		} else {
			$this->eventDefinitions = $eventDefinitions;
		}
	}

	private function register(string $className): void
	{
		if (class_exists($className)) {
			$reflection = new \ReflectionClass($className);
			if (!$reflection->isAbstract() && $reflection->isSubclassOf('Tchooz\\Entities\\Automation\\EventsDefinitions\\Defaults\\EventDefinition')) {
				$instance = $reflection->newInstance();
				$this->eventDefinitions[$instance->getName()] = $instance;
			}
		}
	}

	public function getEventDefinitionInstance(string $name): ?EventDefinition
	{
		return $this->eventDefinitions[$name] ?? null;
	}

	public function getAvailableEventDefinitionsSchema(): array
	{
		$schema = [];
		foreach ($this->eventDefinitions as $name => $class) {
			assert($class instanceof EventDefinition);

			$schema[] = [
				'name' => $name,
				'parameters' => array_map(function ($field) {
					return $field->toSchema();
				}, $class->getParameters()),
				'supportsTargetPredefinitionsCategories' => $class->supportTargetPredefinitionsCategories(),
			];
		}
		return $schema;
	}
}