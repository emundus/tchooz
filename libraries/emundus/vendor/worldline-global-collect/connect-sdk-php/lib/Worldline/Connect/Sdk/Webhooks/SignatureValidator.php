<?php
namespace Worldline\Connect\Sdk\Webhooks;

/**
 * Class SignatureValidator
 *
 * @package Worldline\Connect\Sdk\Webhooks
 */
class SignatureValidator
{
    /**
     * @var SecretKeyStore
     */
    private SecretKeyStore $secretKeyStore;

    /**
     * @param SecretKeyStore $secretKeyStore
     */
    public function __construct(SecretKeyStore $secretKeyStore)
    {
        $this->secretKeyStore = $secretKeyStore;
    }

    /**
     * Validates the given body using the given request headers.
     *
     * @param string $body
     * @param array  $requestHeaders
     *
     * @return void
     * @throws SignatureValidationException
     */
    public function validate(string $body, array $requestHeaders): void
    {
        $this->validateBody($body, $requestHeaders);
    }

    // utility methods

    private function validateBody(string $body, array $requestHeaders): void
    {
        $signature = $this->getHeaderValue($requestHeaders, 'X-GCS-Signature');
        $keyId = $this->getHeaderValue($requestHeaders, 'X-GCS-KeyId');
        $secretKey = $this->secretKeyStore->getSecretKey($keyId);

        $expectedSignature = base64_encode(hash_hmac("sha256", $body, $secretKey, true));

        $isValid = hash_equals($signature, $expectedSignature);
        if (!$isValid) {
            throw new SignatureValidationException("failed to validate signature '$signature'");
        }
    }

    // general utility methods

    private function getHeaderValue(array $requestHeaders, string $headerName): string
    {
        $lowerCaseHeaderName = strtolower($headerName);
        foreach ($requestHeaders as $name => $value) {
            if ($lowerCaseHeaderName === strtolower($name)) {
                return $value;
            }
        }
        throw new SignatureValidationException("could not find header '$headerName'");
    }
}
