<?php

namespace Tchooz\Services\Automation;

use EmundusHelperCache;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\TargetPredefinitionEntity;

class TargetPredefinitionRegistry
{
	private const TARGET_PREDEFINITIONS_DIRECTORY = JPATH_ROOT . '/components/com_emundus/classes/Entities/Automation/TargetPredefinitions';

	private array $targetPredefinitions = [];

	private EmundusHelperCache $cache;

	public function __construct()
	{
		$this->cache = new EmundusHelperCache();
		$this->autoRegisterTargetPredefinitions();
	}

	private function autoRegisterTargetPredefinitions(): void
	{
		$targetPredefinitions = $this->cache->get('automation_target_predefinitions');

		if (empty($targetPredefinitions))
		{
			$files = glob(self::TARGET_PREDEFINITIONS_DIRECTORY . '/*Predefinition.php');
			if ($files) {
				foreach ($files as $file) {
					$className = 'Tchooz\\Entities\\Automation\\TargetPredefinitions\\' . pathinfo($file, PATHINFO_FILENAME);
					$this->register($className);
				}
			}

			$this->cache->set('automation_target_predefinitions', $this->targetPredefinitions);
		} else {
			$this->targetPredefinitions = $targetPredefinitions;
		}
	}

	private function register(string $className): void
	{
		if (class_exists($className)) {
			$reflection = new \ReflectionClass($className);
			if (!$reflection->isAbstract() && $reflection->isSubclassOf(TargetPredefinitionEntity::class)) {
				$instance = $reflection->newInstance();
				$this->targetPredefinitions[$instance->getName()] = $instance;
			}
		}
	}

	public function getTargetPredefinitionInstance(string $name): ?TargetPredefinitionEntity
	{
		return $this->targetPredefinitions[$name] ?? null;
	}

	public function getAvailableTargetPredefinitionsSchema(): array
	{
		$schema = [];
		foreach ($this->targetPredefinitions as $name => $class) {
			assert($class instanceof TargetPredefinitionEntity);
			$schema[] = [
				'name' => $name,
				'label' => Text::_($class->getLabel()),
				'category' => $class->getCategory()->value,
				'fromCategories' => array_map(fn($cat) => $cat->value, $class->getFromCategories())
			];
		}
		return $schema;
	}
}