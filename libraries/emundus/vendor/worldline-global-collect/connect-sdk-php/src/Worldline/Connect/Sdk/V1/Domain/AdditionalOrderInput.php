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
class AdditionalOrderInput extends DataObject
{
    /**
     * @var AccountFundingRecipient|null
     */
    public ?AccountFundingRecipient $accountFundingRecipient = null;

    /**
     * @var AirlineData|null
     */
    public ?AirlineData $airlineData = null;

    /**
     * @var Installments|null
     */
    public ?Installments $installments = null;

    /**
     * @var Level3SummaryData|null
     *
     * @deprecated Use Order.shoppingCart.amountBreakdown instead
     */
    public ?Level3SummaryData $level3SummaryData = null;

    /**
     * @var LoanRecipient|null
     *
     * @deprecated No replacement
     */
    public ?LoanRecipient $loanRecipient = null;

    /**
     * @var LodgingData|null
     */
    public ?LodgingData $lodgingData = null;

    /**
     * @var int|null
     *
     * @deprecated Use installments.numberOfInstallments instead
     */
    public ?int $numberOfInstallments = null;

    /**
     * @var string|null
     */
    public ?string $orderDate = null;

    /**
     * @var OrderTypeInformation|null
     */
    public ?OrderTypeInformation $typeInformation = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->accountFundingRecipient)) {
            $object->accountFundingRecipient = $this->accountFundingRecipient->toObject();
        }
        if (!is_null($this->airlineData)) {
            $object->airlineData = $this->airlineData->toObject();
        }
        if (!is_null($this->installments)) {
            $object->installments = $this->installments->toObject();
        }
        if (!is_null($this->level3SummaryData)) {
            $object->level3SummaryData = $this->level3SummaryData->toObject();
        }
        if (!is_null($this->loanRecipient)) {
            $object->loanRecipient = $this->loanRecipient->toObject();
        }
        if (!is_null($this->lodgingData)) {
            $object->lodgingData = $this->lodgingData->toObject();
        }
        if (!is_null($this->numberOfInstallments)) {
            $object->numberOfInstallments = $this->numberOfInstallments;
        }
        if (!is_null($this->orderDate)) {
            $object->orderDate = $this->orderDate;
        }
        if (!is_null($this->typeInformation)) {
            $object->typeInformation = $this->typeInformation->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): AdditionalOrderInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'accountFundingRecipient')) {
            if (!is_object($object->accountFundingRecipient)) {
                throw new UnexpectedValueException('value \'' . print_r($object->accountFundingRecipient, true) . '\' is not an object');
            }
            $value = new AccountFundingRecipient();
            $this->accountFundingRecipient = $value->fromObject($object->accountFundingRecipient);
        }
        if (property_exists($object, 'airlineData')) {
            if (!is_object($object->airlineData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->airlineData, true) . '\' is not an object');
            }
            $value = new AirlineData();
            $this->airlineData = $value->fromObject($object->airlineData);
        }
        if (property_exists($object, 'installments')) {
            if (!is_object($object->installments)) {
                throw new UnexpectedValueException('value \'' . print_r($object->installments, true) . '\' is not an object');
            }
            $value = new Installments();
            $this->installments = $value->fromObject($object->installments);
        }
        if (property_exists($object, 'level3SummaryData')) {
            if (!is_object($object->level3SummaryData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->level3SummaryData, true) . '\' is not an object');
            }
            $value = new Level3SummaryData();
            $this->level3SummaryData = $value->fromObject($object->level3SummaryData);
        }
        if (property_exists($object, 'loanRecipient')) {
            if (!is_object($object->loanRecipient)) {
                throw new UnexpectedValueException('value \'' . print_r($object->loanRecipient, true) . '\' is not an object');
            }
            $value = new LoanRecipient();
            $this->loanRecipient = $value->fromObject($object->loanRecipient);
        }
        if (property_exists($object, 'lodgingData')) {
            if (!is_object($object->lodgingData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->lodgingData, true) . '\' is not an object');
            }
            $value = new LodgingData();
            $this->lodgingData = $value->fromObject($object->lodgingData);
        }
        if (property_exists($object, 'numberOfInstallments')) {
            $this->numberOfInstallments = $object->numberOfInstallments;
        }
        if (property_exists($object, 'orderDate')) {
            $this->orderDate = $object->orderDate;
        }
        if (property_exists($object, 'typeInformation')) {
            if (!is_object($object->typeInformation)) {
                throw new UnexpectedValueException('value \'' . print_r($object->typeInformation, true) . '\' is not an object');
            }
            $value = new OrderTypeInformation();
            $this->typeInformation = $value->fromObject($object->typeInformation);
        }
        return $this;
    }
}
