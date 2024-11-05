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

class NotLocaleAwareException extends BaseInvalidArgumentException implements InvalidArgumentException
{
    public function __construct($object, $code = 0, Throwable $previous = null)
    {
        $dump = \is_object($object) ? \get_class($object) : \gettype($object);

        parent::__construct("$dump does neither implements Symfony\Contracts\Translation\LocaleAwareInterface nor getLocale() method.", $code, $previous);
    }
}
