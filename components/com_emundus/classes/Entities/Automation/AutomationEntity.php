<?php

namespace Tchooz\Entities\Automation;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Joomla\CMS\Log\Log;
use Tchooz\Repositories\Task\TaskRepository;
use Tchooz\Traits\TraitAutomatedTask;

class AutomationEntity
{
	use TraitAutomatedTask;

	private int $id;

	private string $name = '';

	private ?string $description = '';

	private ?EventEntity $event;

	private bool $published = true;

	/**
	 * @var array <ConditionGroupEntity>
	 */
	private array $conditionsGroups = [];

	/**
	 * @var array <ActionEntity>
	 */
	private array $actions = [];

	public function __construct(int $id = 0, $name = '', $description = '', ?EventEntity $event = null, array $conditionsGroups = [], array $actions = [], bool $published = true)
	{
		$this->id = $id;
		$this->name = $name;
		$this->description = $description;
		$this->event = $event;
		$this->published = $published;

		foreach ($conditionsGroups as $conditionGroup) {
			assert($conditionGroup instanceof ConditionGroupEntity);
			$this->conditionsGroups[] = $conditionGroup;
		}

		foreach ($actions as $action)
		{
			assert($action instanceof ActionEntity);
			$this->actions[] = $action;
		}
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function getEvent(): ?EventEntity
	{
		return $this->event;
	}

	public function setEvent(EventEntity $event): void
	{
		$this->event = $event;
	}

	public function setConditionsGroups(array $conditionsGroups): void
	{
		$this->conditionsGroups = [];

		foreach ($conditionsGroups as $conditionGroup) {
			assert($conditionGroup instanceof ConditionGroupEntity);
			$this->conditionsGroups[] = $conditionGroup;
		}
	}

	public function addConditionGroup(ConditionGroupEntity $conditionGroup): void
	{
		$this->conditionsGroups[] = $conditionGroup;
	}

	public function removeConditionsGroups(): void
	{
		$this->conditionsGroups = [];
	}

	public function getConditionsGroups(): array
	{
		return $this->conditionsGroups;
	}

	public function setActions(array $actions): void
	{
		$this->actions = [];

		foreach ($actions as $action)
		{
			assert($action instanceof ActionEntity);
			$this->actions[] = $action;
		}
	}

	public function addAction(ActionEntity $action): void
	{
		$this->actions[] = $action;
	}

	public function getActions(): array
	{
		return $this->actions;
	}

	public function removeActions(): void
	{
		$this->actions = [];
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): void
	{
		$this->published = $published;
	}

	/**
	 * Process the automation for the given event context.
	 *
	 * @param EventContextEntity $context The context of the event triggering the automation.
	 * @return bool True if all actions were executed successfully for all files, false otherwise.
	 * @throws \Exception If there are no actions to execute.
	 */
	public function process(EventContextEntity $context, ?AutomationExecutionContext $executionContext = null): bool
	{
		Log::addLogger(['text_file' => 'com_emundus.automation.php'], Log::ALL, ['com_emundus.automation']);

		if ($executionContext !== null && $executionContext->hasRun($this->getId())) {
			Log::add('Automation [' . $this->getId() . '] has already been executed in this context. Skipping.', Log::DEBUG, 'com_emundus.automation');
			return true;
		}

		if (empty($this->getActions()))
		{
			Log::add('Automation [' . $this->getId() . '] has no actions to execute.', Log::ERROR, 'com_emundus.automation');
			throw new \Exception('Automation [' . $this->getId() . '] has no actions to execute.');
		}

		Log::add('Processing automation [' . $this->getId() . '] for event context with ' . count($context->getFiles()) . ' files.', Log::DEBUG, 'com_emundus.automation');

		$successActions = [];
		$failedActions = [];
		$nbFilesPassed = 0;

		$taskRepository = new TaskRepository();
		foreach ($this->getIterationContexts($context) as $subContext)
		{
			$iterationContextIdentity = !empty($subContext->getFile()) ? $subContext->getFile() : (!empty($subContext->getUserId()) ? $subContext->getUserId() : $subContext->getTriggeredBy()->id);

			$parentConditionGroups = array_filter($this->conditionsGroups, fn($g) => $g->getParentId() === 0);
			foreach ($parentConditionGroups as $group) {
				assert($group instanceof ConditionGroupEntity);
				$subGroups = array_filter($this->conditionsGroups, fn($g) => $g->getParentId() === $group->getId());

				try
				{
					if (!$group->isSatisfied($subContext, $subGroups)) {
						Log::add('Conditions not satisfied for : ' . $iterationContextIdentity . ' in automation [' . $this->getId() . ']. Skipping actions.', Log::DEBUG, 'com_emundus.automation');
						continue 2; // Skip to the next file
					}
				} catch (\Exception $e) {
					Log::add('Error evaluating conditions for : ' . $iterationContextIdentity . ' in automation [' . $this->getId() . ']: ' . $e->getMessage(), Log::ERROR, 'com_emundus.automation');
					continue 2; // Skip to the next file
				}
			}

			$nbFilesPassed++;
			Log::add('All conditions satisfied for : ' . $iterationContextIdentity . ' in automation [' . $this->getId() . ']. Executing actions.', Log::DEBUG, 'com_emundus.automation');
			foreach ($this->actions as $action) {
				assert($action instanceof ActionEntity);

				$executionContext?->markRun($this->getId());
				$actionContexts = $action->getExecutionTargets($subContext);

				foreach($actionContexts as $actionContext) {
					if ($action->isAsynchronous())
					{
						Log::add('Action [' . $action->getId() . ' - ' . $action->getType() . '] is asynchronous and will be queued for : ' . $iterationContextIdentity . ' in automation [' . $this->getId() . '].', Log::DEBUG, 'com_emundus.automation');
						$task = $taskRepository->addActionToQueue($action, $actionContext, $this->getAutomatedTaskUserId());

						if (empty($task))
						{
							Log::add('Failed to queue asynchronous action [' . $action->getId() . ' - ' . $action->getType() . '] for : ' . $iterationContextIdentity . ' in automation [' . $this->getId() . '].', Log::ERROR, 'com_emundus.automation');
							$failedActions[] = ['action_id' => $action->getId(), 'label' => $action->getLabelForLog(), 'context' => $actionContext->serialize()];
						}
						else
						{
							Log::add('Asynchronous action [' . $action->getId() . ' - ' . $action->getType() . '] queued successfully (task id: ' . $task->getId() . ') for : ' . $iterationContextIdentity . ' in automation [' . $this->getId() . '].', Log::DEBUG, 'com_emundus.automation');
							$successActions[] = ['action_id' => $action->getId(), 'label' => $action->getLabelForLog(), 'context' => $actionContext->serialize(), 'task_id' => $task->getId()];
						}
					}
					else
					{
						$actionResult = $action->execute($actionContext, $executionContext);
						if ($actionResult === ActionExecutionStatusEnum::FAILED)
						{
							Log::add('Action [' . $action->getId() . ' - ' . $action->getType() . '] failed for : ' . $iterationContextIdentity . ' in automation [' . $this->getId() . '].', Log::ERROR, 'com_emundus.automation');
							$failedActions[] = ['action_id' => $action->getId(), 'label' => $action->getLabelForLog(), 'context' => $actionContext->serialize()];
						}
						else
						{
							Log::add('Action [' . $action->getId() . ' - ' . $action->getType() . '] executed successfully for : ' . $iterationContextIdentity . ' in automation [' . $this->getId() . '].', Log::DEBUG, 'com_emundus.automation');
							$successActions[] = ['action_id' => $action->getId(), 'label' => $action->getLabelForLog(), 'context' => $actionContext->serialize()];
						}
					}
				}
			}
		}

		if ($nbFilesPassed > 0)
		{
			$logUser = !empty($context->getUser()->id) ? $context->getUser()->id : $this->getAutomatedTaskUserId();

			$dispatcher               = Factory::getApplication()->getDispatcher();
			$onAfterAutomationProcessed = new GenericEvent('onAfterAutomationProcessed', [
				'context' => new EventContextEntity(
					Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($logUser),
					[],
					[],
					[
						'automation' => $this->getId(),
						'automation_entity' => $this->serialize(),
						'nb_files' => count($context->getFiles()),
						'nb_files_processed' => $nbFilesPassed,
						'successful_actions' => $successActions,
						'failed_actions' => $failedActions,
						'context' => $context->serialize()
					]
				),
				'execution_context' => $executionContext,
			]);
			$dispatcher->dispatch('onAfterAutomationProcessed', $onAfterAutomationProcessed);
		}

		return empty($failedActions);
	}

	/**
	 * @param   EventContextEntity  $context
	 *
	 * @return ActionTargetEntity[]
	 */
	private function getIterationContexts(EventContextEntity $context): array
	{
		$contexts = [];

		if (!empty($context->getFiles())) {
			$files = $context->getFiles();
			$contexts = array_map(function($index) use ($context, $files) {
				return new ActionTargetEntity(
					$context->getUser(),
					$files[$index],
					$context->getUsers()[$index] ?? null,
					$context->getParameters()
				);
			}, array_keys($files));
		} else if (!empty($context->getUsers())) {
			$contexts = array_map(function($user) use ($context) {
				return new ActionTargetEntity(
					$context->getUser(),
					null,
					$user,
					$context->getParameters()
				);
			}, $context->getUsers());
		} else if (!empty($context->getUser())) {
			$contexts = [new ActionTargetEntity(
				$context->getUser(),
				null,
				$context->getUser()->id,
				$context->getParameters()
			)];
		}

		return $contexts;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'description' => $this->getDescription(),
			'event' => $this->getEvent()?->serialize(),
			'published' => $this->isPublished(),
			'conditions_groups' => array_map(fn($group) => $group->serialize(), $this->getConditionsGroups()),
			'actions' => array_map(fn($action) => $action->serialize(), $this->getActions()),
		];
	}
}