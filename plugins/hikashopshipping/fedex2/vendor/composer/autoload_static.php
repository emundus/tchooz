<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php


namespace Composer\Autoload;

class ComposerStaticInitcf4621996a402fa1c8f8d33a25ec1af4
{
    public static $files = array (
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
        '6e3fae29631ef280660b3cdad06f25a8' => __DIR__ . '/..' . '/symfony/deprecation-contracts/function.php',
        '7b11c4dc42b3b3023073cb14e519683c' => __DIR__ . '/..' . '/ralouphie/getallheaders/src/getallheaders.php',
        'a4a119a56e50fbb293281d9a48007e0e' => __DIR__ . '/..' . '/symfony/polyfill-php80/bootstrap.php',
        'a1105708a18b76903365ca1c4aa61b02' => __DIR__ . '/..' . '/symfony/translation/Resources/functions.php',
        '37a3dc5111fe8f707ab4c132ef1dbc62' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/functions_include.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Polyfill\\Php80\\' => 23,
            'Symfony\\Polyfill\\Mbstring\\' => 26,
            'Symfony\\Contracts\\Translation\\' => 30,
            'Symfony\\Component\\Translation\\' => 30,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
            'Psr\\Http\\Client\\' => 16,
            'Psr\\Clock\\' => 10,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
        ),
        'F' => 
        array (
            'FedexRest\\Tests\\' => 16,
            'FedexRest\\' => 10,
        ),
        'C' => 
        array (
            'Carbon\\Doctrine\\' => 16,
            'Carbon\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Polyfill\\Php80\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-php80',
        ),
        'Symfony\\Polyfill\\Mbstring\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'Symfony\\Contracts\\Translation\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/translation-contracts',
        ),
        'Symfony\\Component\\Translation\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/translation',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
            1 => __DIR__ . '/..' . '/psr/http-factory/src',
        ),
        'Psr\\Http\\Client\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-client/src',
        ),
        'Psr\\Clock\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/clock/src',
        ),
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/promises/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
        'FedexRest\\Tests\\' => 
        array (
            0 => __DIR__ . '/..' . '/whatarmy/fedex-rest/tests/FedexRest/Tests',
        ),
        'FedexRest\\' => 
        array (
            0 => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest',
        ),
        'Carbon\\Doctrine\\' => 
        array (
            0 => __DIR__ . '/..' . '/carbonphp/carbon-doctrine-types/src/Carbon/Doctrine',
        ),
        'Carbon\\' => 
        array (
            0 => __DIR__ . '/..' . '/nesbot/carbon/src/Carbon',
        ),
    );

    public static $classMap = array (
        'Attribute' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/Attribute.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'FedexRest\\Authorization\\Authorize' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Authorization/Authorize.php',
        'FedexRest\\Entity\\Address' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Entity/Address.php',
        'FedexRest\\Entity\\DangerousGoodsDetail' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Entity/DangerousGoodsDetail.php',
        'FedexRest\\Entity\\Dimensions' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Entity/Dimensions.php',
        'FedexRest\\Entity\\Item' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Entity/Item.php',
        'FedexRest\\Entity\\PackageSpecialServicesRequested' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Entity/PackageSpecialServicesRequested.php',
        'FedexRest\\Entity\\Person' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Entity/Person.php',
        'FedexRest\\Entity\\Weight' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Entity/Weight.php',
        'FedexRest\\Exceptions\\MissingAccessTokenException' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Exceptions/MissingAccessTokenException.php',
        'FedexRest\\Exceptions\\MissingAccountNumberException' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Exceptions/MissingAccountNumberException.php',
        'FedexRest\\Exceptions\\MissingAuthCredentialsException' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Exceptions/MissingAuthCredentialsException.php',
        'FedexRest\\Exceptions\\MissingLineItemException' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Exceptions/MissingLineItemException.php',
        'FedexRest\\Exceptions\\MissingTrackingNumberException' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Exceptions/MissingTrackingNumberException.php',
        'FedexRest\\Services\\AbstractRequest' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/AbstractRequest.php',
        'FedexRest\\Services\\AddressValidation\\AddressValidation' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/AddressValidation/AddressValidation.php',
        'FedexRest\\Services\\LocationSearch\\FindLocations' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/LocationSearch/FindLocations.php',
        'FedexRest\\Services\\LocationSearch\\Type\\SearchCriterionType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/LocationSearch/Type/SearchCriterionType.php',
        'FedexRest\\Services\\Rates\\CreateRatesRequest' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Rates/CreateRatesRequest.php',
        'FedexRest\\Services\\RequestInterface' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/RequestInterface.php',
        'FedexRest\\Services\\Ship\\CreateShipment' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/CreateShipment.php',
        'FedexRest\\Services\\Ship\\CreateTagRequest' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/CreateTagRequest.php',
        'FedexRest\\Services\\Ship\\Entity\\Label' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/Label.php',
        'FedexRest\\Services\\Ship\\Entity\\ShipmentSpecialServices' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/ShipmentSpecialServices.php',
        'FedexRest\\Services\\Ship\\Entity\\ShippingChargesPayment' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/ShippingChargesPayment.php',
        'FedexRest\\Services\\Ship\\Entity\\Value' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/Value.php',
        'FedexRest\\Services\\Ship\\Exceptions\\MissingLabelException' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Exceptions/MissingLabelException.php',
        'FedexRest\\Services\\Ship\\Exceptions\\MissingLabelResponseOptionsException' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Exceptions/MissingLabelResponseOptionsException.php',
        'FedexRest\\Services\\Ship\\Exceptions\\MissingShippingChargesPaymentException' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Exceptions/MissingShippingChargesPaymentException.php',
        'FedexRest\\Services\\Ship\\Type\\DangerousGoodsAccessibilityType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/DangerousGoodsAccessibilityType.php',
        'FedexRest\\Services\\Ship\\Type\\ImageType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ImageType.php',
        'FedexRest\\Services\\Ship\\Type\\LabelDocOptionType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/LabelDocOptionType.php',
        'FedexRest\\Services\\Ship\\Type\\LabelResponseOptionsType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/LabelResponseOptionsType.php',
        'FedexRest\\Services\\Ship\\Type\\LabelStockType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/LabelStockType.php',
        'FedexRest\\Services\\Ship\\Type\\LinearUnits' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/LinearUnits.php',
        'FedexRest\\Services\\Ship\\Type\\PackageSpecialServiceType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/PackageSpecialServiceType.php',
        'FedexRest\\Services\\Ship\\Type\\PackagingType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/PackagingType.php',
        'FedexRest\\Services\\Ship\\Type\\PickupType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/PickupType.php',
        'FedexRest\\Services\\Ship\\Type\\ProcessingOptionType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ProcessingOptionType.php',
        'FedexRest\\Services\\Ship\\Type\\ServiceType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ServiceType.php',
        'FedexRest\\Services\\Ship\\Type\\ShipActionType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ShipActionType.php',
        'FedexRest\\Services\\Ship\\Type\\ShipmentSpecialServiceType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ShipmentSpecialServiceType.php',
        'FedexRest\\Services\\Ship\\Type\\SubPackagingType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/SubPackagingType.php',
        'FedexRest\\Services\\Ship\\Type\\VolumeUnits' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/VolumeUnits.php',
        'FedexRest\\Services\\Ship\\Type\\WeightUnits' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/WeightUnits.php',
        'FedexRest\\Services\\Track\\TrackByTrackingNumberRequest' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Track/TrackByTrackingNumberRequest.php',
        'FedexRest\\Traits\\rawable' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Traits/rawable.php',
        'FedexRest\\Traits\\switchableEnv' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Traits/switchableEnv.php',
        'FedexRest\\Utils' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Utils.php',
        'PhpToken' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/PhpToken.php',
        'Stringable' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/Stringable.php',
        'UnhandledMatchError' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/UnhandledMatchError.php',
        'ValueError' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/ValueError.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitcf4621996a402fa1c8f8d33a25ec1af4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitcf4621996a402fa1c8f8d33a25ec1af4::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitcf4621996a402fa1c8f8d33a25ec1af4::$classMap;

        }, null, ClassLoader::class);
    }
}
