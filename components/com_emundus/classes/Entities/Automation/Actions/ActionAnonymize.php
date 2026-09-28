<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionExecutionMessage;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\ActionMessageTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;

class ActionAnonymize extends ActionEntity
{

	public static function getIcon(): ?string
	{
		return 'domino_mask';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::FILE;
	}

	public static function isAsynchronous(): bool
	{
		return false;
	}

	public static function getType(): string
	{
		return 'anonymize';
	}

	public static function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_ACTION_ANONYMIZE_LABEL');
	}

	public static function getDescription(): string
	{
		return Text::_('COM_EMUNDUS_ACTION_ANONYMIZE_DESCRIPTION');
	}

	public static function supportTargetTypes(): array
	{
		return [TargetTypeEnum::FILE];
	}

	/**
	 * @param   ActionTargetEntity|array<ActionTargetEntity>  $context
	 * @param   AutomationExecutionContext|null               $executionContext
	 *
	 * @return ActionExecutionStatusEnum
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$status = ActionExecutionStatusEnum::FAILED;

		if (!empty($context))
		{
			if (!is_array($context)) {
				$context = [$context];
			}

			$allSuccessful = true;
			foreach ($context as $target)
			{
				$applicationFileRepository = new ApplicationFileRepository();
				$applicationFile = $applicationFileRepository->getByFnum($target->getFile());
				$applicationFile->setIsAnonymous(true);

				if (!$applicationFileRepository->flush($applicationFile)) {
					$this->addExecutionMessage(new ActionExecutionMessage(
						Text::sprintf('COM_EMUNDUS_ACTION_ANONYMIZE_FAILED_MESSAGE', $applicationFile->getFnum()),
						ActionMessageTypeEnum::ERROR
					));
					$allSuccessful = false;
				}
				else
				{
					$this->log(
						ActionEnum::FILE,
						CrudEnum::UPDATE,
						'COM_EMUNDUS_LOGS_AUTOMATION_ANONYMIZE',
						['updated' => [['description' => Text::_('COM_EMUNDUS_LOGS_AUTOMATION_ANONYMIZE')]]],
						$target->getFile(),
						$target->getTriggeredBy()->id,
						$target->getUserId()
					);
				}
			}

			$status = $allSuccessful ? ActionExecutionStatusEnum::COMPLETED : ActionExecutionStatusEnum::FAILED;
		}

		return $status;
	}

	public function getParameters(): array
	{
		return [];
	}

	public function getLabelForLog(): string
	{
		return Text::_('COM_EMUNDUS_ACTION_ANONYMIZE_LABEL');
	}
}