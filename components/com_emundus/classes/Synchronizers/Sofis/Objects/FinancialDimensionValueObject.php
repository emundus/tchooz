<?php

namespace Tchooz\Synchronizers\Sofis\Objects;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\SynchronizerMappingObjectDefinition;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Services\Mapping\MappingService;
use Tchooz\Synchronizers\Mapping\SelfExecutingMappingObject;
use Tchooz\Synchronizers\MappingTransportInterface;


class FinancialDimensionValueObject extends AbstractSofisObject implements SelfExecutingMappingObject
{
	private const ENTITY = 'FinancialDimensionValues';

	private const SEARCH_FIELDS = ['FinancialDimension', 'DimensionValue'];

	private const KEY_LEGAL_ENTITY_FIELD = 'LegalEntityId';

	private ExternalReferenceRepository $externalReferenceRepository;

	public function __construct(?ExternalReferenceRepository $externalReferenceRepository = null)
	{
		parent::__construct();

		$this->externalReferenceRepository = $externalReferenceRepository ?? new ExternalReferenceRepository();
	}

	public function getName(): string
	{
		return 'financial_dimension_value';
	}

	protected function buildDefinition(): SynchronizerMappingObjectDefinition
	{
		return new SynchronizerMappingObjectDefinition(
			$this->getName(),
			'COM_EMUNDUS_SOFIS_FINANCIAL_DIMENSION_VALUE_OBJECT_LABEL',
			'/' . $this->entityPath(self::ENTITY),
			new ExternalReferenceEntity(0, 'dimension_value', '', '', null, self::ENTITY, 'DimensionValue'),
			[ApiMethodEnum::GET, ApiMethodEnum::POST, ApiMethodEnum::PATCH],
			[], // requiredFields (config params) — none
			[], // metadata
			[], // associations
			[
				(new StringField('FinancialDimension', Text::_('COM_EMUNDUS_SOFIS_FDV_DIMENSION_LABEL'), true))->setDefaultValue('L_NUM_CONTRAT'),
				new StringField('DimensionValue', Text::_('COM_EMUNDUS_SOFIS_FDV_VALUE_LABEL'), true),
				new StringField('Description', Text::_('COM_EMUNDUS_SOFIS_FDV_DESCRIPTION_LABEL'), true),
			]
		);
	}

	/**
	 * @throws \Exception
	 */
	public function execute(MappingEntity $mapping, ActionTargetEntity $context, MappingTransportInterface $transport): bool
	{
		$data = MappingService::getJsonFromMapping($mapping, $context);

		$keyValues = [];

		foreach (self::SEARCH_FIELDS as $keyField)
		{
			$keyValues[$keyField] = (string) ($data[$keyField] ?? '');
		}

		$this->debug('Financial dimension value sync for file ' . $context->getFile() . ' : ' . json_encode($keyValues));

		$existing = $this->findValue($transport, $keyValues);

		if ($existing === null)
		{
			$this->debug('Financial dimension value not found, creating it');

			// Required fields are a creation (POST) constraint — enforced only when creating.
			$this->validateRequiredFields($data);

			$this->create($transport, self::ENTITY, $this->collectMappedFields($data));
		}
		else
		{
			// Existing value: update only the non-key attributes, and only when they actually changed
			// (avoids overwriting on every idempotent task re-run).
			$updates = $this->collectMappedFields($data, null, self::SEARCH_FIELDS);
			$changed = array_keys(array_filter($updates, fn($value, $field) => (string) ($existing->$field ?? '') !== (string) $value, ARRAY_FILTER_USE_BOTH));

			if (empty($changed))
			{
				$this->debug('Financial dimension value found and unchanged, nothing to do');
			}
			else
			{
				$this->debug('Financial dimension value found, changed field(s) : ' . implode(', ', $changed));
				$this->patch($transport, self::ENTITY, $this->key($this->buildKey($keyValues, $existing)), $updates);
			}
		}

		$this->persistReference((string) $data['DimensionValue'], $mapping->getSynchronizerId());

		return true;
	}

	/**
	 * Persist the DimensionValue as the file's external reference (traceability). DimensionValue is a
	 * free mapped value — whatever the admin pointed at it — so it is stored as-is. A failed write is
	 * logged without aborting the successful remote sync.
	 */
	private function persistReference(string $dimensionValue, int $synchronizerId): void
	{
		$reference = new ExternalReferenceEntity(0, 'dimension_value', $dimensionValue, $dimensionValue, $synchronizerId, self::ENTITY, 'DimensionValue');

		if (!$this->externalReferenceRepository->flush($reference))
		{
			Log::add('Failed to persist DimensionValue reference for ' . $dimensionValue, Log::ERROR, self::CHANNEL);

			return;
		}

		$this->debug('Reference DimensionValue ' . $dimensionValue . ' persisted');
	}

	/**
	 * Full OData key of an existing value: the mapped identity plus the record's own legal entity.
	 *
	 * @return array<string,string>
	 */
	private function buildKey(array $keyValues, object $existing): array
	{
		$legalEntityField = self::KEY_LEGAL_ENTITY_FIELD;

		return [
			'FinancialDimension'         => $keyValues['FinancialDimension'] ?? '',
			self::KEY_LEGAL_ENTITY_FIELD => (string) ($existing->$legalEntityField ?? ''),
			'DimensionValue'             => $keyValues['DimensionValue'] ?? '',
		];
	}

	private function findValue(MappingTransportInterface $transport, array $keyValues): ?object
	{
		$results = $this->search($transport, self::ENTITY, $this->buildFilter($keyValues));

		return $results[0] ?? null;
	}

}
