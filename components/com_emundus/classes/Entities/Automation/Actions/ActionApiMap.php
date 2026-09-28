<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionExecutionMessage;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Task\TaskPriorityEnum;
use Tchooz\Enums\Automation\ActionMessageTypeEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Mapping\MappingRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Mapping\MappingExecutor;

class ActionApiMap extends ActionEntity
{

	public static function getIcon(): ?string
	{
		return 'api';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return null;
	}

	public static function isAsynchronous(): bool
	{
		return true;
	}

	public static function getType(): string
	{
		return 'api_map';
	}

	public static function supportTargetTypes(): array
	{
		return [];
	}

	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$status = ActionExecutionStatusEnum::FAILED;

		$this->verifyRequiredParameters();

		if (!empty($this->getParameterValue('api_map_id')))
		{
			$mappingRepository = new MappingRepository();
			$mappingEntity     = $mappingRepository->getById((int) $this->getParameterValue('api_map_id'));

			if (!empty($mappingEntity))
			{
				$executor = new MappingExecutor();

				try
				{
					// todo: add a parameter to choose the method type
					$sent   = $executor->execute($mappingEntity, $context, ApiMethodEnum::POST);
					$status = $sent ? ActionExecutionStatusEnum::COMPLETED : ActionExecutionStatusEnum::FAILED;

					if ($sent)
					{
						$this->logSent($mappingEntity, is_array($context) ? $context : [$context]);
					}
				}
				catch (\Exception $e)
				{
					$this->addExecutionMessage(new ActionExecutionMessage($e->getMessage(), ActionMessageTypeEnum::ERROR));
					Log::add('Error executing API map action: ' . $e->getMessage(), Log::ERROR, 'com_emundus.action');
				}

				// What the synchronization did, item by item — reported whether it succeeded or not,
				// so the task history says more than "failed".
				foreach ($executor->getExecutionMessages() as $message)
				{
					$this->addExecutionMessage($message);
				}

				// A synchronization can decline without throwing. Never leave the task with nothing to
				// explain its failure.
				if ($status === ActionExecutionStatusEnum::FAILED && empty($this->getExecutionMessages(ActionMessageTypeEnum::ERROR)))
				{
					$this->addExecutionMessage(new ActionExecutionMessage(Text::sprintf('TCHOOZ_AUTOMATION_ACTION_API_MAP_FAILED', $mappingEntity->getLabel()), ActionMessageTypeEnum::ERROR));
				}
			}
			else
			{
				$this->addExecutionMessage(new ActionExecutionMessage(Text::sprintf('TCHOOZ_AUTOMATION_ACTION_API_MAP_NOT_FOUND', $this->getParameterValue('api_map_id')), ActionMessageTypeEnum::ERROR));
				Log::add('Mapping not found for API map action with ID: ' . $this->getParameterValue('api_map_id'), Log::WARNING, 'com_emundus.action');
			}
		}

		return $status;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$options = $this->getMappingOptions();

			$this->parameters = [
				new ChoiceField('type', Text::_('TCHOOZ_AUTOMATION_ACTION_API_MAP_PARAMETER_TYPE_LABEL'), [
					new ChoiceFieldValue('post', Text::_('TCHOOZ_AUTOMATION_ACTION_API_MAP_PARAMETER_TYPE_POST_LABEL')),
					// todo: implement GET method handling
					//new ChoiceFieldValue('get', Text::_('TCHOOZ_AUTOMATION_ACTION_API_MAP_PARAMETER_TYPE_GET_LABEL')),
				], true),
				new ChoiceField('api_map_id', Text::_('TCHOOZ_AUTOMATION_ACTION_API_MAP_PARAMETER_API_MAP_ID_LABEL'), $options, true),
			];
		}

		return $this->parameters;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getMappingOptions(): array
	{
		$options = [];

		$repository = new MappingRepository();
		$mappings   = $repository->getAll([], 0);
		foreach ($mappings as $mapping)
		{
			$options[] = new ChoiceFieldValue($mapping->getId(), $mapping->getLabel());
		}

		return $options;
	}

	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_API_MAP_LABEL');
	}

	public function getLabelForLog(): string
	{
		return self::getLabel();
	}

	public function getPriority(): TaskPriorityEnum
	{
		return TaskPriorityEnum::HIGH;
	}

	/**
	 * @param   ActionTargetEntity[]  $targets
	 */
	private function logSent(MappingEntity $mappingEntity, array $targets): void
	{
		$synchronizerName = (new SynchronizerRepository())->getById($mappingEntity->getSynchronizerId())?->getName() ?? '';

		foreach ($targets as $target)
		{
			$this->log(
				ActionEnum::EXTERNAL_EXPORT,
				CrudEnum::CREATE,
				'COM_EMUNDUS_LOGS_AUTOMATION_API_MAP',
				['created' => [[
					'element' => $synchronizerName,
					'details' => $mappingEntity->getLabel()
				]]],
				$target->getFile(),
				$target->getTriggeredBy()->id,
				$target->getUserId()
			);
		}
	}
}
