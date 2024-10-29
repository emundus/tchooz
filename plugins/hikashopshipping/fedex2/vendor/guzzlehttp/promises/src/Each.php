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

final class Each
{
    public static function of(
        $iterable,
        callable $onFulfilled = null,
        callable $onRejected = null
    ) {
        return (new EachPromise($iterable, [
            'fulfilled' => $onFulfilled,
            'rejected'  => $onRejected
        ]))->promise();
    }

    public static function ofLimit(
        $iterable,
        $concurrency,
        callable $onFulfilled = null,
        callable $onRejected = null
    ) {
        return (new EachPromise($iterable, [
            'fulfilled'   => $onFulfilled,
            'rejected'    => $onRejected,
            'concurrency' => $concurrency
        ]))->promise();
    }

    public static function ofLimitAll(
        $iterable,
        $concurrency,
        callable $onFulfilled = null
    ) {
        return each_limit(
            $iterable,
            $concurrency,
            $onFulfilled,
            function ($reason, $idx, PromiseInterface $aggregate) {
                $aggregate->reject($reason);
            }
        );
    }
}
