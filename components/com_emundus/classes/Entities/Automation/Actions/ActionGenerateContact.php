<?php

namespace Tchooz\Entities\Automation\Actions;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Import\ImportConflictModeEnum;
use Tchooz\Services\Automation\Condition\FormDataConditionResolver;
use Tchooz\Services\Import\Entity\ContactImporter;
use Tchooz\Services\Import\ImportContext;
use Tchooz\Services\Import\Validation\TypeValidator;
use Tchooz\Traits\GeneratesEntityFromImportTrait;

class ActionGenerateContact extends ActionEntity
{
	use GeneratesEntityFromImportTrait;

	public static function getIcon(): ?string
	{
		return 'sensor_occupied';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::CONTACT;
	}

	public static function isAsynchronous(): bool
	{
		return false;
	}

	public static function getType(): string
	{
		return 'generate_contact';
	}

	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_GENERATE_CONTACT_LABEL');
	}

	public static function getDescription(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_GENERATE_CONTACT_DESCRIPTION');
	}

	public static function supportTargetTypes(): array
	{
		return []; // Action does not have targets
	}

	/**
	 * @throws Exception
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		try
		{
			$this->verifyRequiredParameters();
		}
		catch (\Throwable $e)
		{
			// Misconfiguration is not tied to a file: fail this action only, so the
			// automation keeps processing its other actions and files.
			Log::add('Cannot generate contact [' . get_class($e) . '] : ' . $e->getMessage(), Log::ERROR, 'com_emundus.action');

			return ActionExecutionStatusEnum::FAILED;
		}

		if (empty($context))
		{
			return ActionExecutionStatusEnum::FAILED;
		}

		if (!is_array($context))
		{
			$context = [$context];
		}

		// Contact creation/update, duplicate detection and field type validation
		// all live in the import service; the ColumnMap is the single source of truth.
		$importer  = ContactImporter::create();
		$columnMap = $importer->getColumnMap();
		$resolver  = new FormDataConditionResolver();
		$validator = new TypeValidator();

		$mode = $this->getParameterValue('duplicate_contact_action') === 'update_existing_one'
			? ImportConflictModeEnum::UPDATE
			: ImportConflictModeEnum::SKIP;

		$generationStates = [];
		foreach ($context as $target)
		{
			assert($target instanceof ActionTargetEntity);

			if (empty($target->getFile()))
			{
				continue;
			}

			try
			{
				$row = $this->buildValidatedRow($columnMap, $target, $resolver, $validator);

				if ($row === null)
				{
					// A required field (email/lastname/firstname) is missing or invalid
					// in this file: nothing to generate for this target.
					Log::add('Skipping contact generation for file ' . $target->getFile() . ': required field missing or invalid.', Log::DEBUG, 'com_emundus.action');
					continue;
				}

				$importContext = new ImportContext('automation', 0, false, $target->getTriggeredBy()->id);

				$this->applyImport($importer, $row, $importContext, $mode);
				$generationStates[] = true;
			}
			catch (\Throwable $e)
			{
				Log::add('Error generating contact for file ' . $target->getFile() . ' [' . get_class($e) . '] : ' . $e->getMessage(), Log::ERROR, 'com_emundus.action');
				$generationStates[] = false;
			}
		}

		return !empty($generationStates) && !in_array(false, $generationStates, true) ? ActionExecutionStatusEnum::COMPLETED : ActionExecutionStatusEnum::FAILED;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			// Field pickers are derived from the importer's ColumnMap so labels and
			// required flags stay defined in one place (the importer).
			$this->parameters = $this->buildFieldParametersFromColumnMap(ContactImporter::create()->getColumnMap());

			// Action-local control, not part of the import column map.
			$this->parameters[] = new ChoiceField('duplicate_contact_action', Text::_('TCHOOZ_AUTOMATION_ACTION_GENERATE_CONTACT_PARAMETER_DUPLICATE_CONTACT_ACTION_FIELD_LABEL'), [
				new ChoiceFieldValue('update_existing_one', Text::_('TCHOOZ_AUTOMATION_ACTION_GENERATE_CONTACT_PARAMETER_DUPLICATE_CONTACT_ACTION_UPDATE_CHOICE')),
				new ChoiceFieldValue('ignore', Text::_('TCHOOZ_AUTOMATION_ACTION_GENERATE_CONTACT_PARAMETER_DUPLICATE_CONTACT_ACTION_IGNORE_CHOICE')),
			], true, false);

			// The contact's nationality is stored as a country, so a nationality
			// reference cannot be resolved into one.
			$this->getParameter('nationality_field')?->setHelpText(Text::_('TCHOOZ_AUTOMATION_ACTION_GENERATE_CONTACT_PARAMETER_NATIONALITY_FIELD_HELP_TEXT'));
		}

		return $this->parameters;
	}

	public function setParametersOptionsWithValues(): void
	{
		$this->resolveFieldParameterOptions(ContactImporter::create()->getColumnMap());
	}

	public function getLabelForLog(): string
	{
		return $this->getLabel();
	}
}
