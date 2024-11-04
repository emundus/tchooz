<?php

namespace GuzzleHttp;

use Psr\Http\Message\MessageInterface;

interface BodySummarizerInterface
{
    public function summarize(MessageInterface $message): ?string;
}
