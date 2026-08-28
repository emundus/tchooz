<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\Webhooks;

use Worldline\Connect\Sdk\V1\Webhooks\V1WebhooksFactory;

/**
 * Worldline Global Collect platform factory for several webhooks components.
 *
 * @package Worldline\Connect\Sdk\Webhooks
 */
class Webhooks
{
    /**
     * @var V1WebhooksFactory|null
     */
    private static ?V1WebhooksFactory $v1 = null;

    private function __construct()
    {
    }

    public static function v1(): V1WebhooksFactory
    {
        if (is_null(self::$v1)) {
            self::$v1 = new V1WebhooksFactory();
        }
        return self::$v1;
    }
}
