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


$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'Attribute' => $vendorDir . '/symfony/polyfill-php80/Resources/stubs/Attribute.php',
    'Composer\\InstalledVersions' => $vendorDir . '/composer/InstalledVersions.php',
    'FedexRest\\Authorization\\Authorize' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Authorization/Authorize.php',
    'FedexRest\\Entity\\Address' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Entity/Address.php',
    'FedexRest\\Entity\\DangerousGoodsDetail' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Entity/DangerousGoodsDetail.php',
    'FedexRest\\Entity\\Dimensions' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Entity/Dimensions.php',
    'FedexRest\\Entity\\Item' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Entity/Item.php',
    'FedexRest\\Entity\\PackageSpecialServicesRequested' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Entity/PackageSpecialServicesRequested.php',
    'FedexRest\\Entity\\Person' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Entity/Person.php',
    'FedexRest\\Entity\\Weight' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Entity/Weight.php',
    'FedexRest\\Exceptions\\MissingAccessTokenException' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Exceptions/MissingAccessTokenException.php',
    'FedexRest\\Exceptions\\MissingAccountNumberException' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Exceptions/MissingAccountNumberException.php',
    'FedexRest\\Exceptions\\MissingAuthCredentialsException' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Exceptions/MissingAuthCredentialsException.php',
    'FedexRest\\Exceptions\\MissingLineItemException' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Exceptions/MissingLineItemException.php',
    'FedexRest\\Exceptions\\MissingTrackingNumberException' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Exceptions/MissingTrackingNumberException.php',
    'FedexRest\\Services\\AbstractRequest' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/AbstractRequest.php',
    'FedexRest\\Services\\AddressValidation\\AddressValidation' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/AddressValidation/AddressValidation.php',
    'FedexRest\\Services\\LocationSearch\\FindLocations' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/LocationSearch/FindLocations.php',
    'FedexRest\\Services\\LocationSearch\\Type\\SearchCriterionType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/LocationSearch/Type/SearchCriterionType.php',
    'FedexRest\\Services\\Rates\\CreateRatesRequest' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Rates/CreateRatesRequest.php',
    'FedexRest\\Services\\RequestInterface' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/RequestInterface.php',
    'FedexRest\\Services\\Ship\\CreateShipment' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/CreateShipment.php',
    'FedexRest\\Services\\Ship\\CreateTagRequest' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/CreateTagRequest.php',
    'FedexRest\\Services\\Ship\\Entity\\Label' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/Label.php',
    'FedexRest\\Services\\Ship\\Entity\\ShipmentSpecialServices' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/ShipmentSpecialServices.php',
    'FedexRest\\Services\\Ship\\Entity\\ShippingChargesPayment' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/ShippingChargesPayment.php',
    'FedexRest\\Services\\Ship\\Entity\\Value' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Entity/Value.php',
    'FedexRest\\Services\\Ship\\Exceptions\\MissingLabelException' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Exceptions/MissingLabelException.php',
    'FedexRest\\Services\\Ship\\Exceptions\\MissingLabelResponseOptionsException' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Exceptions/MissingLabelResponseOptionsException.php',
    'FedexRest\\Services\\Ship\\Exceptions\\MissingShippingChargesPaymentException' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Exceptions/MissingShippingChargesPaymentException.php',
    'FedexRest\\Services\\Ship\\Type\\DangerousGoodsAccessibilityType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/DangerousGoodsAccessibilityType.php',
    'FedexRest\\Services\\Ship\\Type\\ImageType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ImageType.php',
    'FedexRest\\Services\\Ship\\Type\\LabelDocOptionType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/LabelDocOptionType.php',
    'FedexRest\\Services\\Ship\\Type\\LabelResponseOptionsType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/LabelResponseOptionsType.php',
    'FedexRest\\Services\\Ship\\Type\\LabelStockType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/LabelStockType.php',
    'FedexRest\\Services\\Ship\\Type\\LinearUnits' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/LinearUnits.php',
    'FedexRest\\Services\\Ship\\Type\\PackageSpecialServiceType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/PackageSpecialServiceType.php',
    'FedexRest\\Services\\Ship\\Type\\PackagingType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/PackagingType.php',
    'FedexRest\\Services\\Ship\\Type\\PickupType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/PickupType.php',
    'FedexRest\\Services\\Ship\\Type\\ProcessingOptionType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ProcessingOptionType.php',
    'FedexRest\\Services\\Ship\\Type\\ServiceType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ServiceType.php',
    'FedexRest\\Services\\Ship\\Type\\ShipActionType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ShipActionType.php',
    'FedexRest\\Services\\Ship\\Type\\ShipmentSpecialServiceType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/ShipmentSpecialServiceType.php',
    'FedexRest\\Services\\Ship\\Type\\SubPackagingType' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/SubPackagingType.php',
    'FedexRest\\Services\\Ship\\Type\\VolumeUnits' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/VolumeUnits.php',
    'FedexRest\\Services\\Ship\\Type\\WeightUnits' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Ship/Type/WeightUnits.php',
    'FedexRest\\Services\\Track\\TrackByTrackingNumberRequest' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Services/Track/TrackByTrackingNumberRequest.php',
    'FedexRest\\Traits\\rawable' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Traits/rawable.php',
    'FedexRest\\Traits\\switchableEnv' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Traits/switchableEnv.php',
    'FedexRest\\Utils' => $vendorDir . '/whatarmy/fedex-rest/src/FedexRest/Utils.php',
    'PhpToken' => $vendorDir . '/symfony/polyfill-php80/Resources/stubs/PhpToken.php',
    'Stringable' => $vendorDir . '/symfony/polyfill-php80/Resources/stubs/Stringable.php',
    'UnhandledMatchError' => $vendorDir . '/symfony/polyfill-php80/Resources/stubs/UnhandledMatchError.php',
    'ValueError' => $vendorDir . '/symfony/polyfill-php80/Resources/stubs/ValueError.php',
);
