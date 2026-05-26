<?php

namespace Tchooz\Services\ApplicationFile;

use EmundusHelperCache;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\Actions\ApplicationFileAction;
use Tchooz\Entities\ApplicationFile\Actions\ApplicationFileActionCreateTab;
use Tchooz\Entities\ApplicationFile\Actions\ApplicationFileActionMoveToTab;
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

		Log::addLogger(['text_file' => 'com_emundus.registry.custom_actions.php'], Log::ALL, ['com_emundus.registry.custom_actions']);
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

	public function getConfiguredActions(): array
	{
		$configuredActions = [];
		foreach ($this->getActions() as $action)
		{
			if ($action->getActionType()->isAvailable())
			{
				$configuredActions[] = $action;
			}
		}

		$config  = ComponentHelper::getParams('com_emundus');
		$customActions = $config->get('custom_actions', '');
		if (!empty($customActions))
		{
			foreach ($customActions as $id => $customAction)
			{
				$configuredActions[] = ApplicationFileActionFactory::customApplicationActionsFromConfig($customAction, $id);
			}
		}

		return $configuredActions;
	}

	public function getAvailableActions(ApplicationFileEntity $applicationFileEntity, string $context = 'multiple', ?User $currentUser = null): array
	{
		$availableActions = [];

		if ($context === 'multiple')
		{
			$availableActions[] = new ApplicationFileActionRedirectToFile();

			if (ApplicationFileActionsEnum::MOVE_TO_TAB->isAvailable())
			{
				$availableActions[] = new ApplicationFileActionMoveToTab();
			}
			else
			{
				$availableActions[] = new ApplicationFileActionCreateTab();
			}
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

		$customActions = $config->get('custom_actions', '');
		if (!empty($customActions))
		{
			if (empty($currentUser))
			{
				$currentUser = Factory::getApplication()->getIdentity();
			}

			foreach ($customActions as $id => $customAction)
			{
				try
				{
					$customAction = ApplicationFileActionFactory::customApplicationActionsFromConfig($customAction, $id);
					$targetEntity = new ActionTargetEntity($currentUser, $applicationFileEntity->getFnum());

					if (empty($customAction->getConditionGroup()) || $customAction->getConditionGroup()->isSatisfied($targetEntity))
					{
						$availableActions[] = $customAction;
					}
				}
				catch (\Exception $e)
				{
					Log::add('Error while loading custom action: ' . $e->getMessage(), 'error', 'com_emundus.registry.custom_actions');
				}
			}
		}

		return $availableActions;
	}
}