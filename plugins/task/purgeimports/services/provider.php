<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Task.purgeimports
 *
 * @copyright   (C) 2024 eMundus
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Task\PurgeImports\Extension\PurgeImports;

return new class () implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 * @since   5.0.0
	 */
	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$executor = new PurgeImports(
					$container->get(DispatcherInterface::class),
					(array) PluginHelper::getPlugin('task', 'purgeimports')
				);
				$executor->setDatabase($container->get(DatabaseInterface::class));

				return $executor;
			}
		);
	}
};
