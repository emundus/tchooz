<?php

namespace Tchooz\Services\ApplicationFile;

use EmundusHelperCache;
use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Entities\ApplicationFile\Actions\ApplicationFileAction;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;

if (!class_exists('EmundusHelperCache'))
{
	require_once JPATH_SITE . '/components/com_emundus/helpers/cache.php';
}

class ApplicationFileRegistry
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

	public function getAvailableActions(?ApplicationFileEntity $applicationFileEntity = null): array
	{
		$availableActions = [];

		foreach ($this->getActions() as $action)
		{
			$config  = ComponentHelper::getParams('com_emundus');
			$actionEnabled = (bool) $config->get('action_' . $action->getActionType()->value, false);

			if ($actionEnabled)
			{
				$availableActions[] = $action;
			}
		}

		return $availableActions;
	}
}