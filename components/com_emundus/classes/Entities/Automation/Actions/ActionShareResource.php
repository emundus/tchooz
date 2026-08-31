<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\Resource\ResourceAccessTypeEnum;
use Tchooz\Enums\Resource\ResourcePermissionEnum;
use Tchooz\Services\Field\DisplayRule;
use Tchooz\Services\Resource\ResourceService;

class ActionShareResource extends ActionEntity
{
	public const RESOURCE_PARAMETER      = 'resources';
	public const FOLDER_PARAMETER        = 'folders';
	public const RESOURCE_TYPE_PARAMETER = 'resource_type';
	public const PERMISSION_PARAMETER    = 'permission';
	public const ADD_OR_REMOVE_PARAMETER = 'add_or_remove';
	public const ADD                     = 'add';
	public const REMOVE                  = 'remove';
	public const TYPE_FILE               = 'file';
	public const TYPE_FOLDER             = 'folder';

	private array $resourceChoices = [];
	private array $folderChoices   = [];

	public static function getIcon(): ?string
	{
		return 'perm_media';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::USER;
	}

	public static function isAsynchronous(): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public static function getType(): string
	{
		return 'share_resource';
	}

	/**
	 * @inheritDoc
	 */
	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_SHARE_RESOURCE_LABEL');
	}

	/**
	 * @inheritDoc
	 */
	public static function supportTargetTypes(): array
	{
		return [TargetTypeEnum::USER];
	}

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$executed = ActionExecutionStatusEnum::FAILED;

		$this->verifyRequiredParameters();

		if (!empty($context->getUserId()))
		{
			$resourceType = $this->getParameterValue(self::RESOURCE_TYPE_PARAMETER) ?? self::TYPE_FILE;
			$isFolder     = $resourceType === self::TYPE_FOLDER;

			$resourceIds = $this->getParameterValue($isFolder ? self::FOLDER_PARAMETER : self::RESOURCE_PARAMETER);
			if (!is_array($resourceIds))
			{
				$resourceIds = [$resourceIds];
			}

			$mode       = $this->getParameterValue(self::ADD_OR_REMOVE_PARAMETER) ?? self::ADD;
			$permission = ResourcePermissionEnum::tryFrom((string) $this->getParameterValue(self::PERMISSION_PARAMETER))
				?? ResourcePermissionEnum::VIEW;

			$resourceService = new ResourceService();
			$userId          = (int) $context->getUserId();

			// The share is applied per-resource without an enclosing transaction, so a mid-loop
			// throw can leave a partial share set. $results is tracked outside the try and persisted
			// in both the success and failure paths so a partial apply stays detectable.
			$results = [];
			try
			{
				foreach ($resourceIds as $resourceId)
				{
					$resourceId = (int) $resourceId;
					if ($resourceId <= 0)
					{
						continue;
					}

					if ($mode === self::REMOVE)
					{
						$results[$resourceId] = $isFolder
							? $resourceService->revokeFolderAccess($resourceId, ResourceAccessTypeEnum::USER, $userId)
							: $resourceService->revokeAccess($resourceId, ResourceAccessTypeEnum::USER, $userId);
					}
					else
					{
						$results[$resourceId] = $isFolder
							? $resourceService->grantFolderAccess($resourceId, ResourceAccessTypeEnum::USER, $userId, $permission)
							: $resourceService->grantAccess($resourceId, ResourceAccessTypeEnum::USER, $userId, $permission);
					}
				}

				$this->setResult($results);
				$executed = !empty($results) && !in_array(false, $results, true)
					? ActionExecutionStatusEnum::COMPLETED
					: ActionExecutionStatusEnum::FAILED;
			}
			catch (\Exception $e)
			{
				$this->setResult($results);
				Log::add(
					sprintf(
						'Error sharing resources in ActionShareResource for user %d (mode: %s): %s [%s] in %s:%d. Applied before failure: %s',
						$userId,
						$mode,
						$e->getMessage(),
						get_class($e),
						$e->getFile(),
						$e->getLine(),
						json_encode($results)
					),
					Log::ERROR,
					'com_emundus.action'
				);
			}
		}

		return $executed;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$addOrRemoveField = new ChoiceField(
				self::ADD_OR_REMOVE_PARAMETER,
				Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_RESOURCE_PARAMETER_ADD_OR_REMOVE_LABEL'),
				[
					new ChoiceFieldValue(self::ADD, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_RESOURCE_PARAMETER_ADD_OR_REMOVE_OPTION_ADD_LABEL')),
					new ChoiceFieldValue(self::REMOVE, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_RESOURCE_PARAMETER_ADD_OR_REMOVE_OPTION_REMOVE_LABEL')),
				],
				true
			);

			// The permission only makes sense when granting access; it is hidden (and its required
			// check skipped) when the action removes access.
			$permissionField = (new ChoiceField(
				self::PERMISSION_PARAMETER,
				Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_RESOURCE_PARAMETER_PERMISSION_LABEL'),
				$this->getPermissionChoices(),
				true
			))->setDisplayRules([new DisplayRule($addOrRemoveField, ConditionOperatorEnum::EQUALS, self::ADD)]);

			// Whether the action shares individual files or whole folders. The two pickers below are
			// mutually exclusive, driven by this choice; files default so the previous behaviour holds.
			$resourceTypeField = new ChoiceField(
				self::RESOURCE_TYPE_PARAMETER,
				Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_RESOURCE_PARAMETER_TYPE_LABEL'),
				[
					new ChoiceFieldValue(self::TYPE_FILE, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_RESOURCE_PARAMETER_TYPE_OPTION_FILE_LABEL')),
					new ChoiceFieldValue(self::TYPE_FOLDER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_RESOURCE_PARAMETER_TYPE_OPTION_FOLDER_LABEL')),
				],
				true
			);
			$resourceTypeField->setDefaultValue(self::TYPE_FILE);

			// Files: grouped by folder (root files under a "Racine" group), shown when type = file.
			$fileField = (new ChoiceField(self::RESOURCE_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_RESOURCE_PARAMETER_RESSOURCES_LABEL'), $this->getResourceChoices(), true, true, null, true))
				->setDisplayRules([new DisplayRule($resourceTypeField, ConditionOperatorEnum::EQUALS, self::TYPE_FILE)]);

			// Folders: flat (ungrouped) list, shown when type = folder.
			$folderField = (new ChoiceField(self::FOLDER_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_RESOURCE_PARAMETER_FOLDERS_LABEL'), $this->getFolderChoices(), true, true))
				->setDisplayRules([new DisplayRule($resourceTypeField, ConditionOperatorEnum::EQUALS, self::TYPE_FOLDER)]);

			$this->parameters = [
				$resourceTypeField,
				$fileField,
				$folderField,
				$addOrRemoveField,
				$permissionField,
			];
		}

		return $this->parameters;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getResourceChoices(): array
	{
		if (!empty($this->resourceChoices))
		{
			return $this->resourceChoices;
		}

		// Choices are grouped by folder: root files first under a "Racine" group, then one group
		// per folder path sorted alphabetically. Grouping is driven by each choice's FieldGroup.
		$rootGroup    = new FieldGroup('root', Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_RESOURCE_PARAMETER_RESSOURCES_ROOT_GROUP'));
		$folderGroups = [];
		$rootOptions   = [];
		$folderOptions = [];

		$resourceService = new ResourceService();
		foreach ($resourceService->getFilesWithFolderPath() as $resource)
		{
			$path = $resource->folder_path;
			if ($path === '')
			{
				$rootOptions[] = new ChoiceFieldValue((string) $resource->id, $resource->name, $rootGroup);
				continue;
			}

			if (!isset($folderGroups[$path]))
			{
				$folderGroups[$path]  = new FieldGroup('folder_' . md5($path), $path);
				$folderOptions[$path] = [];
			}

			$folderOptions[$path][] = new ChoiceFieldValue((string) $resource->id, $resource->name, $folderGroups[$path]);
		}

		ksort($folderOptions);

		$options = $rootOptions;
		foreach ($folderOptions as $groupOptions)
		{
			$options = array_merge($options, $groupOptions);
		}

		$this->resourceChoices = $options;

		return $options;
	}

	/**
	 * Flat (ungrouped) list of every folder as a breadcrumb label. The service's root placeholder
	 * (empty value) is dropped: the library root itself is not a shareable folder.
	 *
	 * @return array<ChoiceFieldValue>
	 */
	private function getFolderChoices(): array
	{
		if (!empty($this->folderChoices))
		{
			return $this->folderChoices;
		}

		$options = [];

		$resourceService = new ResourceService();
		foreach ($resourceService->getFolderOptions(Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_RESOURCE_PARAMETER_RESSOURCES_ROOT_GROUP')) as $folder)
		{
			if ($folder['value'] === '')
			{
				continue;
			}

			$options[] = new ChoiceFieldValue($folder['value'], $folder['label']);
		}

		$this->folderChoices = $options;

		return $options;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getPermissionChoices(): array
	{
		$options = [];

		foreach (ResourcePermissionEnum::cases() as $permission)
		{
			$options[] = new ChoiceFieldValue($permission->value, $permission->getLabel());
		}

		return $options;
	}

	public function getLabelForLog(): string
	{
		return $this->getLabel();
	}
}