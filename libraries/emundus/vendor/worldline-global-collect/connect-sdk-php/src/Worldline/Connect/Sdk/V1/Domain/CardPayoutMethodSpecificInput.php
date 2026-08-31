<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Domain;

use UnexpectedValueException;

/**
 * @package Worldline\Connect\Sdk\V1\Domain
 */
class CardPayoutMethodSpecificInput extends AbstractPayoutMethodSpecificInput
{
    /**
     * @var Card|null
     */
    public ?Card $card = null;

    /**
     * @var int|null
     */
    public ?int $paymentProductId = null;

    /**
     * @var PayoutRecipient|null
     */
    public ?PayoutRecipient $recipient = null;

    /**
     * @var string|null
     */
    public ?string $token = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->card)) {
            $object->card = $this->card->toObject();
        }
        if (!is_null($this->paymentProductId)) {
            $object->paymentProductId = $this->paymentProductId;
        }
        if (!is_null($this->recipient)) {
            $object->recipient = $this->recipient->toObject();
        }
        if (!is_null($this->token)) {
            $object->token = $this->token;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CardPayoutMethodSpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'card')) {
            if (!is_object($object->card)) {
                throw new UnexpectedValueException('value \'' . print_r($object->card, true) . '\' is not an object');
            }
            $value = new Card();
            $this->card = $value->fromObject($object->card);
        }
        if (property_exists($object, 'paymentProductId')) {
            $this->paymentProductId = $object->paymentProductId;
        }
        if (property_exists($object, 'recipient')) {
            if (!is_object($object->recipient)) {
                throw new UnexpectedValueException('value \'' . print_r($object->recipient, true) . '\' is not an object');
            }
            $value = new PayoutRecipient();
            $this->recipient = $value->fromObject($object->recipient);
        }
        if (property_exists($object, 'token')) {
            $this->token = $object->token;
        }
        return $this;
    }
}
