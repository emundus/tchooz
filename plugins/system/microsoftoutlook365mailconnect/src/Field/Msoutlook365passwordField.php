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

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\TextField;

class Msoutlook365passwordField extends TextField
{
    protected $type = 'msoutlook365password';

    protected function getInput()
    {
        return str_replace('type="text"', 'type="password"', parent::getInput());
    }
}