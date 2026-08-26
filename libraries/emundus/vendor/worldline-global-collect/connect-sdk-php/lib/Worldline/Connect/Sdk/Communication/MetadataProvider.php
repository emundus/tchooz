<?php
namespace Worldline\Connect\Sdk\Communication;

use stdClass;
use Worldline\Connect\Sdk\CommunicatorConfiguration;
use Worldline\Connect\Sdk\Domain\ShoppingCartExtension;

/**
 * Class MetadataProvider
 *
 * @package Worldline\Connect\Sdk\Communication
 */
class MetadataProvider
{
    const SDK_VERSION = '10.3.0';

    /**
     * @var string
     */
    private string $integrator;

    /**
     * @var ShoppingCartExtension|null
     */
    private ?ShoppingCartExtension $shoppingCartExtension;

    /**
     * @param CommunicatorConfiguration $communicatorConfiguration
     */
    public function __construct(CommunicatorConfiguration $communicatorConfiguration)
    {
        $this->integrator = $communicatorConfiguration->getIntegrator();
        $this->shoppingCartExtension = $communicatorConfiguration->getShoppingCartExtension();
    }

    /**
     * @return string
     */
    public function getServerMetaInfoValue(): string
    {
        // use a stdClass instead of specific class to keep out null properties
        $serverMetaInfo = new stdClass();
        $serverMetaInfo->platformIdentifier = sprintf('%s; php version %s', php_uname(), phpversion());
        $serverMetaInfo->sdkIdentifier = 'PHPServerSDK/v' . static::SDK_VERSION;
        $serverMetaInfo->sdkCreator = 'Worldline';
        $serverMetaInfo->integrator = $this->integrator;
        if (!is_null($this->shoppingCartExtension)) {
            $serverMetaInfo->shoppingCartExtension = $this->shoppingCartExtension->toObject();
        }
        // the sdkIdentifier contains a /. Without the JSON_UNESCAPED_SLASHES, this is turned to \/ in JSON.
        return base64_encode(json_encode($serverMetaInfo, JSON_UNESCAPED_SLASHES));
    }
}
