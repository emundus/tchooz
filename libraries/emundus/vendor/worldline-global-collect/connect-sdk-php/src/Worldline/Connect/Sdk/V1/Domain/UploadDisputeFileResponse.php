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
class UploadDisputeFileResponse extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $disputeId = null;

    /**
     * @var string|null
     */
    public ?string $fileId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->disputeId)) {
            $object->disputeId = $this->disputeId;
        }
        if (!is_null($this->fileId)) {
            $object->fileId = $this->fileId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): UploadDisputeFileResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'disputeId')) {
            $this->disputeId = $object->disputeId;
        }
        if (property_exists($object, 'fileId')) {
            $this->fileId = $object->fileId;
        }
        return $this;
    }
}
