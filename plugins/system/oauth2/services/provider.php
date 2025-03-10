<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Authentication.joomla
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\Oauth2\Extension\Oauth2;

return new class () implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.3.0
	 */
	public function register(Container $container)
	{
		if((Joomla\CMS\Factory::getApplication() instanceof Joomla\CMS\Application\WebApplication))
		{
			$container->set(
				PluginInterface::class,
				function (Container $container) {
					$plugin = new Oauth2(
						$container->get(DispatcherInterface::class),
						(array) PluginHelper::getPlugin('system', 'oauth2')
					);
					$plugin->setApplication(Factory::getApplication());

					return $plugin;
				}
			);
		}
	}
};
