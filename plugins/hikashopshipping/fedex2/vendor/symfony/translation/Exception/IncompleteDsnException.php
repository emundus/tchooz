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


namespace Symfony\Component\Translation\Exception;

class IncompleteDsnException extends InvalidArgumentException
{
    public function __construct(string $message, ?string $dsn = null, ?\Throwable $previous = null)
    {
        if ($dsn) {
            $message = sprintf('Invalid "%s" provider DSN: ', $dsn).$message;
        }

        parent::__construct($message, 0, $previous);
    }
}
