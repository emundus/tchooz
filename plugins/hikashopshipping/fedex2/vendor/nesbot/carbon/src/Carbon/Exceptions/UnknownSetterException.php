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

class UnknownSetterException extends BaseInvalidArgumentException implements BadMethodCallException
{
    protected $setter;

    public function __construct($setter, $code = 0, Throwable $previous = null)
    {
        $this->setter = $setter;

        parent::__construct("Unknown setter '$setter'", $code, $previous);
    }

    public function getSetter(): string
    {
        return $this->setter;
    }
}
