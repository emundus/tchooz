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

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Controller\Administrator;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

abstract class AbstractAdministratorController
{
    /** @var Factory */
    protected $application;

    public function __construct()
    {
        $this->application = Factory::getApplication();
        $this->init();
    }

    /**
     * Initializes the necessary parts or settings for the current instance.
     *
     * @return void
     */
    public function init(): void
    {
    }

    public function handle(string $action): void
    {
        if ($this->application->isClient('administrator') === true) {
            $methodName = 'action' . str_replace(' ', '', ucwords(str_replace('-', ' ', $action)));
            if (method_exists($this, $methodName)) {
                $this->$methodName();
                $this->application->close();
            }
        }
    }

}