<?php
declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Task\Securitycheckprocron\Extension\Securitycheckprocron;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container): PluginInterface {
                /** @var array<string,mixed> $config */
                $config = (array) PluginHelper::getPlugin('task', 'securitycheckprocron');

                return new Securitycheckprocron(
                    $container->get(DispatcherInterface::class),
                    $config
                );
            }
        );
    }
};