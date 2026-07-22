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
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\RadioField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Services\Field\DisplayRule;

class ActionRedirect extends ActionEntity
{
	const URL_TYPE = 'url_type';
	const KNOWN_URL = 'known_url';
	const INTERN_URL = 'url';
	const CUSTOM_URL = 'custom_url';

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
		if (empty($this->getParameterValue(self::KNOWN_URL)) && empty($this->getParameterValue(self::INTERN_URL)) && empty($this->getParameterValue(self::CUSTOM_URL)))
		{
			return ActionExecutionStatusEnum::FAILED;
		}

		$app = Factory::getApplication();
		if ($app->isClient('site'))
		{
			$url = $this->getUrl($context);

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

	public function getUrl(ActionTargetEntity|array $context): string
	{
		$url = '';

		$knownUrl  = $this->getParameterValue(self::KNOWN_URL);
		$internUrl = $this->getParameterValue(self::INTERN_URL);
		$customUrl = $this->getParameterValue(self::CUSTOM_URL);

		if (!empty($knownUrl))
		{
			$url = $this->getKnownUrl($knownUrl, $context);
		}
		else
		{
			if (!empty($customUrl))
			{
				if (str_starts_with($customUrl, 'http://'))
				{
					$url = str_replace('http://', 'https://', $customUrl);
				}
				else
				{
					$url = $customUrl;
				}
			}
			else
			{
				if (!empty($internUrl))
				{
					$url = Route::_('index.php?Itemid=' . $internUrl);
				}
			}
		}

		return $url;
	}

	/**
	 * Build the target URL for a known-url abstraction. Menu-page destinations
	 * are resolved through EmundusHelperMenu (never hardcoded routes); the
	 * applicant campaign catalog has no fixed route and is resolved from the
	 * menu item carrying the mod_emundus_campaign module.
	 */
	private function getKnownUrl(string $knownUrl, ActionTargetEntity|array $context): string
	{
		if (!class_exists('EmundusHelperMenu'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/menu.php';
		}

		switch ($knownUrl)
		{
			case 'open_file':
				$fnum = $this->resolveContextFnum($context);

				return !empty($fnum)
					? Route::_('/index.php?option=com_emundus&task=openfile&fnum=' . $fnum)
					: '';
			case 'my_applications':
				return \EmundusHelperMenu::routeViaLink('index.php?option=com_emundus&view=application_choices');
			case 'campaigns_catalog':
				return $this->getCampaignCatalogUrl();
			case 'home':
				return \EmundusHelperMenu::getHomepageLink();
			case 'files_list':
				return \EmundusHelperMenu::routeViaLink('index.php?option=com_emundus&view=files');
			default:
				// TODO(redirect-known-urls): open_file_manager (files-list menu route + '#'.fnum, per messenger::gotofile) not handled yet.
				return '';
		}
	}

	/**
	 * The fnum of the file the automation runs on, or null when the context
	 * carries no file (some events have no file target).
	 */
	private function resolveContextFnum(ActionTargetEntity|array $context): ?string
	{
		if (!is_array($context))
		{
			$context = [$context];
		}

		return !empty($context[0]) && !empty($context[0]->getFile()) ? $context[0]->getFile() : null;
	}

	/**
	 * Resolve the applicant campaign catalog URL. That catalog has no fixed
	 * route: it is a menu item carrying the mod_emundus_campaign module (tchooz
	 * default template). We route to the menu item the module is bound to, and
	 * fall back to the configured home page when it is not bound to a specific
	 * item (e.g. published on all pages).
	 */
	private function getCampaignCatalogUrl(): string
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery()
			->select($db->quoteName('mm.menuid'))
			->from($db->quoteName('#__modules', 'm'))
			->join('INNER', $db->quoteName('#__modules_menu', 'mm') . ' ON ' . $db->quoteName('mm.moduleid') . ' = ' . $db->quoteName('m.id'))
			->join('INNER', $db->quoteName('#__menu', 'menu') . ' ON ' . $db->quoteName('menu.id') . ' = ' . $db->quoteName('mm.menuid'))
			->where($db->quoteName('m.module') . ' = ' . $db->quote('mod_emundus_campaign'))
			->andWhere($db->quoteName('m.published') . ' = 1')
			->andWhere($db->quoteName('mm.menuid') . ' > 0')
			->andWhere($db->quoteName('menu.published') . ' = 1')
			->andWhere($db->quoteName('menu.access') . '!= 1')
			->andWhere('JSON_EXTRACT(' . $db->quoteName('m.params') . ', "$.mod_em_campaign_layout") = ' . $db->quote('default_tchooz'))
			->order($db->quoteName('mm.menuid') . ' ASC');

		$db->setQuery($query, 0, 1);
		$menuId = (int) $db->loadResult();

		if (empty($menuId))
		{
			return \EmundusHelperMenu::getHomepageLink();
		}

		return Route::_('index.php?Itemid=' . $menuId);
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$urlType                = new RadioField(self::URL_TYPE, Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_URL_TYPE'), $this->getUrlTypeOptions());
			$displayKnownUrlRule    = [new DisplayRule($urlType, ConditionOperatorEnum::EQUALS, 'known_url')];
			$displayUrlRule         = [new DisplayRule($urlType, ConditionOperatorEnum::EQUALS, 'url')];
			$displayExternalUrlRule = [new DisplayRule($urlType, ConditionOperatorEnum::EQUALS, 'custom_url')];

			$this->parameters = [
				$urlType,
				(new ChoiceField(self::KNOWN_URL, Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_KNOWN_URLS'), $this->getKnownUrlsOptions()))->setDisplayRules($displayKnownUrlRule),
				(new ChoiceField(self::INTERN_URL, Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_TO_URL'), $this->getMenuOptions()))->setDisplayRules($displayUrlRule),
				(new StringField(self::CUSTOM_URL, Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_TO_CUSTOM_URL')))->setDisplayRules($displayExternalUrlRule),
			];
		}

		return $this->parameters;
	}

	public function getUrlTypeOptions(): array
	{
		return [
			new ChoiceFieldValue(self::KNOWN_URL, Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_KNOWN_URLS')),
			new ChoiceFieldValue(self::INTERN_URL, Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_TO_URL')),
			new ChoiceFieldValue(self::CUSTOM_URL, Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_TO_CUSTOM_URL')),
		];
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	public function getKnownUrlsOptions(): array
	{
		$options = [];

		$applicantGroup = new FieldGroup('applicant_group', Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_KNOWN_URLS_GROUP_APPLICANT'));
		$managersGroup  = new FieldGroup('managers_group', Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_KNOWN_URLS_GROUP_MANAGERS'));

		// Applicant-facing destinations
		$options[] = new ChoiceFieldValue('open_file', Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_KNOWN_URLS_OPEN_FILE'), $applicantGroup);
		$options[] = new ChoiceFieldValue('my_applications', Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_KNOWN_URLS_MY_APPLICATIONS'), $applicantGroup);
		$options[] = new ChoiceFieldValue('campaigns_catalog', Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_KNOWN_URLS_CAMPAIGNS_CATALOG'), $applicantGroup);
		$options[] = new ChoiceFieldValue('home', Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_KNOWN_URLS_HOME'), $applicantGroup);

		// Manager-facing destinations
		$options[] = new ChoiceFieldValue('files_list', Text::_('TCHOOZ_AUTOMATION_ACTION_REDIRECT_KNOWN_URLS_FILES_LIST'), $managersGroup);

		return $options;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getMenuOptions(): array
	{
		$options = [];

		$db    = Factory::getContainer()->get('DatabaseDriver');
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
			$options[] = new ChoiceFieldValue($item->id, Text::_($item->title) . ' (' . $item->path . ')');
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