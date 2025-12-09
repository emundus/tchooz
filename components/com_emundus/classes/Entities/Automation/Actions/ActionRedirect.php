<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;

class ActionRedirect extends ActionEntity
{

	public static function getIcon(): ?string
	{
		return 'directions';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::USER;
	}

	/**
	 * @inheritDoc
	 */
	public static function getType(): string
	{
		return 'redirect';
	}

	/**
	 * @inheritDoc
	 */
	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_LABEL');
	}

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		if (empty($this->getParameterValue('url')) && empty($this->getParameterValue('custom_url'))) {
			return ActionExecutionStatusEnum::FAILED;
		}

		$app = Factory::getApplication();
		if ($app->isClient('site'))
		{
			if (!empty($this->getParameterValue('custom_url')))
			{
				if (str_starts_with($this->getParameterValue('custom_url'), 'http://'))
				{
					$url = str_replace('http://', 'https://', $this->getParameterValue('url'));
				}
				else
				{
					$url = $this->getParameterValue('custom_url');
				}
			}
			else if (!empty($this->getParameterValue('url')))
			{

				$url = Route::_('index.php?Itemid=' . $this->getParameterValue('url'));
			}

			if (!empty($url))
			{
				if ($url !== '/' . $app->getMenu()->getActive()->route)
				{
					// Perform the redirect
					$app->redirect($url);
				}
			}

			return ActionExecutionStatusEnum::COMPLETED;
		}

		return ActionExecutionStatusEnum::FAILED;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$this->parameters = [
				new ChoiceField('url', Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_TO_URL'), $this->getMenuOptions()),
				new StringField('custom_url', Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_TO_CUSTOM_URL')),
			];
		}

		return $this->parameters;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getMenuOptions(): array
	{
		$options = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery()
			->select($db->quoteName(['id', 'title', 'path', 'link']))
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('published') . ' = 1')
			->where($db->quoteName('client_id') . ' = 0')
			->order($db->quoteName('lft'));

		$db->setQuery($query);
		$items = $db->loadObjectList();

		foreach ($items as $item)
		{
			$options[]= new ChoiceFieldValue($item->id, Text::_($item->title) . ' (' . $item->path . ')');
		}

		return $options;
	}

	public static function supportTargetTypes(): array
	{
		return [];
	}

	public static function isAsynchronous(): bool
	{
		return false;
	}

	public function getLabelForLog(): string
	{
		return $this->getLabel();
	}
}