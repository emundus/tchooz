<?php

namespace Psr\Http\Message;

interface UploadedFileInterface
{
    public function getStream(): StreamInterface;

    public function moveTo(string $targetPath): void;

    public function getSize(): ?int;

    public function getError(): int;

    public function getClientFilename(): ?string;

    public function getClientMediaType(): ?string;
}
