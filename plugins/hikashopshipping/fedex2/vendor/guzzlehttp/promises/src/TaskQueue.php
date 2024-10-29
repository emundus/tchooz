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

class TaskQueue implements TaskQueueInterface
{
    private $enableShutdown = true;
    private $queue = [];

    public function __construct($withShutdown = true)
    {
        if ($withShutdown) {
            register_shutdown_function(function () {
                if ($this->enableShutdown) {
                    $err = error_get_last();
                    if (!$err || ($err['type'] ^ E_ERROR)) {
                        $this->run();
                    }
                }
            });
        }
    }

    public function isEmpty()
    {
        return !$this->queue;
    }

    public function add(callable $task)
    {
        $this->queue[] = $task;
    }

    public function run()
    {
        while ($task = array_shift($this->queue)) {

            $task();
        }
    }

    public function disableShutdown()
    {
        $this->enableShutdown = false;
    }
}
