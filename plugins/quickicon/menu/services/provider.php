<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Quickicon.eos
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Quickicon\Menu\Extension\Menu;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since  4.4.0
     *
     * @throws Exception
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin     = new Menu(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('quickicon', 'menu')
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get(DatabaseInterface::class));

                return $plugin;
            }
        );
    }
};
