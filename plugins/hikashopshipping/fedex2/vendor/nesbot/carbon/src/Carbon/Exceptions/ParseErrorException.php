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

class ParseErrorException extends BaseInvalidArgumentException implements InvalidArgumentException
{
    protected $expected;

    protected $actual;

    protected $help;

    public function __construct($expected, $actual, $help = '', $code = 0, Throwable $previous = null)
    {
        $this->expected = $expected;
        $this->actual = $actual;
        $this->help = $help;

        $actual = $actual === '' ? 'data is missing' : "get '$actual'";

        parent::__construct(trim("Format expected $expected but $actual\n$help"), $code, $previous);
    }

    public function getExpected(): string
    {
        return $this->expected;
    }

    public function getActual(): string
    {
        return $this->actual;
    }

    public function getHelp(): string
    {
        return $this->help;
    }
}
