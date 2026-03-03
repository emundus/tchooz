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

		if(!class_exists('EmundusHelperAccess'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
		}
		if(!class_exists('EmundusHelperCache'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
		}

		$app = Factory::getApplication();
		$lang = $app->getLanguage();
		$user = $app->getIdentity();

		$data['shortLang']   = substr($lang->getTag(), 0, 2);
		$data['currentLang'] = $lang->getTag();
		$data['manyLanguages'] = '0';
		$data['defaultLang'] = $data['currentLang'];

		$languages    = LanguageHelper::getLanguages();
		if (count($languages) > 1) {
			$data['manyLanguages'] = '1';
			$data['defaultLang'] = LanguageFactory::getDefaultLanguageCode();
		}

		$data['coordinator_access'] = \EmundusHelperAccess::asCoordinatorAccessLevel($user->id);
		$data['coordinatorAccess'] = $data['coordinator_access'];
		$data['sysadmin_access']    = \EmundusHelperAccess::isAdministrator($user->id);
		$data['sysadminAccess']    = $data['sysadmin_access'];

		$data['hash'] = \EmundusHelperCache::getCurrentGitHash();

		return $data;
	}
}