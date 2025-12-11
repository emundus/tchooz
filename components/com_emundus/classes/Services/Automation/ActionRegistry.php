<?php

namespace Tchooz\Services\Automation;

use EmundusHelperCache;
use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Synchronizer\SynchronizerContextEnum;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

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

			if(!$class->isAvailable())
			{
				continue; // skip this action
			}

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
				case ActionCategoryEnum::SIGN:
					$synchronizerRepository = new SynchronizerRepository();

					$synchronizers = $synchronizerRepository->getBy('context', SynchronizerContextEnum::NUMERIC_SIGN->value);
					$atLeastOneActive = false;
					foreach ($synchronizers as $synchronizer)
					{
						assert($synchronizer instanceof SynchronizerEntity);
						if ($synchronizer->isPublished() && $synchronizer->isEnabled())
						{
							$atLeastOneActive = true;
							break;
						}
					}

					if (!$atLeastOneActive)
					{
						continue 2; // skip this action
					}

					break;
				default:
					break;
			}

			switch ($type) {
				case 'print_application':
					$eMConfig              = ComponentHelper::getParams('com_emundus');
					$export_pdf              = $eMConfig->get('export_application_pdf', 0);
					if($export_pdf == 0)
					{
						continue 2; // skip this action
					}
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