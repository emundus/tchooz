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

use Web357\Plugin\System\Microsoftoutlook365mailconnect\Interfaces\MailFactory;
use Joomla\Registry\Registry;

class MicrosoftOutlook365MailConnectMailerFactory implements MailFactory
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function createMailer(?Registry $settings = null): MicrosoftOutlook365MailConnectMailer
    {
        $configuration = new Registry($this->config);

        if ($settings) {
            $configuration->merge($settings);
        }

        return new MicrosoftOutlook365MailConnectMailer((bool)$configuration->get('throw_exceptions', true));
    }
}