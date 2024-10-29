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

class BadFluentConstructorException extends BaseBadMethodCallException implements BadMethodCallException
{
    protected $method;

    public function __construct($method, $code = 0, Throwable $previous = null)
    {
        $this->method = $method;

        parent::__construct(sprintf("Unknown fluent constructor '%s'.", $method), $code, $previous);
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
