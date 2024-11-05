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


namespace Carbon\Traits;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

trait Mutability
{
    use Cast;

    public static function isMutable()
    {
        return false;
    }

    public static function isImmutable()
    {
        return !static::isMutable();
    }

    public function toMutable()
    {

        $date = $this->cast(Carbon::class);

        return $date;
    }

    public function toImmutable()
    {

        $date = $this->cast(CarbonImmutable::class);

        return $date;
    }
}
