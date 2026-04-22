<?php

/* ======================================================
 # Microsoft/Outlook 365 Mail Connect for Joomla! - v1.0.9 (pro version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x, v5.x, v6.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2026 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html   
 # Website: https://www.web357.com
 # Demo: 
 # Support: support@web357.com
 # Last modified: Tuesday 14 April 2026, 10:47:44 AM
 ========================================================= */
declare(strict_types=1);

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Service\MicrosoftOutlook365MailConnectMailer;

defined('_JEXEC') or die;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Interfaces\MailFactory;

class MicrosoftOutlook365MailConnectMailerFactoryProvider implements ServiceProviderInterface
{

    public function register(Container $container)
    {
        $mailerFactoryKey = interface_exists('Joomla\CMS\Mail\MailerFactoryInterface') ? 'Joomla\CMS\Mail\MailerFactoryInterface' : 'mailer.factory';
        if ($container->has($mailerFactoryKey)) {
            $fallbackMailer = $container->get($mailerFactoryKey);
            $container->set(MicrosoftOutlook365MailConnectMailer::MICROSOFT_FALLBACK_MAILER_SERVICE, $fallbackMailer);
            $container->alias($mailerFactoryKey, MicrosoftOutlook365MailConnectMailerFactory::class)
                ->share(
                    MicrosoftOutlook365MailConnectMailerFactory::class,
                    function (Container $container) {
                        return new MicrosoftOutlook365MailConnectMailerFactory($container->get('config'));
                    },
                    true
                );
        } else {
            $container->set(MailFactory::class, function (Container $container) {
                return new MicrosoftOutlook365MailConnectMailerFactory($container->get('config'));
            }, true);
        }
    }
}