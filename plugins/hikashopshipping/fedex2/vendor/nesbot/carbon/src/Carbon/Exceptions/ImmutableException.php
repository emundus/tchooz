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

use RuntimeException as BaseRuntimeException;
use Throwable;

class ImmutableException extends BaseRuntimeException implements RuntimeException
{
    protected $value;

    public function __construct($value, $code = 0, Throwable $previous = null)
    {
        $this->value = $value;
        parent::__construct("$value is immutable.", $code, $previous);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
