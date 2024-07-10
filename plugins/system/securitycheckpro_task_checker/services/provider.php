<?php
/**
 * @Securitycheckpro_task_checker plugin
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\Securitycheckpro_task_checker\Extension\Securitycheckpro_task_checker;

return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   9.0.0
	 */
	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$config     = (array) PluginHelper::getPlugin('system', 'securitycheckpro_task_checker');
				$dispatcher = $container->get(DispatcherInterface::class);

				$plugin = new Securitycheckpro_task_checker(
					$dispatcher,
					$config
				);

				$plugin->setApplication(Factory::getApplication());
				
				return $plugin;
			}
		);
	}
};
