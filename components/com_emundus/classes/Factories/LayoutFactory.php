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
use Tchooz\Factories\Language\LanguageFactory;

class LayoutFactory
{
	public static function prepareVueData(): array
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
		$user = $app->getIdentity();

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

		$data['coordinatorAccess'] = \EmundusHelperAccess::asCoordinatorAccessLevel($user->id);
		$data['sysadminAccess']    = \EmundusHelperAccess::isAdministrator($user->id);

		// To manage the camelCase and snake_case in the Vue component, we duplicate the values with both keys.
		$data['short_lang']         = $data['shortLang'];
		$data['current_lang']       = $data['currentLang'];
		$data['many_languages']     = $data['manyLanguages'];
		$data['default_lang']       = $data['defaultLang'];
		$data['coordinator_access'] = $data['coordinatorAccess'];
		$data['sysadmin_access']    = $data['sysadminAccess'];

		$data['hash'] = \EmundusHelperCache::getCurrentGitHash();

		return $data;
	}
}