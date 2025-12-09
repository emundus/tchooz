<?php

namespace Tchooz\Entities\Automation\Actions;

use EmundusModelApplication;
use EmundusModelFiles;
use EmundusModelSettings;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;

class ActionUpdateFileTags extends ActionEntity
{
	private array $tagsChoices = [];

	public const TAGS_PARAMETER = 'tags';

	public const ADD_OR_REMOVE_PARAMETER = 'add_or_remove';

	public const ADD = 'add';
	public const REMOVE = 'remove';

	public static function getType(): string
	{
		return 'update_tags';
	}

	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_UPDATE_TAGS_LABEL');
	}

	public static function getDescription(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_UPDATE_TAGS_DESCRIPTION');
	}

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$tags = $this->getParameterValue(self::TAGS_PARAMETER);
		if (empty($tags)) {
			throw new \InvalidArgumentException('No tags provided');
		}
		$addOrRemove = $this->getParameterValue('add_or_remove');

		switch($addOrRemove) {
			case 'add':
				if (!class_exists('EmundusModelFiles'))
				{
					require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
				}
				$m_files = new EmundusModelFiles();
				$tagged = $m_files->tagFile([$context->getFile()], $tags, $context->getTriggeredBy()->id, $executionContext);

				if (!$tagged) {
					return ActionExecutionStatusEnum::FAILED;
				}
				break;
			case 'remove':
				if (!class_exists('EmundusModelApplication'))
				{
					require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
				}
				$m_application = new EmundusModelApplication();

				$removeAll = true;
				foreach($tags as $tag)
				{

					$deleted = $m_application->deleteTag($tag, $context->getFile(), null, $context->getTriggeredBy()->id, $executionContext);
					if (!$deleted)
					{
						$removeAll = false;
					}

				}

				if (!$removeAll) {
					return ActionExecutionStatusEnum::FAILED;
				}
				break;
			default:
				throw new \InvalidArgumentException('Invalid add_or_remove value: ' . $addOrRemove);
		}

		return ActionExecutionStatusEnum::COMPLETED;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters)) {
			$this->parameters = [
				new ChoiceField(self::ADD_OR_REMOVE_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_ADD_OR_REMOVE_LABEL'), [
					new ChoiceFieldValue(self::ADD, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_ADD_OR_REMOVE_ADD')),
					new ChoiceFieldValue(self::REMOVE, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_ADD_OR_REMOVE_REMOVE')),
				], true),
				new ChoiceField(self::TAGS_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_TAGS_LABEL'), $this->getTagChoices(), true, true)
			];
		}

		return $this->parameters;
	}

	/**
	 * Get the available tags from the system.
	 *
	 * @return ChoiceFieldValue[] Array of available tags as ChoiceFieldValue objects.
	 */
	private function getTagChoices(): array
	{
		if (empty($this->tagsChoices)) {
			if (!class_exists('EmundusModelSettings'))
			{
				require_once JPATH_ROOT.'/components/com_emundus/models/settings.php';
			}
			$m_settings = new EmundusModelSettings();
			$tags = $m_settings->getTags();

			foreach ($tags as $tag) {
				$this->tagsChoices[] = new ChoiceFieldValue($tag->id, $tag->label);
			}
		}

		return $this->tagsChoices;
	}

	public static function getIcon(): ?string
	{
		return 'sell';
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
		return false;
	}

	public function getLabelForLog(): string
	{
		$labelForLog = $this->getLabel();

		if ($this->getParameterValue(self::ADD_OR_REMOVE_PARAMETER) === self::ADD) {
			$labelForLog .= ' - ' . Text::_('COM_EMUNDUS_AUTOMATION_ACTION_ADD_TAG') . ': ';
		} else {
			$labelForLog .= ' - ' . Text::_('COM_EMUNDUS_AUTOMATION_ACTION_REMOVE_TAG') . ': ';
		}

		$tagsChoices = $this->getTagChoices();
		$tags = $this->getParameterValue(self::TAGS_PARAMETER);
		foreach($tags as $tag)
		{
			foreach($tagsChoices as $choice)
			{
				if ($choice->getValue() == $tag)
				{
					$labelForLog .= $choice->getLabel() . ', ';
				}
			}
		}

		return Text::_($labelForLog);
	}
}