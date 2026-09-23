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
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Task\TaskPriorityEnum;
use Tchooz\Enums\Automation\ActionMessageTypeEnum;
use Tchooz\Repositories\Mapping\MappingRepository;
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
				try
				{
					// todo: add a parameter to choose the method type
					$sent   = (new MappingExecutor())->execute($mappingEntity, $context, ApiMethodEnum::POST);
					$status = $sent ? ActionExecutionStatusEnum::COMPLETED : ActionExecutionStatusEnum::FAILED;
				}
				catch (\Exception $e)
				{
					$this->addExecutionMessage(new ActionExecutionMessage($e->getMessage(), ActionMessageTypeEnum::ERROR));
					Log::add('Error executing API map action: ' . $e->getMessage(), Log::ERROR, 'com_emundus.action');
				}
			}
			else
			{
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
}