<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php


namespace Carbon;

use Carbon\Traits\Date;
use Carbon\Traits\DeprecatedProperties;
use DateTime;
use DateTimeInterface;
use DateTimeZone;

class Carbon extends DateTime implements CarbonInterface
{
    use Date;

    public static function isMutable()
    {
        return true;
    }
}
