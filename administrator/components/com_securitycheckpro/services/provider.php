<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license GNU General Public License version 3, or later
 */
 
declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Extension\SecuritycheckProComponent;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $baseNamespace = '\\SecuritycheckExtensions\\Component\\SecuritycheckPro';

        // Providers MVC estándar
        $container->registerServiceProvider(new MVCFactory($baseNamespace));
        $container->registerServiceProvider(new ComponentDispatcherFactory($baseNamespace));
        $container->registerServiceProvider(new RouterFactory($baseNamespace));

        // Componente real
        $container->set(
            ComponentInterface::class,
            static function (Container $container): ComponentInterface {
                // El constructor espera DispatcherFactoryInterface
                $component = new SecuritycheckProComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class)
                );

                // Inyección de servicios MVC
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};