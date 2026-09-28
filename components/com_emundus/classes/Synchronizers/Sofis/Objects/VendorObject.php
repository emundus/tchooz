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
 * Sofis vendor (INT-01): synchronizes a project holder (personne morale française) as a Dynamics
 * VendorsV2 record plus its default bank account.
 *
 * Identity is the SIRET (from a form field); existence is checked live by an OData search on
 * VendorsV2. All target values — including the FONDO-scoped ones (dataAreaId, VendorGroupId,
 * SalesTaxGroupCode, CurrencyCode, VendorPartyType) — are regular mapped fields carrying a default
 * value (see Field::setDefaultValue): each mapping gets them pre-filled but can override them (e.g.
 * to target another organization), so the client stays autonomous with no hardcoded value.
 *
 * Choreography:
 *   - existing + default bank account (BankAccountId) already matches the file IBAN → nothing to do;
 *   - existing + IBAN changed → create the new bank account, then update the vendor's default;
 *   - not existing → create the vendor (ERP generates VendorAccountNumber), persist the
 *     SIRET → VendorAccountNumber correspondence, then create the bank account (which becomes the
 *     default — no explicit vendor update needed for a brand-new vendor).
 */
class VendorObject extends AbstractSofisObject implements SelfExecutingMappingObject
{
	private const ENTITY = 'VendorsV2';

	private const BANK_ENTITY           = 'VendorBankAccounts';
	private const BANK_ACCOUNT_ID_FIELD = 'VendorBankAccountId';

	// FieldGroup names used to route each mapped field to its Dynamics entity (dynamic payloads).
	private const GROUP_VENDOR = 'vendor';
	private const GROUP_BANK   = 'bank';

	private ExternalReferenceRepository $externalReferenceRepository;

	public function __construct(?ExternalReferenceRepository $externalReferenceRepository = null)
	{
		parent::__construct();

		$this->externalReferenceRepository = $externalReferenceRepository ?? new ExternalReferenceRepository();
	}

	public function getName(): string
	{
		return 'vendor';
	}

	protected function buildDefinition(): SynchronizerMappingObjectDefinition
	{
		// Each field is tagged with the Dynamics entity it feeds (see collectMappedFields): the
		// payloads pass through every mapped field of the matching group, so adding an attribute is
		// just adding one availableField here — no payload edit.
		$vendorGroup = new FieldGroup(self::GROUP_VENDOR, Text::_('COM_EMUNDUS_SOFIS_VENDOR_GROUP_LABEL'));
		$bankGroup   = new FieldGroup(self::GROUP_BANK, Text::_('COM_EMUNDUS_SOFIS_BANK_GROUP_LABEL'));

		return new SynchronizerMappingObjectDefinition(
			'vendor',
			'COM_EMUNDUS_SOFIS_VENDOR_OBJECT_LABEL',
			'/data/VendorsV2',
			new ExternalReferenceEntity(0, 'siret', '', '', null, self::ENTITY, 'VendorAccountNumber'),
			[ApiMethodEnum::GET, ApiMethodEnum::POST, ApiMethodEnum::PATCH],
			[], // requiredFields (config params) — none
			[], // metadata
			[], // associations
			[
				// Vendor entity (VendorsV2) — field names ARE the Dynamics field names (passthrough).
				new NumericField('SiretNumber', Text::_('COM_EMUNDUS_SOFIS_VENDOR_FIELD_SIRET'), true, $vendorGroup),
				new StringField('VendorOrganizationName', Text::_('COM_EMUNDUS_SOFIS_VENDOR_FIELD_NAME'), true, $vendorGroup),
				(new StringField('dataAreaId', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_DATA_AREA_ID_LABEL'), true, $vendorGroup))->setDefaultValue('FOND'),
				(new StringField('VendorGroupId', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_VENDOR_GROUP_ID_LABEL'), true, $vendorGroup))->setDefaultValue('FRN_P_FR'),
				(new StringField('SalesTaxGroupCode', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_SALES_TAX_GROUP_LABEL'), true, $vendorGroup))->setDefaultValue('GTAXE_VSB'),
				(new StringField('CurrencyCode', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_CURRENCY_CODE_LABEL'), true, $vendorGroup, 3, 3))->setDefaultValue('EUR'),
				(new StringField('VendorPartyType', Text::_('COM_EMUNDUS_INTEGRATIONS_SOFIS_VENDOR_PARTY_TYPE_LABEL'), true, $vendorGroup))->setDefaultValue('Organization'),

				// Postal address — carried by VendorsV2 itself, hence the vendor group. Optional: a
				// mapping that leaves them unmapped keeps creating vendors without an address.
				new StringField('AddressDescription', Text::_('COM_EMUNDUS_SOFIS_VENDOR_FIELD_ADDRESS_DESCRIPTION'), false, $vendorGroup),
				new StringField('AddressStreetNumber', Text::_('COM_EMUNDUS_SOFIS_VENDOR_FIELD_ADDRESS_STREET_NUMBER'), false, $vendorGroup),
				new StringField('AddressStreet', Text::_('COM_EMUNDUS_SOFIS_VENDOR_FIELD_ADDRESS_STREET'), false, $vendorGroup),
				new StringField('AddressZipCode', Text::_('COM_EMUNDUS_SOFIS_VENDOR_FIELD_ADDRESS_ZIPCODE'), false, $vendorGroup),
				new StringField('AddressCity', Text::_('COM_EMUNDUS_SOFIS_VENDOR_FIELD_ADDRESS_CITY'), false, $vendorGroup),
				(new StringField('AddressCountryRegionId', Text::_('COM_EMUNDUS_SOFIS_VENDOR_FIELD_ADDRESS_COUNTRY'), false, $vendorGroup))->setDefaultValue('FRA'),
				new StringField('AddressLocationRoles', Text::_('COM_EMUNDUS_SOFIS_VENDOR_FIELD_ADDRESS_ROLES'), false, $vendorGroup),

				// Bank account entity — field names ARE the Dynamics bank field names (passthrough).
				new StringField('IBAN', Text::_('COM_EMUNDUS_SOFIS_VENDOR_FIELD_IBAN'), true, $bankGroup),
				new StringField('SWIFTCode', Text::_('COM_EMUNDUS_SOFIS_VENDOR_FIELD_SWIFT'), false, $bankGroup),
			]
		);
	}

	public function execute(MappingEntity $mapping, ActionTargetEntity $context, MappingTransportInterface $transport): bool
	{
		$data = MappingService::getJsonFromMapping($mapping, $context);

		$siret         = trim((string) ($data['SiretNumber'] ?? ''));
		$iban          = trim((string) ($data['IBAN'] ?? ''));
		$bankAccountId = substr($iban, -10);

		$existing = $this->findVendor($transport, $siret, $data);

		if ($existing !== null)
		{
			$vendorAccountNumber = (string) $existing->VendorAccountNumber;

			// Keep the SIRET → VendorAccountNumber correspondence even when the vendor already exists.
			$this->persistReference($siret, $vendorAccountNumber, $mapping->getSynchronizerId());

			// Default bank account already matches the file IBAN → nothing else to do.
			if ((string) ($existing->BankAccountId ?? '') === $bankAccountId)
			{
				return true;
			}

			$this->createBankAccount($transport, $vendorAccountNumber, $data, $bankAccountId);

			return true;
		}

		// New vendor: create it (ERP generates the account number), keep the correspondence, add the
		// bank account — which becomes the default for a brand-new vendor.
		$vendorAccountNumber = $this->createVendor($transport, $data);
		$this->persistReference($siret, $vendorAccountNumber, $mapping->getSynchronizerId());
		$this->createBankAccount($transport, $vendorAccountNumber, $data, $bankAccountId);

		return true;
	}

	private function findVendor(MappingTransportInterface $transport, string $siret, array $data): ?object
	{
		$filter = $this->buildFilter([
			'VendorGroupId' => (string) ($data['VendorGroupId'] ?? ''),
			'SiretNumber'   => $siret,
			'dataAreaId'    => (string) ($data['dataAreaId'] ?? ''),
		]);

		$results = $this->search($transport, self::ENTITY, $filter);

		return $results[0] ?? null;
	}

	/**
	 * @throws \Exception
	 */
	private function createVendor(MappingTransportInterface $transport, array $data): string
	{
		// Required fields are a creation (POST) constraint — enforced only when we actually create.
		$this->validateRequiredFields($data, self::GROUP_VENDOR);

		$created             = $this->create($transport, self::ENTITY, $this->buildVendorPayload($data));
		$vendorAccountNumber = (string) ($created->VendorAccountNumber ?? '');

		if ($vendorAccountNumber === '')
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_SOFIS_VENDOR_NO_ACCOUNT_NUMBER'));
		}

		return $vendorAccountNumber;
	}

	private function buildVendorPayload(array $data): array
	{
		return $this->collectMappedFields($data, self::GROUP_VENDOR);
	}

	/**
	 * Create the bank account, flagged as the vendor's default. Its id (VendorBankAccountId) is set to
	 * the last 10 IBAN chars (INT-01).
	 *
	 * @throws \Exception
	 */
	private function createBankAccount(MappingTransportInterface $transport, string $vendorAccountNumber, array $data, string $bankAccountId): void
	{
		// Required bank fields are enforced only here — a vendor-only update must not require them.
		$this->validateRequiredFields($data, self::GROUP_BANK);

		$payload = $this->collectMappedFields($data, self::GROUP_BANK);

		$payload['dataAreaId']                = (string) ($data['dataAreaId'] ?? '');
		$payload['VendorAccountNumber']       = $vendorAccountNumber;
		$payload[self::BANK_ACCOUNT_ID_FIELD] = $bankAccountId;
		// F&O NoYes fields are quoted strings over OData ("Yes"/"No"), not JSON booleans.
		$payload['IsDefaultBankAccount']      = 'Yes';

		$this->create($transport, self::BANK_ENTITY, $payload);
	}

	/**
	 * Persist the SIRET → VendorAccountNumber correspondence. Traceability only (existence is checked
	 * live by SIRET), so a failed write is logged without aborting the successful remote creation.
	 */
	private function persistReference(string $siret, string $vendorAccountNumber, int $synchronizerId): void
	{
		$reference = new ExternalReferenceEntity(0, 'siret', $siret, $vendorAccountNumber, $synchronizerId, self::ENTITY, 'VendorAccountNumber');

		if (!$this->externalReferenceRepository->flush($reference))
		{
			Log::add('Failed to persist SIRET -> VendorAccountNumber reference for SIRET ' . $siret, Log::ERROR, self::CHANNEL);
		}
	}
}
