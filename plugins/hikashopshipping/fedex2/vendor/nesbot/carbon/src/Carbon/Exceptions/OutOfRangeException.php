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

use InvalidArgumentException as BaseInvalidArgumentException;
use Throwable;


class OutOfRangeException extends BaseInvalidArgumentException implements InvalidArgumentException
{
    private $unit;

    private $min;

    private $max;

    private $value;

    public function __construct($unit, $min, $max, $value, $code = 0, Throwable $previous = null)
    {
        $this->unit = $unit;
        $this->min = $min;
        $this->max = $max;
        $this->value = $value;

        parent::__construct("$unit must be between $min and $max, $value given", $code, $previous);
    }

    public function getMax()
    {
        return $this->max;
    }

    public function getMin()
    {
        return $this->min;
    }

    public function getUnit()
    {
        return $this->unit;
    }

    public function getValue()
    {
        return $this->value;
    }
}
