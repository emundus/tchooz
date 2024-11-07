<?php


namespace FedexRest\Services;


interface RequestInterface
{
    public function setAccessToken(string $access_token);

    public function setApiEndpoint();

    public function request();
}
