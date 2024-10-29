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

class InvalidDateException extends BaseInvalidArgumentException implements InvalidArgumentException
{
    private $field;

    private $value;

    public function __construct($field, $value, $code = 0, Throwable $previous = null)
    {
        $this->field = $field;
        $this->value = $value;
        parent::__construct($field.' : '.$value.' is not a valid value.', $code, $previous);
    }

    public function getField()
    {
        return $this->field;
    }

    public function getValue()
    {
        return $this->value;
    }
}
