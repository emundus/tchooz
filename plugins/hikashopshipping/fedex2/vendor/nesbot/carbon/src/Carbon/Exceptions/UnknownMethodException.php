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

use BadMethodCallException as BaseBadMethodCallException;
use Throwable;

class UnknownMethodException extends BaseBadMethodCallException implements BadMethodCallException
{
    protected $method;

    public function __construct($method, $code = 0, Throwable $previous = null)
    {
        $this->method = $method;

        parent::__construct("Method $method does not exist.", $code, $previous);
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
