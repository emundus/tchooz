<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Tokens;

use Worldline\Connect\Sdk\Communication\RequestObject;

/**
 * Query parameters for Delete token
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Tokens
 * @link    https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/tokens/delete.html Delete token
 */
class DeleteTokenParams extends RequestObject
{
    /**
     * @var string|null
     */
    public ?string $mandateCancelDate;
}
