<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\MixedField;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Automation\ConditionRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Services\Automation\ConditionRegistry;
use Tchooz\Services\Field\FieldOptionProvider;
use Tchooz\Services\Field\FieldResearch;
use Tchooz\Services\Field\FieldWatcher;

class ActionUpdateFileData extends ActionEntity
{

	public static function getIcon(): ?string
	{
		return 'edit_document';
	}

	/**
	 * @inheritDoc
	 */
	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::FILE;
	}

	/**
	 * @inheritDoc
	 */
	public static function isAsynchronous(): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public static function getType(): string
	{
		return 'update_file_data';
	}

	/**
	 * @inheritDoc
	 */
	public static function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_FILE_DATA_LABEL');
	}

	/**
	 * @inheritDoc
	 */
	public static function supportTargetTypes(): array
	{
		return [TargetTypeEnum::FILE];
	}

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$executionStatus = ActionExecutionStatusEnum::FAILED;

		if (!empty($context->getFile()))
		{
			$fieldType = $this->getParameterValue('type');
			$fieldName = $this->getParameterValue('field');
			$newValue = $this->getParameterValue('value');

			switch ($fieldType)
			{
				case ConditionTargetTypeEnum::FORMDATA->value:
					list($formId, $elementId) = explode('.', $fieldName, 2);
					$fabrikRepository          = new FabrikRepository(true);
					$element                   = $fabrikRepository->getElementById($elementId);
					$applicationFileRepository = new ApplicationFileRepository();
					$applicationFileRepository->insertDatas([
						$element->getName() => $newValue
					], $element->getDbTableName(), $context->getFile(), 0, $context->getTriggeredBy()->id);

					break;
				case ConditionTargetTypeEnum::ALIASDATA->value:

					break;
			}
		}

		return $executionStatus;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$optionProvider = new FieldOptionProvider('automation', 'getConditionsFields', ['type'], new ConditionRepository(), 'getAvailableConditionFields', [], 'getLabel', 'getName');
			$watcher = new FieldWatcher('type');

			$this->parameters = [
				new ChoiceField('type', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_FILE_DATA_PARAMETER_TYPE_LABEL'), [
					new ChoiceFieldValue(ConditionTargetTypeEnum::FORMDATA->value, ConditionTargetTypeEnum::FORMDATA->getLabel()),
					new ChoiceFieldValue(ConditionTargetTypeEnum::ALIASDATA->value, ConditionTargetTypeEnum::ALIASDATA->getLabel())
				], true, false),
				(new ChoiceField('field', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_FILE_DATA_PARAMETER_FIELD_LABEL'), [], true, false))
					->setOptionsProvider($optionProvider)
					->addWatcher($watcher),
				new MixedField('value', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_FILE_DATA_PARAMETER_VALUE_LABEL'), true),
			];
		}

		return $this->parameters;
	}

	public function getLabelForLog(): string
	{
		return self::getLabel();
	}
}