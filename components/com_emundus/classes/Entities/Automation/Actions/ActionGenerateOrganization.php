<?php

namespace Tchooz\Entities\Automation\Actions;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Import\ImportConflictModeEnum;
use Tchooz\Services\Automation\Condition\FormDataConditionResolver;
use Tchooz\Services\Import\Entity\OrganizationImporter;
use Tchooz\Services\Import\ImportContext;
use Tchooz\Services\Import\Validation\TypeValidator;
use Tchooz\Traits\GeneratesEntityFromImportTrait;

class ActionGenerateOrganization extends ActionEntity
{
	use GeneratesEntityFromImportTrait;

	/**
	 * Column map fields this action does not offer to map. Other sources of the
	 * same column map, such as import files, still carry them.
	 */
	private const IGNORED_FIELD_PARAMETERS = [
		'contact_person_field',
		'other_contact_field',
	];

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
		return 'generate_organization';
	}

	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_GENERATE_ORGANIZATION_LABEL');
	}

	public static function getDescription(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_GENERATE_ORGANIZATION_DESCRIPTION');
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
			Log::add('Cannot generate organization [' . get_class($e) . '] : ' . $e->getMessage(), Log::ERROR, 'com_emundus.action');

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

		// Organization creation, duplicate detection (identifier_code then name)
		// and field type validation live in the import service; the ColumnMap is
		// the single source of truth.
		$importer  = OrganizationImporter::create();
		$columnMap = $importer->getColumnMap();
		$resolver  = new FormDataConditionResolver();
		$validator = new TypeValidator();

		$mode = $this->getParameterValue('duplicate_organization_action') === 'create_new_one'
			? ImportConflictModeEnum::CREATE_NEW
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
					// The required name field is missing or invalid in this file.
					Log::add('Skipping organization generation for file ' . $target->getFile() . ': required field missing or invalid.', Log::DEBUG, 'com_emundus.action');
					continue;
				}

				$importContext = new ImportContext('automation', 0, false, $target->getTriggeredBy()->id);

				$this->applyImport($importer, $row, $importContext, $mode);
				$generationStates[] = true;
			}
			catch (\Throwable $e)
			{
				Log::add('Error generating organization for file ' . $target->getFile() . ' [' . get_class($e) . '] : ' . $e->getMessage(), Log::ERROR, 'com_emundus.action');
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
			$this->parameters = array_values(array_filter(
				$this->buildFieldParametersFromColumnMap(OrganizationImporter::create()->getColumnMap()),
				static fn(Field $parameter) => !in_array($parameter->getName(), self::IGNORED_FIELD_PARAMETERS, true)
			));

			// Action-local control, not part of the import column map.
			$this->parameters[] = new ChoiceField('duplicate_organization_action', Text::_('TCHOOZ_AUTOMATION_ACTION_GENERATE_ORGANIZATION_PARAMETER_DUPLICATE_ORGANIZATION_ACTION_FIELD_LABEL'), [
				new ChoiceFieldValue('create_new_one', Text::_('TCHOOZ_AUTOMATION_ACTION_GENERATE_ORGANIZATION_PARAMETER_DUPLICATE_ORGANIZATION_ACTION_CREATE_CHOICE')),
				new ChoiceFieldValue('ignore', Text::_('TCHOOZ_AUTOMATION_ACTION_GENERATE_ORGANIZATION_PARAMETER_DUPLICATE_ORGANIZATION_ACTION_IGNORE_CHOICE')),
			], true, false);
		}

		return $this->parameters;
	}

	public function setParametersOptionsWithValues(): void
	{
		$this->resolveFieldParameterOptions(OrganizationImporter::create()->getColumnMap());
	}

	public function getLabelForLog(): string
	{
		return $this->getLabel();
	}
}
