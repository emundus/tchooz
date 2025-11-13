<?php

/**
 * @package     Emundus.Plugin
 * @subpackage  System.emundus
 *
 * @copyright   Copyright (C) 2005-2025 eMundus - All rights reserved.
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
use Joomla\Plugin\System\EmundusAnalytics\Extension\EmundusAnalytics;

return new class () implements ServiceProviderInterface {

    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin     = new EmundusAnalytics(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'emundus_analytics')
                );
                $plugin->setApplication(Factory::getApplication());
	            $plugin->setDatabase($container->get(DatabaseInterface::class));
	            $plugin->setUserFactory($container->get(UserFactoryInterface::class));

                return $plugin;
            }
        );
    }
};
