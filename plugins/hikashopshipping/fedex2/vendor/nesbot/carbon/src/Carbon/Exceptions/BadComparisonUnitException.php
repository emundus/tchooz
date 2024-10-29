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


namespace Carbon\Exceptions;

use Throwable;

class BadComparisonUnitException extends UnitException
{
    protected $unit;

    public function __construct($unit, $code = 0, Throwable $previous = null)
    {
        $this->unit = $unit;

        parent::__construct("Bad comparison unit: '$unit'", $code, $previous);
    }

    public function getUnit(): string
    {
        return $this->unit;
    }
}
