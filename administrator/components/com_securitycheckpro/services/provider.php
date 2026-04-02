<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The banners service provider.
 *
 * @since  9.0.0
 */
return new class implements ServiceProviderInterface {
	
	/**
     * @@param Container $container
     */
	public function register(Container $container): void {
        $container->registerServiceProvider(new MVCFactory('\\SecuritycheckExtensions\\Component\\SecuritycheckPro'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\SecuritycheckExtensions\\Component\\SecuritycheckPro'));
		
        $container->set(
				ComponentInterface::class,
				/**
				 * @param Container $container
				 */
				function (Container $container)
				{
					$component = new MVCComponent($container->get(ComponentDispatcherFactoryInterface::class));
					$component->setMVCFactory($container->get(MVCFactoryInterface::class));

					return $component;
		}
		);
    }
};
