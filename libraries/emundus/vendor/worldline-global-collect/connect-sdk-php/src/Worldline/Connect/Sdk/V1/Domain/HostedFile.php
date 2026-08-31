<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Domain;

use UnexpectedValueException;
use Worldline\Connect\Sdk\Domain\DataObject;

/**
 * @package Worldline\Connect\Sdk\V1\Domain
 */
class HostedFile extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $fileName = null;

    /**
     * @var string|null
     */
    public ?string $fileSize = null;

    /**
     * @var string|null
     */
    public ?string $fileType = null;

    /**
     * @var string|null
     */
    public ?string $id = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->fileName)) {
            $object->fileName = $this->fileName;
        }
        if (!is_null($this->fileSize)) {
            $object->fileSize = $this->fileSize;
        }
        if (!is_null($this->fileType)) {
            $object->fileType = $this->fileType;
        }
        if (!is_null($this->id)) {
            $object->id = $this->id;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): HostedFile
    {
        parent::fromObject($object);
        if (property_exists($object, 'fileName')) {
            $this->fileName = $object->fileName;
        }
        if (property_exists($object, 'fileSize')) {
            $this->fileSize = $object->fileSize;
        }
        if (property_exists($object, 'fileType')) {
            $this->fileType = $object->fileType;
        }
        if (property_exists($object, 'id')) {
            $this->id = $object->id;
        }
        return $this;
    }
}
