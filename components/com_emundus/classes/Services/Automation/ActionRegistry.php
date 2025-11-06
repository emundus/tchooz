<?php

namespace Tchooz\Services\Automation;

use EmundusHelperCache;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Repositories\Payment\PaymentRepository;

class ActionRegistry
{
	private CONST ACTIONS_DIRECTORY = JPATH_ROOT . '/components/com_emundus/classes/Entities/Automation/Actions';

	private array $actions = [];

	private EmundusHelperCache $cache;

	public function __construct()
	{
		$this->cache = new EmundusHelperCache();
		$this->autoRegisterActions();
	}


	private function autoRegisterActions(): void
	{
		$actions = $this->cache->get('automation_actions');

		if (empty($actions)) {
			$files = glob(self::ACTIONS_DIRECTORY . '/Action*.php');
			if ($files) {
				foreach ($files as $file) {
					$className = 'Tchooz\\Entities\\Automation\\Actions\\' . pathinfo($file, PATHINFO_FILENAME);
					$this->register($className);
				}

				$this->cache->set('automation_actions', $this->actions);
			}
		} else {
			$this->actions = $actions;
		}
	}

	private function register(string $className): void
	{
		if (class_exists($className)) {
			$reflection = new \ReflectionClass($className);
			if (!$reflection->isAbstract() && $reflection->isSubclassOf(ActionEntity::class)) {
				$instance = $reflection->newInstance();
				$this->actions[$instance->getType()] = $instance;
			}
		}
	}

	public function getActionInstance(string $type, array $parameterValues = []): ?ActionEntity
	{
		if (isset($this->actions[$type])) {
			$className = get_class($this->actions[$type]);
			return new $className($parameterValues);
		}
		return null;
	}

	public function getAvailableActionsSchema(): array
	{
		$schema = [];
		foreach ($this->actions as $type => $class) {
			assert($class instanceof ActionEntity);


			switch($class::getCategory())
			{
				case ActionCategoryEnum::CART:
					// check if payment is enabled
					$paymentRepository = new PaymentRepository();
					if (!$paymentRepository->isActivated())
					{
						continue 2; // skip this action
					}
					break;
				default:
					break;
			}

			$schema[] = [
				'type' => $type,
				'label' => $class::getLabel(),
				'description' => $class::getDescription(),
				'category' => [
					'value' => $class::getCategory()?->value,
					'label' => $class::getCategory()?->getLabel(),
					'icon' => $class::getCategory()?->getIcon(),
				],
				'icon' => $class::getIcon(),
				'parameters' => $class->getParametersSchema(),
				'supported_target_types' => $class::supportTargetTypes(),
			];
		}
		return $schema;
	}

	public function getAvailableActions(): array
	{
		return $this->actions;
	}
}