<?php

declare(strict_types=1);

namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

final class PumpStream implements StreamInterface
{

    private $source;


    private $size;


    private $tellPos = 0;


    private $metadata;


    private $buffer;

    public function __construct(callable $source, array $options = [])
    {
        $this->source = $source;
        $this->size = $options['size'] ?? null;
        $this->metadata = $options['metadata'] ?? [];
        $this->buffer = new BufferStream();
    }

    public function __toString(): string
    {
        try {
            return Utils::copyToString($this);
        } catch (\Throwable $e) {
            if (\PHP_VERSION_ID >= 70400) {
                throw $e;
            }
            trigger_error(sprintf('%s::__toString exception: %s', self::class, (string) $e), E_USER_ERROR);

            return '';
        }
    }

    public function close(): void
    {
        $this->detach();
    }

    public function detach()
    {
        $this->tellPos = 0;
        $this->source = null;

        return null;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function tell(): int
    {
        return $this->tellPos;
    }

    public function eof(): bool
    {
        return $this->source === null;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        throw new \RuntimeException('Cannot seek a PumpStream');
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): int
    {
        throw new \RuntimeException('Cannot write to a PumpStream');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        $data = $this->buffer->read($length);
        $readLen = strlen($data);
        $this->tellPos += $readLen;
        $remaining = $length - $readLen;

        if ($remaining) {
            $this->pump($remaining);
            $data .= $this->buffer->read($remaining);
            $this->tellPos += strlen($data) - $readLen;
        }

        return $data;
    }

    public function getContents(): string
    {
        $result = '';
        while (!$this->eof()) {
            $result .= $this->read(1000000);
        }

        return $result;
    }

    public function getMetadata($key = null)
    {
        if (!$key) {
            return $this->metadata;
        }

        return $this->metadata[$key] ?? null;
    }

    private function pump(int $length): void
    {
        if ($this->source !== null) {
            do {
                $data = ($this->source)($length);
                if ($data === false || $data === null) {
                    $this->source = null;

                    return;
                }
                $this->buffer->write($data);
                $length -= strlen($data);
            } while ($length > 0);
        }
    }
}
