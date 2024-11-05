<?php

declare(strict_types=1);

namespace GuzzleHttp\Promise;

interface PromisorInterface
{
    public function promise(): PromiseInterface;
}
