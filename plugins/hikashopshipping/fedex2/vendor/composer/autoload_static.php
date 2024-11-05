<?php


namespace Composer\Autoload;

class ComposerStaticInitcf4621996a402fa1c8f8d33a25ec1af4
{
    public static $files = array (
        '7b11c4dc42b3b3023073cb14e519683c' => __DIR__ . '/..' . '/ralouphie/getallheaders/src/getallheaders.php',
        '6e3fae29631ef280660b3cdad06f25a8' => __DIR__ . '/..' . '/symfony/deprecation-contracts/function.php',
        '37a3dc5111fe8f707ab4c132ef1dbc62' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/functions_include.php',
    );

    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
            'Psr\\Http\\Client\\' => 16,
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
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
            1 => __DIR__ . '/..' . '/psr/http-factory/src',
        ),
        'Psr\\Http\\Client\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-client/src',
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
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'FedexRest\\Authorization\\Authorize' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Authorization/Authorize.php',
        'FedexRest\\Entity\\Address' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Entity/Address.php',
        'FedexRest\\Entity\\DangerousGoodsDetail' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Entity/DangerousGoodsDetail.php',
        'FedexRest\\Entity\\Dimensions' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Entity/Dimensions.php',
        'FedexRest\\Entity\\EmailNotificationRecipient' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Entity/EmailNotificationRecipient.php',
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
        'FedexRest\\Services\\Ship\\CancelShipment' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/CancelShipment.php',
        'FedexRest\\Services\\Ship\\CreateShipment' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/CreateShipment.php',
        'FedexRest\\Services\\Ship\\CreateTagRequest' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/CreateTagRequest.php',
        'FedexRest\\Services\\Ship\\Entity\\EmailNotificationDetail' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/EmailNotificationDetail.php',
        'FedexRest\\Services\\Ship\\Entity\\Label' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/Label.php',
        'FedexRest\\Services\\Ship\\Entity\\ShipmentSpecialServices' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/ShipmentSpecialServices.php',
        'FedexRest\\Services\\Ship\\Entity\\ShippingChargesPayment' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/ShippingChargesPayment.php',
        'FedexRest\\Services\\Ship\\Entity\\SmartPostInfoDetail' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/SmartPostInfoDetail.php',
        'FedexRest\\Services\\Ship\\Entity\\Value' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/Value.php',
        'FedexRest\\Services\\Ship\\Exceptions\\MissingLabelException' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Exceptions/MissingLabelException.php',
        'FedexRest\\Services\\Ship\\Exceptions\\MissingLabelResponseOptionsException' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Exceptions/MissingLabelResponseOptionsException.php',
        'FedexRest\\Services\\Ship\\Exceptions\\MissingShippingChargesPaymentException' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Exceptions/MissingShippingChargesPaymentException.php',
        'FedexRest\\Services\\Ship\\Type\\AncillaryEndorsementType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/AncillaryEndorsementType.php',
        'FedexRest\\Services\\Ship\\Type\\DangerousGoodsAccessibilityType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/DangerousGoodsAccessibilityType.php',
        'FedexRest\\Services\\Ship\\Type\\ImageType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ImageType.php',
        'FedexRest\\Services\\Ship\\Type\\IndiciaType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/IndiciaType.php',
        'FedexRest\\Services\\Ship\\Type\\LabelDocOptionType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/LabelDocOptionType.php',
        'FedexRest\\Services\\Ship\\Type\\LabelResponseOptionsType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/LabelResponseOptionsType.php',
        'FedexRest\\Services\\Ship\\Type\\LabelStockType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/LabelStockType.php',
        'FedexRest\\Services\\Ship\\Type\\LinearUnits' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/LinearUnits.php',
        'FedexRest\\Services\\Ship\\Type\\NotificationEventType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/NotificationEventType.php',
        'FedexRest\\Services\\Ship\\Type\\PackageSpecialServiceType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/PackageSpecialServiceType.php',
        'FedexRest\\Services\\Ship\\Type\\PackagingType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/PackagingType.php',
        'FedexRest\\Services\\Ship\\Type\\PickupType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/PickupType.php',
        'FedexRest\\Services\\Ship\\Type\\ProcessingOptionType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ProcessingOptionType.php',
        'FedexRest\\Services\\Ship\\Type\\ServiceType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ServiceType.php',
        'FedexRest\\Services\\Ship\\Type\\ShipActionType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ShipActionType.php',
        'FedexRest\\Services\\Ship\\Type\\ShipmentSpecialServiceType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ShipmentSpecialServiceType.php',
        'FedexRest\\Services\\Ship\\Type\\SpecialServiceType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/SpecialServiceType.php',
        'FedexRest\\Services\\Ship\\Type\\SubPackagingType' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/SubPackagingType.php',
        'FedexRest\\Services\\Ship\\Type\\VolumeUnits' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/VolumeUnits.php',
        'FedexRest\\Services\\Ship\\Type\\WeightUnits' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/WeightUnits.php',
        'FedexRest\\Services\\Track\\TrackByTrackingNumberRequest' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Services/Track/TrackByTrackingNumberRequest.php',
        'FedexRest\\Traits\\rawable' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Traits/rawable.php',
        'FedexRest\\Traits\\switchableEnv' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Traits/switchableEnv.php',
        'FedexRest\\Utils' => __DIR__ . '/..' . '/whatarmy/fedex-rest/src/FedexRest/Utils.php',
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
