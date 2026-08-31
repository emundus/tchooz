<?php
namespace Worldline\Connect\Sdk\TestUtil;

use Worldline\Connect\Sdk\BodyHandler;

class AppendingBodyHandler extends BodyHandler
{
    /** @var string */
    private string $body = '';

    protected function initialize(array $headers): void
    {
        $this->body = '';
    }

    protected function doHandleBodyPart(string $bodyPart): void
    {
        $this->body .= $bodyPart;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
