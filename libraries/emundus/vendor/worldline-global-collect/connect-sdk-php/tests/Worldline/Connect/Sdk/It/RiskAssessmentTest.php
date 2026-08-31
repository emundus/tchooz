<?php
namespace Worldline\Connect\Sdk\It;

use Worldline\Connect\Sdk\ClientTestCase;
use Worldline\Connect\Sdk\V1\ApiException;
use Worldline\Connect\Sdk\V1\Domain\Address;
use Worldline\Connect\Sdk\V1\Domain\AmountOfMoney;
use Worldline\Connect\Sdk\V1\Domain\BankAccountBban;
use Worldline\Connect\Sdk\V1\Domain\CustomerRiskAssessment;
use Worldline\Connect\Sdk\V1\Domain\OrderRiskAssessment;
use Worldline\Connect\Sdk\V1\Domain\RiskAssessmentBankAccount;

/**
 * @group integration
 *
 */
class RiskAssessmentTest extends ClientTestCase
{
    /**
     * @throws ApiException
     */
    public function testRiskAssessBankAccount()
    {
        $this->markTestSkipped("Risk assessments are not available for pre-prod sandbox accounts");

        // @phpstan-ignore deadCode.unreachable
        $client = $this->getClient();
        $merchantId = $this->getMerchantId();

        $riskAssessmentBankAccount = new RiskAssessmentBankAccount();

        $bankAccountBban = new BankAccountBban();
        $bankAccountBban->countryCode = "DE";
        $bankAccountBban->accountNumber = "0532013000";
        $bankAccountBban->bankCode = "37040044";
        $riskAssessmentBankAccount->bankAccountBban = $bankAccountBban;

        $riskAssessmentOrder = new OrderRiskAssessment();

        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->amount = 100;
        $amountOfMoney->currencyCode = "EUR";
        $riskAssessmentOrder->amountOfMoney = $amountOfMoney;

        $customer = new CustomerRiskAssessment();
        $customer->locale = "en";
        $customerBillingAddress = new Address();
        $customerBillingAddress->countryCode = 'NL';
        $customer->billingAddress = $customerBillingAddress;
        $riskAssessmentOrder->customer = $customer;

        $riskAssessmentBankAccount->order = $riskAssessmentOrder;

        $response = $client->v1()->merchant($merchantId)->riskassessments()->bankaccounts($riskAssessmentBankAccount);
        $this->assertNotEmpty($response->results);

        return $response;
    }
}
