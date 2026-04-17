<?php

namespace Tchooz\Services\ApplicationFile;

use EmundusHelperCache;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Tchooz\Entities\ApplicationFile\Actions\ApplicationFileAction;
use Tchooz\Entities\ApplicationFile\Actions\ApplicationFileActionRedirectToFile;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;
use Tchooz\Factories\ApplicationFile\ApplicationFileActionFactory;

if (!class_exists('EmundusHelperCache'))
{
	require_once JPATH_SITE . '/components/com_emundus/helpers/cache.php';
}

class ApplicationFileActionsRegistry
{
	private const ACTIONS_DIRECTORY = JPATH_ROOT . '/components/com_emundus/classes/Entities/ApplicationFile/Actions';

	private array $actions = [];

	private EmundusHelperCache $cache;

	public function __construct()
	{
		$this->cache = new EmundusHelperCache();
		$this->autoRegisterActions();
	}

	private function autoRegisterActions(): void
	{
		$actions = $this->cache->get('application_file_actions');

		if (empty($actions))
		{
			$files = glob(self::ACTIONS_DIRECTORY . '/*.php');
			if ($files)
			{
				foreach ($files as $file)
				{
					$className = 'Tchooz\\Entities\\ApplicationFile\\Actions\\' . pathinfo($file, PATHINFO_FILENAME);
					$this->register($className);
				}

				$this->cache->set('application_file_actions', $this->actions);
			}
		}
		else
		{
			$this->actions = $actions;
		}
	}

	private function register(string $className): void
	{
		if (class_exists($className))
		{
			$reflection = new \ReflectionClass($className);
			if (!$reflection->isAbstract() && $reflection->isSubclassOf(ApplicationFileAction::class))
			{
				$instance = $reflection->newInstance();
				$this->actions[$instance->getActionType()->value] = $instance;
			}
		}
	}

	public function getAction(string $actionType): ?ApplicationFileAction
	{
		return $this->actions[$actionType] ?? null;
	}

	/**
	 * @return array<string, ApplicationFileAction>
	 */
	public function getActions(): array
	{
		return $this->actions;
	}

	public function getAvailableActions(ApplicationFileEntity $applicationFileEntity, string $context = 'multiple'): array
	{
		$availableActions = [];

		if ($context === 'multiple')
		{
			$availableActions[] = new ApplicationFileActionRedirectToFile();
		}

		$config  = ComponentHelper::getParams('com_emundus');

		$deletionStatus = $config->get('status_for_delete', 0);
		$deletionStatus = explode(',', $deletionStatus);

		foreach ($this->getActions() as $action)
		{
			if ($action->getActionType()->isAvailable())
			{
				$actionEnabled = (bool) $config->get('action_' . $action->getActionType()->value, false);

				if ($actionEnabled)
				{
					if (
						$action->getActionType() === ApplicationFileActionsEnum::DELETE
						&& (in_array(-1, $deletionStatus)
						|| !in_array($applicationFileEntity->getStatus()->getStep(), $deletionStatus))
					)
					{
						continue;
					}

					$availableActions[] = $action;
				}
			}
		}

		usort($availableActions, function (ApplicationFileAction $a, ApplicationFileAction $b) {
			return $a->getActionType()->getOrdering() <=> $b->getActionType()->getOrdering();
		});

		if (!empty($config->get('custom_actions')))
		{
			$customActions = $config->get('custom_actions');

			foreach ($customActions as $id => $customAction)
			{
				$customAction = ApplicationFileActionFactory::customApplicationActionsFromConfig($customAction, $id);
				$targetEntity = new ActionTargetEntity(Factory::getApplication()->getIdentity(), $applicationFileEntity->getFnum());
				if (empty($customAction->getConditionGroup()) || $customAction->getConditionGroup()->isSatisfied($targetEntity))
				{
					$availableActions[] = $customAction;
				}
			}
		}

		return $availableActions;
	}
}