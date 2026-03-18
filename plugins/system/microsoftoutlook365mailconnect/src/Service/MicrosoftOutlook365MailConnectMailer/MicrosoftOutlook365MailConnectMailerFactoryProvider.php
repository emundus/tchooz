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

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Service\MicrosoftOutlook365MailConnectMailer;

defined('_JEXEC') or die;

use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

class MicrosoftOutlook365MailConnectMailerFactoryProvider implements ServiceProviderInterface
{

    public function register(Container $container)
    {
        if ($container->has(MailerFactoryInterface::class)) {
            $fallbackMailer = $container->get(MailerFactoryInterface::class);
            $container->set(MicrosoftOutlook365MailConnectMailer::MICROSOFT_FALLBACK_MAILER_SERVICE, $fallbackMailer);
        }
        $container->alias(MailerFactoryInterface::class, MicrosoftOutlook365MailConnectMailerFactory::class)
            ->share(
                MicrosoftOutlook365MailConnectMailerFactory::class,
                function (Container $container) {
                    return new MicrosoftOutlook365MailConnectMailerFactory($container->get('config'));
                },
                true
            );
    }
}