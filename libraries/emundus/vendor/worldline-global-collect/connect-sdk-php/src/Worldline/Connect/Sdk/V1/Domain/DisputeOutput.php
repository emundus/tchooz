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
class DisputeOutput extends DataObject
{
    /**
     * @var AmountOfMoney|null
     */
    public ?AmountOfMoney $amountOfMoney = null;

    /**
     * @var string|null
     */
    public ?string $contactPerson = null;

    /**
     * @var DisputeCreationDetail|null
     */
    public ?DisputeCreationDetail $creationDetails = null;

    /**
     * @var string|null
     */
    public ?string $emailAddress = null;

    /**
     * @var HostedFile[]|null
     */
    public ?array $files = null;

    /**
     * @var DisputeReference|null
     */
    public ?DisputeReference $reference = null;

    /**
     * @var string|null
     */
    public ?string $replyTo = null;

    /**
     * @var string|null
     */
    public ?string $requestMessage = null;

    /**
     * @var string|null
     */
    public ?string $responseMessage = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->amountOfMoney)) {
            $object->amountOfMoney = $this->amountOfMoney->toObject();
        }
        if (!is_null($this->contactPerson)) {
            $object->contactPerson = $this->contactPerson;
        }
        if (!is_null($this->creationDetails)) {
            $object->creationDetails = $this->creationDetails->toObject();
        }
        if (!is_null($this->emailAddress)) {
            $object->emailAddress = $this->emailAddress;
        }
        if (!is_null($this->files)) {
            $object->files = [];
            foreach ($this->files as $element) {
                if (!is_null($element)) {
                    $object->files[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->reference)) {
            $object->reference = $this->reference->toObject();
        }
        if (!is_null($this->replyTo)) {
            $object->replyTo = $this->replyTo;
        }
        if (!is_null($this->requestMessage)) {
            $object->requestMessage = $this->requestMessage;
        }
        if (!is_null($this->responseMessage)) {
            $object->responseMessage = $this->responseMessage;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): DisputeOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'amountOfMoney')) {
            if (!is_object($object->amountOfMoney)) {
                throw new UnexpectedValueException('value \'' . print_r($object->amountOfMoney, true) . '\' is not an object');
            }
            $value = new AmountOfMoney();
            $this->amountOfMoney = $value->fromObject($object->amountOfMoney);
        }
        if (property_exists($object, 'contactPerson')) {
            $this->contactPerson = $object->contactPerson;
        }
        if (property_exists($object, 'creationDetails')) {
            if (!is_object($object->creationDetails)) {
                throw new UnexpectedValueException('value \'' . print_r($object->creationDetails, true) . '\' is not an object');
            }
            $value = new DisputeCreationDetail();
            $this->creationDetails = $value->fromObject($object->creationDetails);
        }
        if (property_exists($object, 'emailAddress')) {
            $this->emailAddress = $object->emailAddress;
        }
        if (property_exists($object, 'files')) {
            if (!is_array($object->files) && !is_object($object->files)) {
                throw new UnexpectedValueException('value \'' . print_r($object->files, true) . '\' is not an array or object');
            }
            $this->files = [];
            foreach ($object->files as $element) {
                $value = new HostedFile();
                $this->files[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'reference')) {
            if (!is_object($object->reference)) {
                throw new UnexpectedValueException('value \'' . print_r($object->reference, true) . '\' is not an object');
            }
            $value = new DisputeReference();
            $this->reference = $value->fromObject($object->reference);
        }
        if (property_exists($object, 'replyTo')) {
            $this->replyTo = $object->replyTo;
        }
        if (property_exists($object, 'requestMessage')) {
            $this->requestMessage = $object->requestMessage;
        }
        if (property_exists($object, 'responseMessage')) {
            $this->responseMessage = $object->responseMessage;
        }
        return $this;
    }
}
