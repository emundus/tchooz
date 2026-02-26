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

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Subscriber;

use Joomla\CMS\Factory;
use Joomla\Event\Priority;
use Joomla\Event\SubscriberInterface;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Helper\MicrosoftOutlookApplicationHelper;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Service\MicrosoftOutlook365MailConnectMailer\MicrosoftOutlook365MailConnectMailerFactoryProvider;

class Microsoftoutlook365mailconnectMailerMaxPrioritySubscriber implements SubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterInitialise' => ['registerMailer', Priority::MAX],
        ];
    }

    public function registerMailer(): void
    {
        if (MicrosoftOutlookApplicationHelper::getInstance()->isAuthorized()) {
            Factory::getContainer()->registerServiceProvider(new MicrosoftOutlook365MailConnectMailerFactoryProvider());
        }
    }
}