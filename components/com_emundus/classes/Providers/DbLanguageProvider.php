<?php
/**
 * @package     Tchooz\Providers
 * @subpackage
 *
 * @copyright   Copyright (C) 2005-2025 eMundus - All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Tchooz\Providers;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Tchooz\Factories\Language\DbLanguageFactory;

class DbLanguageProvider implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$app = Factory::getApplication();

		if (!$app->isClient('site'))
		{
			return;
		}

		$currentLang = $app->getLanguage();

		$container->alias('language.factory', DbLanguageFactory::class)
			->share(
				DbLanguageFactory::class,
				function (Container $container) {
					return new DbLanguageFactory();
				},
				true
			);

		$lang = $container->get(DbLanguageFactory::class)->createLanguage($currentLang->getTag(), false, $currentLang);

		$app->loadLanguage($lang);
		Factory::$language = $lang;
	}
}

