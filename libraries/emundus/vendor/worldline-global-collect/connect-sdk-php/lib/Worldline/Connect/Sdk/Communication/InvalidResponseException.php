<?php
namespace Worldline\Connect\Sdk\Communication;

use RuntimeException;

/**
 * Class InvalidResponseException
 *
 * @package Worldline\Connect\Sdk\Communication
 */
class InvalidResponseException extends RuntimeException
{
    /**
     * @var ConnectionResponse
     */
    private ConnectionResponse $response;

    /**
     * @param ConnectionResponse $response
     * @param string|null        $message
     */
    public function __construct(ConnectionResponse $response, ?string $message = null)
    {
        if (is_null($message)) {
            $message = 'The server returned an invalid response.';
        }
        parent::__construct($message);
        $this->response = $response;
    }

    /**
     * @return ConnectionResponse
     */
    public function getResponse(): ConnectionResponse
    {
        return $this->response;
    }
}
