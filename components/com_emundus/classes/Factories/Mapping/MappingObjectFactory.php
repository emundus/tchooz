<?php

namespace Tchooz\Factories\Mapping;

use Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject;
use Tchooz\Synchronizers\Hubspot\Objects\ContactObject;
use Tchooz\Synchronizers\Hubspot\Objects\DealObject;
use Tchooz\Synchronizers\Mapping\MappingObjectInterface;
use Tchooz\Synchronizers\Sofis\Objects\FinancialDimensionValueObject;
use Tchooz\Synchronizers\Sofis\Objects\PurchaseOrderObject;
use Tchooz\Synchronizers\Sofis\Objects\VendorObject;

/**
 * Single entry point between the outside world (automation action, mapping configuration UI) and
 * the mapping objects. Resolves the object that carries the business rules for a given
 * (connector type, object name) pair, and lists the objects available for a connector.
 *
 * Adding a new object or connector means adding an entry to the registry and its object class —
 * never touching the transport (synchronizer) nor the executor.
 */
class MappingObjectFactory
{
	/**
	 * connector type => object name => object class.
	 *
	 * @var array<string, array<string, class-string<MappingObjectInterface>>>
	 */
	private const REGISTRY = [
		'hubspot' => [
			'contact' => ContactObject::class,
			'deal'    => DealObject::class,
		],
		'sofis'   => [
			'vendor'                    => VendorObject::class,
			'financial_dimension_value' => FinancialDimensionValueObject::class,
			'purchase_order'            => PurchaseOrderObject::class,
		],
		'sacem_ged' => [
			'document' => DocumentObject::class,
		],
	];

	/**
	 * @throws \DomainException when no object is registered for the pair.
	 */
	public function make(string $connectorType, string $objectName): MappingObjectInterface
	{
		$class = self::REGISTRY[$connectorType][$objectName] ?? null;

		if ($class === null || !class_exists($class))
		{
			throw new \DomainException(sprintf('No mapping object registered for connector "%s" and object "%s".', $connectorType, $objectName));
		}

		return new $class();
	}

	/**
	 * Connector types that actually support mapping, i.e. those exposing at least one object. Used to
	 * restrict the connector choices offered by the mapping configuration UI, so an admin cannot pick
	 * a synchronizer no object can be mapped to.
	 *
	 * @return array<string>
	 */
	public function getSupportedConnectorTypes(): array
	{
		$types = [];

		foreach (array_keys(self::REGISTRY) as $connectorType)
		{
			if (!empty($this->getAvailableObjects($connectorType)))
			{
				$types[] = $connectorType;
			}
		}

		return $types;
	}

	/**
	 * @return array<MappingObjectInterface>
	 */
	public function getAvailableObjects(string $connectorType): array
	{
		$objects = [];

		foreach (self::REGISTRY[$connectorType] ?? [] as $class)
		{
			if (class_exists($class))
			{
				$objects[] = new $class();
			}
		}

		return $objects;
	}
}
