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

interface PromiseInterface
{
    const PENDING = 'pending';
    const FULFILLED = 'fulfilled';
    const REJECTED = 'rejected';

    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null
    );

    public function otherwise(callable $onRejected);

    public function getState();

    public function resolve($value);

    public function reject($reason);

    public function cancel();

    public function wait($unwrap = true);
}
