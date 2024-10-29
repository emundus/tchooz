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

namespace GuzzleHttp\Promise;

final class Is
{
    public static function pending(PromiseInterface $promise)
    {
        return $promise->getState() === PromiseInterface::PENDING;
    }

    public static function settled(PromiseInterface $promise)
    {
        return $promise->getState() !== PromiseInterface::PENDING;
    }

    public static function fulfilled(PromiseInterface $promise)
    {
        return $promise->getState() === PromiseInterface::FULFILLED;
    }

    public static function rejected(PromiseInterface $promise)
    {
        return $promise->getState() === PromiseInterface::REJECTED;
    }
}
