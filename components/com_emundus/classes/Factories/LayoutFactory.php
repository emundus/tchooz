<?php
/**
 * @package     Tchooz\Factories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Tchooz\Factories\Language\LanguageFactory;

class LayoutFactory
{
	public static function prepareVueData(User $user = null): array
	{
		$data = [];

		if (!class_exists('EmundusHelperAccess'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
		}
		if (!class_exists('EmundusHelperCache'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
		}

		$app  = Factory::getApplication();
		$lang = $app->getLanguage();
		if (empty($user))
		{
			$user = $app->getIdentity();
		}

		$data['shortLang']     = substr($lang->getTag(), 0, 2);
		$data['currentLang']   = $lang->getTag();
		$data['manyLanguages'] = '0';
		$data['defaultLang']   = $data['currentLang'];

		$languages = LanguageHelper::getLanguages();
		if (count($languages) > 1)
		{
			$data['manyLanguages'] = '1';
			$data['defaultLang']   = LanguageFactory::getDefaultLanguageCode();
		}

		$data['coordinatorAccess'] = static::checkCoordinatorAccess($user->id);
		$data['sysadminAccess']    = static::checkSysadminAccess($user->id);

		// To manage the camelCase and snake_case in the Vue component, we duplicate the values with both keys.
		$data['short_lang']         = $data['shortLang'];
		$data['current_lang']       = $data['currentLang'];
		$data['many_languages']     = $data['manyLanguages'];
		$data['default_lang']       = $data['defaultLang'];
		$data['coordinator_access'] = $data['coordinatorAccess'];
		$data['sysadmin_access']    = $data['sysadminAccess'];

		$data['hash'] = static::getCurrentGitHash();

		return $data;
	}

	/**
	 * @codeCoverageIgnore Because it is a simple wrapper around EmundusHelperCache::getCurrentGitHash, which is already covered by unit tests.
	 */
	protected static function getCurrentGitHash(): string
	{
		return \EmundusHelperCache::getCurrentGitHash();
	}

	/**
	 * @param   string  $title
	 * @param   string  $subtitle
	 * @param   string  $noDataMessage
	 * @param   array   $objects  ['id', 'label', 'menuLnk']  $objects
	 *
	 * @return array['shortTags' => string, 'longTags' => string]
	 */
	public static function buildLongLayout(
		string $title,
		string $subtitle,
		string $noDataMessage,
		array  $objects
	): array
	{
		if (!empty($objects))
		{
			if (count($objects) > 1)
			{
				$tags       = '<div>';
				$short_tags = $tags;
				$tags       .= '<h2 class="tw-mb-8 tw-text-center">' . Text::_($title) . '</h2>';
				$tags       .= '<div class="tw-flex tw-flex-wrap">';
				foreach ($objects as $object)
				{
					if (!empty($object->menuLink))
					{
						$tags .= '<a href="' . static::resolveMenuLink($object->menuLink) . '" class="tw-cursor-pointer tw-mr-2 tw-mb-2 tw-h-max tw-px-3 tw-py-1 tw-font-semibold hover:tw-font-semibold tw-bg-main-100 tw-text-neutral-900 tw-text-sm tw-rounded-coordinator em-campaign-tag"> ' . $object->label . '</a>';
					}
					else
					{
						$tags .= '<span class="tw-mr-2 tw-mb-2 tw-h-max tw-px-3 tw-py-1 tw-font-semibold tw-bg-main-100 tw-text-neutral-900 tw-text-sm tw-rounded-coordinator em-campaign-tag"> ' . $object->label . '</span>';
					}
				}
				$tags .= '</div>';

				$short_tags .= '<span class="tw-w-fit tw-cursor-pointer tw-text-profile-full tw-flex tw-items-center tw-justify-center tw-text-sm hover:!tw-underline tw-font-semibold">' . Text::sprintf($subtitle, count($objects)) . '</span>';
				$short_tags .= '</div>';
				$tags       .= '</div>';
			}
			else
			{
				if (!empty($objects[0]->menuLink))
				{
					$short_tags = '<a href="' . static::resolveMenuLink($objects[0]->menuLink) . '" class="tw-cursor-pointer tw-mr-2 tw-mb-2 tw-h-max tw-font-semibold hover:tw-font-semibold hover:tw-underline tw-text-neutral-900 tw-text-sm em-campaign-tag"> ' . $objects[0]->label . '</a>';
				}
				else
				{
					$short_tags = '<span class="tw-mr-2 tw-mb-2 tw-h-max tw-font-semibold tw-text-neutral-900 tw-text-sm em-campaign-tag"> ' . $objects[0]->label . '</span>';
				}
			}
		}
		else
		{
			$short_tags = Text::_($noDataMessage);
		}

		return [
			'shortTags' => $short_tags ?? '',
			'longTags'  => $tags ?? null
		];
	}

	/**
	 * @codeCoverageIgnore Because it is a simple wrapper around EmundusHelperAccess::asCoordinatorAccessLevel and EmundusHelperAccess::isAdministrator, which are already covered by unit tests.
	 */
	protected static function checkCoordinatorAccess(int $userId): bool
	{
		return \EmundusHelperAccess::asCoordinatorAccessLevel($userId);
	}

	/**
	 * @codeCoverageIgnore Because it is a simple wrapper around EmundusHelperAccess::asCoordinatorAccessLevel and EmundusHelperAccess::isAdministrator, which are already covered by unit tests.
	 */
	protected static function checkSysadminAccess(int $userId): bool
	{
		return \EmundusHelperAccess::isAdministrator($userId);
	}

	/**
	 * @codeCoverageIgnore Because it is a simple wrapper around EmundusHelperMenu::routeViaLink, which is already covered by unit tests.
	 */
	protected static function resolveMenuLink(string $link): string
	{
		return \EmundusHelperMenu::routeViaLink($link);
	}
}