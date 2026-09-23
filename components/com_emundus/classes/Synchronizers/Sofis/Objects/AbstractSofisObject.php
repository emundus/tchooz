<?php

namespace Tchooz\Synchronizers\Sofis\Objects;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\MappingResolution;
use Tchooz\Synchronizers\Mapping\AbstractMappingObject;
use Tchooz\Synchronizers\MappingTransportInterface;

/**
 * Shared behaviour for Sofis (Microsoft Dynamics 365 F&O) mapping objects: OData transport helpers
 * (search/create/patch with cross-company) and filter/key builders. The FONDO-scoped values
 * (dataAreaId, vendor group, tax group, currency, party type) are not hardcoded — concrete objects
 * read them from the editable synchronizer configuration so the client stays autonomous.
 *
 * Failures are thrown, not retried here: the whole action is asynchronous and re-run by eMundus'
 * task management, so per-operation retry would be redundant (the choreography is idempotent on
 * re-run — the SIRET search recovers an already-created vendor).
 *
 * Sofis objects are self-executing (see SelfExecutingMappingObject), so the generic executor entry
 * points (resolveExistence / buildPayload) are never called on them and throw if reached.
 */
abstract class AbstractSofisObject extends AbstractMappingObject
{
	protected const CHANNEL = 'com_emundus.sofis';

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.sofis.php'], Log::ALL, [self::CHANNEL]);
	}

	/**
	 * GET an OData entity set with a filter, always scoped to the FONDO company (cross-company).
	 *
	 * @return array The `value` array of matching records (empty if none).
	 * @throws \Exception
	 */
	protected function search(MappingTransportInterface $transport, string $entitySet, string $filter): array
	{
		$url      = 'data/' . $entitySet . '?$filter=' . rawurlencode($filter) . '&cross-company=true';
		$response = $transport->get($url);

		if (empty($response) || !in_array($response['status'], [200, 201]))
		{
			Log::add('Sofis search on ' . $entitySet . ' failed : ' . json_encode($response), Log::ERROR, self::CHANNEL);

			throw new \RuntimeException($this->remoteError('COM_EMUNDUS_SOFIS_SEARCH_FAILED', $entitySet, $response));
		}

		return $response['data']->value ?? [];
	}

	/**
	 * POST a new record on an OData entity set (cross-company). Returns the created record.
	 *
	 * @throws \Exception
	 */
	protected function create(MappingTransportInterface $transport, string $entitySet, array $payload): object
	{
		$url      = 'data/' . $entitySet . '?cross-company=true';
		$response = $transport->post($url, json_encode($payload), ['Content-Type' => 'application/json', 'Accept' => 'application/json']);

		if (empty($response) || !in_array($response['status'], [200, 201]) || empty($response['data']))
		{
			Log::add('Sofis create on ' . $entitySet . ' failed : ' . json_encode($response) . ' with sent payload ' . json_encode($payload), Log::ERROR, self::CHANNEL);

			throw new \RuntimeException($this->remoteError('COM_EMUNDUS_SOFIS_CREATE_FAILED', $entitySet, $response));
		}

		return $response['data'];
	}

	/**
	 * PATCH an existing record identified by its OData key (cross-company).
	 *
	 * @throws \Exception
	 */
	protected function patch(MappingTransportInterface $transport, string $entitySet, string $key, array $payload): void
	{
		$url      = 'data/' . $entitySet . $key . '?cross-company=true';
		$response = $transport->patch($url, json_encode($payload));

		if (empty($response) || !in_array($response['status'], [200, 201, 204]))
		{
			Log::add('Sofis update on ' . $entitySet . ' failed : ' . json_encode($response), Log::ERROR, self::CHANNEL);

			throw new \RuntimeException($this->remoteError('COM_EMUNDUS_SOFIS_UPDATE_FAILED', $entitySet, $response));
		}
	}

	/**
	 * Failure message for an ERP call, carrying the functional reason when Dynamics sends one.
	 *
	 * F&O buries the useful part (an X++ infolog: unapproved vendor, unknown item, missing property…)
	 * inside the OData error body. Surfacing it in the exception rather than leaving it in the log
	 * alone is what makes a failure actionable for the support team.
	 */
	private function remoteError(string $langKey, string $entitySet, array $response): string
	{
		$reason = $this->extractRemoteReason($response);

		if ($reason === '')
		{
			return Text::sprintf($langKey, $entitySet);
		}

		return Text::sprintf($langKey . '_REASON', $entitySet, $reason);
	}

	/**
	 * Pull `error.innererror.message` (or `error.message`) out of an OData error payload.
	 */
	private function extractRemoteReason(array $response): string
	{
		$raw = $response['error_details'] ?? $response['error'] ?? null;

		if (is_string($raw) && $raw !== '')
		{
			$raw = json_decode($raw, true);
		}

		if (!is_array($raw) || empty($raw['error']))
		{
			return '';
		}

		$reason = $raw['error']['innererror']['message'] ?? $raw['error']['message'] ?? '';

		return trim((string) $reason);
	}

	/**
	 * Build an OData `$filter` string from equality conditions (value = string), FONDO-scoped fields
	 * appended last for homogeneity between GET and POST.
	 *
	 * @param array<string,string> $equals field => value
	 */
	protected function buildFilter(array $equals): string
	{
		$parts = [];

		foreach ($equals as $field => $value)
		{
			$parts[] = $field . " eq '" . str_replace("'", "''", $value) . "'";
		}

		return implode(' and ', $parts);
	}

	/**
	 * Build an OData composite key segment, e.g. (dataAreaId='FOND',VendorAccountNumber='F-FD-1').
	 *
	 * @param array<string,string> $keyParts
	 */
	protected function key(array $keyParts): string
	{
		$parts = [];

		foreach ($keyParts as $field => $value)
		{
			$parts[] = $field . "='" . str_replace("'", "''", $value) . "'";
		}

		return '(' . implode(',', $parts) . ')';
	}

	/**
	 * Enforce required fields from the definition (single source of truth). Required is a creation
	 * (POST) constraint, so concrete objects call this only when they actually create a record. A
	 * $group limits the check to one entity group (e.g. bank fields for a vendor-only bank update).
	 *
	 * @throws \DomainException when a required field is missing.
	 */
	protected function validateRequiredFields(array $data, ?string $group = null): void
	{
		foreach ($this->getDefinition()->getAvailableFields() as $field)
		{
			if ($group !== null && $field->getGroup()?->getName() !== $group)
			{
				continue;
			}

			if ($field->isRequired() && trim((string) ($data[$field->getName()] ?? '')) === '')
			{
				throw new \DomainException(Text::sprintf('COM_EMUNDUS_SOFIS_FIELD_REQUIRED', $field->getLabel()));
			}
		}
	}

	/**
	 * Build a payload by passing through every mapped field of the definition, optionally limited to
	 * one entity group and excluding some field names. Adding an attribute to a payload is therefore
	 * just declaring one more availableField — no payload edit.
	 *
	 * @param   array<string,mixed>  $data            Mapped data (MappingService output).
	 * @param   string|null          $group           Only collect fields of this entity group.
	 * @param   array<string>        $excludedFields  Field names to leave out (e.g. immutable keys).
	 *
	 * @return array<string,mixed>
	 */
	protected function collectMappedFields(array $data, ?string $group = null, array $excludedFields = []): array
	{
		$payload = [];

		foreach ($this->getDefinition()->getAvailableFields() as $field)
		{
			$name = $field->getName();

			if ($group !== null && $field->getGroup()?->getName() !== $group)
			{
				continue;
			}

			if (in_array($name, $excludedFields, true) || !array_key_exists($name, $data))
			{
				continue;
			}

			$payload[$name] = $data[$name];
		}

		return $payload;
	}

	// -------------------------------------------------------------------------
	// Not applicable — Sofis objects are self-executing (see SelfExecutingMappingObject).
	// -------------------------------------------------------------------------

	public function resolveExistence(MappingEntity $mapping, ActionTargetEntity $context, MappingTransportInterface $transport): MappingResolution
	{
		throw new \LogicException('Sofis objects are self-executing; resolveExistence() is not used.');
	}

	public function buildPayload(array $mappedData, MappingEntity $mapping, ActionTargetEntity $context): array
	{
		throw new \LogicException('Sofis objects are self-executing; buildPayload() is not used.');
	}
}
