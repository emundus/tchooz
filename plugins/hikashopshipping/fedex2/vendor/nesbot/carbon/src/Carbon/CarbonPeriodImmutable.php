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

class CarbonPeriodImmutable extends CarbonPeriod
{
    protected const DEFAULT_DATE_CLASS = CarbonImmutable::class;

    protected $dateClass = CarbonImmutable::class;

    protected function copyIfImmutable()
    {
        return $this->constructed ? clone $this : $this;
    }
}
