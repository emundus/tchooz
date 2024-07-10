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

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

class FactoryImmutable extends Factory implements ClockInterface
{
    protected $className = CarbonImmutable::class;

    public function now($tz = null): DateTimeImmutable
    {
        $className = $this->className;

        return new $className(null, $tz);
    }
}
