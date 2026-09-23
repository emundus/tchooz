<?php

namespace Tchooz\Synchronizers\Sofis\Objects;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\SynchronizerMappingObjectDefinition;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Services\Mapping\MappingService;
use Tchooz\Synchronizers\Mapping\SelfExecutingMappingObject;
use Tchooz\Synchronizers\MappingTransportInterface;

/**
 * Sofis purchase order (INT-03): creates the grant purchase order of an application file — one header
 * plus one line — on Dynamics.
 *
 * Choreography (per the SAC mapping):
 *   1. GET VendorsV2 by SIRET → the project holder's VendorAccountNumber and its
 *      DefaultLedgerDimensionDisplayValue, used as the base of the financial dimension string;
 *   2. POST PurchaseOrderHeadersV2 → the ERP generates PurchaseOrderNumber, persisted as the file's
 *      external reference;
 *   3. POST PurchaseOrderLinesV2 with that PurchaseOrderNumber.
 *
 * A purchase order is a financial document: creation is guarded by the stored reference so a task
 * re-run never produces a duplicate.
 */
class PurchaseOrderObject extends AbstractSofisObject implements SelfExecutingMappingObject
{
	private const VENDOR_ENTITY = 'VendorsV2';
	private const HEADER_ENTITY = 'PurchaseOrderHeadersV2';
	private const LINE_ENTITY   = 'PurchaseOrderLinesV2';

	private const DIMENSION_FIELD = 'DefaultLedgerDimensionDisplayValue';

	// FieldGroup names routing each mapped field to its payload (see collectMappedFields).
	private const GROUP_HEADER    = 'header';
	private const GROUP_LINE      = 'line';
	private const GROUP_DIMENSION = 'dimension';

	/**
	 * RM1: the vendor's financial dimension value is a tilde-separated positional string. We keep the
	 * vendor's own segments (notably the vendor account in position 4) and overwrite only the mapped
	 * ones.
	 *
	 * The position IS carried by the target field name (`LedgerDimension_<position>`), so covering a
	 * new position needs no code change: the admin adds a mapping row targeting e.g.
	 * `LedgerDimension_7`. The positions known today are declared as availableFields below, to be
	 * pre-filled and labelled in the configuration UI.
	 */
	private const DIMENSION_FIELD_PREFIX = 'LedgerDimension_';

	// Expected number of segments in the dimension string. A different shape means the positions no
	// longer mean what we think, so we fail instead of writing wrong accounting dimensions.
	private const DIMENSION_SEGMENTS = 25;

	private ExternalReferenceRepository $externalReferenceRepository;

	public function __construct(?ExternalReferenceRepository $externalReferenceRepository = null)
	{
		parent::__construct();

		$this->externalReferenceRepository = $externalReferenceRepository ?? new ExternalReferenceRepository();
	}

	public function getName(): string
	{
		return 'purchase_order';
	}

	protected function buildDefinition(): SynchronizerMappingObjectDefinition
	{
		$headerGroup    = new FieldGroup(self::GROUP_HEADER, Text::_('COM_EMUNDUS_SOFIS_PO_HEADER_GROUP_LABEL'));
		$lineGroup      = new FieldGroup(self::GROUP_LINE, Text::_('COM_EMUNDUS_SOFIS_PO_LINE_GROUP_LABEL'));
		$dimensionGroup = new FieldGroup(self::GROUP_DIMENSION, Text::_('COM_EMUNDUS_SOFIS_PO_DIMENSION_GROUP_LABEL'));

		return new SynchronizerMappingObjectDefinition(
			$this->getName(),
			'COM_EMUNDUS_SOFIS_PURCHASE_ORDER_OBJECT_LABEL',
			'/data/PurchaseOrderHeadersV2',
			new ExternalReferenceEntity(0, 'fnum', '', '', null, self::HEADER_ENTITY, 'PurchaseOrderNumber'),
			[ApiMethodEnum::GET, ApiMethodEnum::POST],
			[], // requiredFields (config params) — none
			[], // metadata
			[], // associations
			[
				// Identifies the project holder: same SIRET as the vendor synchronization (INT-01).
				new NumericField('SiretNumber', Text::_('COM_EMUNDUS_SOFIS_VENDOR_FIELD_SIRET'), true, $headerGroup),

				// Header — field names ARE the Dynamics field names (passthrough).
				(new StringField('dataAreaId', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_DATA_AREA_ID_LABEL'), true, $headerGroup))->setDefaultValue('FOND'),
				(new StringField('OrdererPersonnelNumber', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_ORDERER'), true, $headerGroup))->setDefaultValue('000970'),
				(new StringField('RequesterPersonnelNumber', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_REQUESTER'), true, $headerGroup))->setDefaultValue('000970'),
				(new StringField('VendorPaymentMethodName', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_PAYMENT_METHOD'), true, $headerGroup))->setDefaultValue('F_VIREMENT'),
				(new StringField('SACEInfosTreso', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_TREASURY_INFO'), true, $headerGroup))->setDefaultValue('Fondo'),
				// F&O NoYes fields travel as quoted strings over OData, never as JSON booleans.
				(new StringField('IsChangeManagementActive', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_CHANGE_MANAGEMENT'), true, $headerGroup))->setDefaultValue('Yes'),
				// Left unmapped, defaults to the current date (see buildHeaderPayload).
				new StringField('RequestedDeliveryDate', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_DELIVERY_DATE'), false, $headerGroup),

				// Financial dimensions (RM1): the name suffix is the position in the dimension string.
				// Any other position can be covered by mapping a `LedgerDimension_<position>` target.
				// Slots 1 and 2 carry a fixed value whose accounting meaning SACEM did not document.
				(new StringField(self::DIMENSION_FIELD_PREFIX . '1', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_DIMENSION_1'), true, $dimensionGroup))->setDefaultValue('FDS100000'),
				(new StringField(self::DIMENSION_FIELD_PREFIX . '2', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_DIMENSION_2'), true, $dimensionGroup))->setDefaultValue('FDS100000'),
				new StringField(self::DIMENSION_FIELD_PREFIX . '3', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_DIMENSION_3'), true, $dimensionGroup),
				new StringField(self::DIMENSION_FIELD_PREFIX . '5', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_DIMENSION_5'), true, $dimensionGroup),
				new StringField(self::DIMENSION_FIELD_PREFIX . '12', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_DIMENSION_12'), true, $dimensionGroup),

				// Line — field names ARE the Dynamics field names (passthrough).
				new StringField('ItemNumber', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_ITEM_NUMBER'), true, $lineGroup),
				new NumericField('PurchasePrice', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_PRICE'), true, $lineGroup),
				(new NumericField('PurchasePriceQuantity', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_QUANTITY'), true, $lineGroup))->setDefaultValue(1),
				// A single order line to date, hence the fixed line number.
				(new NumericField('LineNumber', Text::_('COM_EMUNDUS_SOFIS_PO_FIELD_LINE_NUMBER'), true, $lineGroup))->setDefaultValue(1),
			]
		);
	}

	/**
	 * @throws \Exception
	 */
	public function execute(MappingEntity $mapping, ActionTargetEntity $context, MappingTransportInterface $transport): bool
	{
		$fnum = (string) $context->getFile();
		$data = MappingService::getJsonFromMapping($mapping, $context);

		// A purchase order is a financial document: never create a second header for the same file.
		// The line may still be missing though (a previous run failing between the two POSTs), so we
		// resume the choreography instead of skipping it.
		$storedOrderNumber = $this->findStoredOrderNumber($mapping->getSynchronizerId(), $fnum);

		if ($storedOrderNumber !== null)
		{
			$this->ensureLine($transport, $data, $storedOrderNumber);

			return true;
		}

		$this->validateRequiredFields($data, self::GROUP_HEADER);
		$this->validateRequiredFields($data, self::GROUP_DIMENSION);
		$this->validateRequiredFields($data, self::GROUP_LINE);

		$vendor = $this->findVendor($transport, $data);

		if ($vendor === null)
		{
			throw new \DomainException(Text::sprintf('COM_EMUNDUS_SOFIS_PO_VENDOR_NOT_FOUND', (string) $data['SiretNumber']));
		}

		$headerPayload = $this->buildHeaderPayload($data, $vendor);
		$created       = $this->create($transport, self::HEADER_ENTITY, $headerPayload);

		$orderNumber = (string) ($created->PurchaseOrderNumber ?? '');

		if ($orderNumber === '')
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_SOFIS_PO_NO_ORDER_NUMBER'));
		}

		// Persist before creating the line: the reference is what prevents a duplicate header, and a
		// failing line is recoverable on the next run (see ensureLine).
		$this->persistReference($fnum, $orderNumber, $mapping->getSynchronizerId());

		$this->create($transport, self::LINE_ENTITY, $this->buildLinePayload($data, $orderNumber));

		return true;
	}

	/**
	 * Create the order line unless it already exists. This is what makes a re-run resume an order
	 * whose header was created but whose line failed, without ever duplicating either.
	 *
	 * @throws \Exception
	 */
	private function ensureLine(MappingTransportInterface $transport, array $data, string $orderNumber): void
	{
		if ($this->findLine($transport, $data, $orderNumber) !== null)
		{
			Log::add('Purchase order ' . $orderNumber . ' already complete, nothing to do.', Log::INFO, self::CHANNEL);

			return;
		}

		Log::add('Purchase order ' . $orderNumber . ' has no line yet, creating it.', Log::INFO, self::CHANNEL);

		$this->validateRequiredFields($data, self::GROUP_LINE);
		$this->create($transport, self::LINE_ENTITY, $this->buildLinePayload($data, $orderNumber));
	}

	/**
	 * @throws \Exception
	 */
	private function findLine(MappingTransportInterface $transport, array $data, string $orderNumber): ?object
	{
		$filter = $this->buildFilter([
			'dataAreaId'          => (string) ($data['dataAreaId'] ?? ''),
			'PurchaseOrderNumber' => $orderNumber,
		]);

		$results = $this->search($transport, self::LINE_ENTITY, $filter);

		return $results[0] ?? null;
	}

	/**
	 * Look the project holder up by SIRET. The same call yields its VendorAccountNumber and the
	 * financial dimension string used as the base of RM1.
	 *
	 * @throws \Exception
	 */
	private function findVendor(MappingTransportInterface $transport, array $data): ?object
	{
		$filter = $this->buildFilter([
			'SiretNumber' => (string) $data['SiretNumber'],
			'dataAreaId'  => (string) ($data['dataAreaId'] ?? ''),
		]);

		$results = $this->search($transport, self::VENDOR_ENTITY, $filter);

		return $results[0] ?? null;
	}

	private function buildHeaderPayload(array $data, object $vendor): array
	{
		$payload = $this->collectMappedFields($data, self::GROUP_HEADER, ['SiretNumber']);

		$payload['OrderVendorAccountNumber'] = (string) ($vendor->VendorAccountNumber ?? '');
		$payload[self::DIMENSION_FIELD]      = $this->buildDimensionValue($data, $vendor);

		if (empty($payload['RequestedDeliveryDate']))
		{
			$payload['RequestedDeliveryDate'] = gmdate('Y-m-d\TH:i:s\Z');
		}

		return $payload;
	}

	/**
	 * RM1: enrich the vendor's dimension string at the declared positions, keeping every other
	 * segment untouched.
	 *
	 * @throws \DomainException when the string does not have the expected structure.
	 */
	private function buildDimensionValue(array $data, object $vendor): string
	{
		$base = (string) ($vendor->{self::DIMENSION_FIELD} ?? '');

		if ($base === '')
		{
			throw new \DomainException(Text::_('COM_EMUNDUS_SOFIS_PO_VENDOR_DIMENSION_EMPTY'));
		}

		$segments = explode('~', $base);

		if (count($segments) !== self::DIMENSION_SEGMENTS)
		{
			throw new \DomainException(Text::sprintf('COM_EMUNDUS_SOFIS_PO_DIMENSION_UNEXPECTED', count($segments), self::DIMENSION_SEGMENTS));
		}

		foreach ($data as $field => $value)
		{
			if (!str_starts_with((string) $field, self::DIMENSION_FIELD_PREFIX))
			{
				continue;
			}

			$position = substr((string) $field, strlen(self::DIMENSION_FIELD_PREFIX));

			// A typo would silently write into the wrong accounting slot, or none at all.
			if (!ctype_digit($position) || (int) $position < 1 || (int) $position > self::DIMENSION_SEGMENTS)
			{
				throw new \DomainException(Text::sprintf('COM_EMUNDUS_SOFIS_PO_DIMENSION_POSITION_INVALID', $field, self::DIMENSION_SEGMENTS));
			}

			$segments[(int) $position - 1] = (string) $value;
		}

		return implode('~', $segments);
	}

	private function buildLinePayload(array $data, string $orderNumber): array
	{
		$payload = $this->collectMappedFields($data, self::GROUP_LINE);

		$payload['dataAreaId']          = (string) ($data['dataAreaId'] ?? '');
		$payload['PurchaseOrderNumber'] = $orderNumber;

		// The ERP types these numerically; the mapping carries them as strings.
		$payload['LineNumber']            = (int) $this->requireNumber($data, 'LineNumber');
		$payload['PurchasePrice']         = $this->requireNumber($data, 'PurchasePrice');
		$payload['PurchasePriceQuantity'] = $this->requireNumber($data, 'PurchasePriceQuantity');

		return $payload;
	}

	/**
	 * Convert a mapped value to a number, refusing anything not unambiguously numeric. A plain (float)
	 * cast would silently turn a formatted amount like "1 500,00" into 1.0 — on a purchase order, so
	 * an ambiguous value must abort the order rather than under-fund it. Normalize formatted sources
	 * with a transformation on the mapping row.
	 *
	 * @throws \DomainException when the value is not a machine-readable number.
	 */
	private function requireNumber(array $data, string $fieldName): float
	{
		$value = $data[$fieldName] ?? null;

		if (is_int($value) || is_float($value))
		{
			return (float) $value;
		}

		if (is_string($value) && is_numeric(trim($value)))
		{
			return (float) trim($value);
		}

		throw new \DomainException(Text::sprintf(
			'COM_EMUNDUS_SOFIS_PO_FIELD_NOT_NUMERIC',
			$this->getFieldLabel($fieldName),
			is_scalar($value) ? (string) $value : gettype($value)
		));
	}

	/**
	 * Human-readable label of an available field, for error messages.
	 */
	private function getFieldLabel(string $fieldName): string
	{
		foreach ($this->getDefinition()->getAvailableFields() as $field)
		{
			if ($field->getName() === $fieldName)
			{
				return $field->getLabel();
			}
		}

		return $fieldName;
	}

	/**
	 * The PurchaseOrderNumber already stored for this file, if any.
	 */
	private function findStoredOrderNumber(int $synchronizerId, string $fnum): ?string
	{
		$reference = $this->getDefinition()->getExternalReference();

		$found = $this->externalReferenceRepository->get([
			'sync_id'             => $synchronizerId,
			'reference_object'    => $reference->getReferenceObject(),
			'reference_attribute' => $reference->getReferenceAttribute(),
			'intern_id'           => $fnum,
		]);

		return empty($found) ? null : $found[0]->getReference();
	}

	/**
	 * Persist the file → PurchaseOrderNumber correspondence. It also guards against duplicate orders,
	 * so a failed write must abort rather than be logged and ignored.
	 */
	private function persistReference(string $fnum, string $orderNumber, int $synchronizerId): void
	{
		$reference = new ExternalReferenceEntity(0, 'fnum', $fnum, $orderNumber, $synchronizerId, self::HEADER_ENTITY, 'PurchaseOrderNumber');

		if (!$this->externalReferenceRepository->flush($reference))
		{
			Log::add('Failed to persist purchase order reference ' . $orderNumber . ' for file ' . $fnum, Log::ERROR, self::CHANNEL);

			throw new \RuntimeException(Text::sprintf('COM_EMUNDUS_SOFIS_PO_REFERENCE_SAVE_FAILED', $orderNumber));
		}
	}
}
