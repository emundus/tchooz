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

class UnknownGetterException extends BaseInvalidArgumentException implements InvalidArgumentException
{
    protected $getter;

    public function __construct($getter, $code = 0, Throwable $previous = null)
    {
        $this->getter = $getter;

        parent::__construct("Unknown getter '$getter'", $code, $previous);
    }

    public function getGetter(): string
    {
        return $this->getter;
    }
}
