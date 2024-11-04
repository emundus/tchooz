<?php

namespace GuzzleHttp;

use Psr\Http\Message\MessageInterface;

final class BodySummarizer implements BodySummarizerInterface
{
    private $truncateAt;

    public function __construct(?int $truncateAt = null)
    {
        $this->truncateAt = $truncateAt;
    }

    public function summarize(MessageInterface $message): ?string
    {
        return $this->truncateAt === null
            ? Psr7\Message::bodySummary($message)
            : Psr7\Message::bodySummary($message, $this->truncateAt);
    }
}
