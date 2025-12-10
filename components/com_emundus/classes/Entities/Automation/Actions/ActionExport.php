<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Services\Export\ExportRegistry;

class ActionExport extends ActionEntity
{
	public const FORMAT_PARAMETER = 'format';

	public const EXPORT_BASE_PATH = 'images/emundus/exports/';

	private array $formatChoices = [];

	public static function getType(): string
	{
		return 'export';
	}

	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_EXPORT_LABEL');
	}

	public static function getDescription(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_EXPORT_DESCRIPTION');
	}

	/**
	 * Execute the action with the given parameters.
	 *
	 * @param   ActionTargetEntity|ActionTargetEntity[]         $context
	 * @param   AutomationExecutionContext|null  $executionContext
	 *
	 * @return ActionExecutionStatusEnum  The status of the action execution.
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		try
		{
			$this->verifyRequiredParameters();

			if (is_array($context))
			{
				$fnums = array_map(function($target) {
					return $target->getFile();
				}, $context);
			}
			else
			{
				$fnums = [$context->getFile()];
			}

			if (empty($fnums))
			{
				throw new \Exception('No fnums provided for export.');
			}

			$format = $this->getParameterValue(self::FORMAT_PARAMETER);
			if (empty($format))
			{
				throw new \Exception('No export format specified.');
			}

			$format = ExportFormatEnum::tryFrom($format);
			if (empty($format))
			{
				throw new \Exception('Invalid export format specified.');
			}
			
			$langCode = $this->getParameterValue('lang');

			$lang        = Factory::getApplication()->getLanguage();
			$lang->setDefault($langCode);
			$lang->load('com_emundus', JPATH_SITE . '/components/com_emundus', $langCode);
			$lang->load('', JPATH_SITE, $langCode);
			
			$exportRepository = new ExportRepository();
			$exportEntity = null;
			$task = null;

			if ($this->isExecutedWith(ExportEntity::class))
			{
				$exportEntity = $this->getWithOfType(ExportEntity::class)[0];
			}
			elseif ($this->isExecutedWith(TaskEntity::class))
			{
				$task = $this->getWithOfType(TaskEntity::class)[0];
				$exportEntity = $exportRepository->getExportByTask($task->getId());
			}

			$triggeredBy = is_array($context) ? $context[0]->getTriggeredBy() : $context->getTriggeredBy();

			$exportPath = self::EXPORT_BASE_PATH . $triggeredBy->id . '/';

			$exportRegistry = new ExportRegistry();
			$exportService  = $exportRegistry->getExportServiceInstance(
				$format->getType(),
				$fnums,
				$triggeredBy,
				$this->getParameterValues(),
				$exportEntity
			);
			$result         = $exportService->export($exportPath, $task, $langCode);

			if ($result->isStatus() && !empty($result->getFilePath()))
			{
				$expiredAt = null;
				if ($result->getProgress() === 100)
				{
					// Set expired_date to +7 days
					$expiredAt = new \DateTime();
					$expiredAt->modify('+7 days');
				}

				if ($exportEntity)
				{
					$exportEntity->setFilename($result->getFilePath());
					$exportEntity->setProgress($result->getProgress());
					if ($expiredAt)
					{
						$exportEntity->setExpiredAt($expiredAt);
					}
				}
				else
				{
					$exportEntity = new ExportEntity(
						id: 0,
						createdAt: new \DateTime(),
						createdBy: $triggeredBy,
						filename: $result->getFilePath(),
						format: $format,
						expiredAt: $expiredAt,
						task: $task ?? null,
						hits: 0,
						progress: $result->getProgress()
					);
				}

				if (!$exportRepository->flush($exportEntity))
				{
					throw new \Exception('Failed to save export record.');
				}

				if ($result->getProgress() !== 100)
				{
					return ActionExecutionStatusEnum::PENDING;
				}
			}
			else
			{
				throw new \Exception('Export failed to generate file.');
			}
		}
		catch (\Exception $e)
		{
			if (isset($exportEntity) && !empty($exportEntity->getId()))
			{
				$exportEntity->setFailed(true);
				$exportRepository->flush($exportEntity);
			}

			Log::add('Export action failed: ' . $e->getMessage(), Log::ERROR, 'com_emundus.action');

			return ActionExecutionStatusEnum::FAILED;
		}

		return ActionExecutionStatusEnum::COMPLETED;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$this->parameters = [
				new ChoiceField(self::FORMAT_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_EXPORT_PARAMETER_FORMAT_LABEL'), $this->getFormatChoices(), true, true)
			];
		}

		return $this->parameters;
	}

	private function getFormatChoices(): array
	{
		$options = [];

		if (!empty($this->formatChoices))
		{
			return $this->formatChoices;
		}
		else
		{
			$formats = ExportFormatEnum::cases();

			foreach ($formats as $format)
			{
				$options[] = new ChoiceFieldValue($format->value, $format->getLabel());
			}
		}

		return $options;
	}

	public static function getIcon(): ?string
	{
		return 'chart';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::FILE;
	}

	public static function supportTargetTypes(): array
	{
		return [TargetTypeEnum::FILE];
	}

	public static function isAsynchronous(): bool
	{
		return true;
	}

	public function getLabelForLog(): string
	{
		return $this->getLabel();
	}

	public function isAvailable(): bool
	{
		return false;
	}
}