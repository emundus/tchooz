<?php
/* ======================================================
 # Microsoft/Outlook 365 Mail Connect for Joomla! - v1.0.8 (pro version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2024 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html
 # Website: https://www.web357.com
 # Demo: 
 # Support: support@web357.com
 # Last modified: Tuesday 03 February 2026, 10:20:16 AM
 ========================================================= */

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Extension\Microsoftoutlook365mailconnect;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Subscriber\Microsoftoutlook365mailconnectMailerMaxPrioritySubscriber;

return new class() implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $config = (array)PluginHelper::getPlugin('system', 'microsoftoutlook365mailconnect');
                /** @var \Joomla\Event\Dispatcher $subject */
                $subject = $container->get(DispatcherInterface::class);
                $subject->addSubscriber(new Microsoftoutlook365mailconnectMailerMaxPrioritySubscriber());
                $app = Factory::getApplication();
                $plugin = new Microsoftoutlook365mailconnect($subject, $config);
                $plugin->setApplication($app);
                return $plugin;
            }
        );
    }
};