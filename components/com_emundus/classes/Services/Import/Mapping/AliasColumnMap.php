<?php
/**
 * @package     Tchooz\Services\Import\Mapping
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Mapping;

/**
 * Declarative ColumnMap built from canonical → aliases pairs.
 *
 * Use the fluent builder:
 *
 *   AliasColumnMap::create()
 *       ->field('email',     aliases: ['Email', 'Adresse mail'], required: true, type: FieldTypeEnum::EMAIL)
 *       ->field('firstname', aliases: ['Prénom'],                required: true)
 *       ->build();
 *
 * Each canonical name is itself accepted as a valid header (so a file using
 * canonical names directly resolves without extra configuration).
 */
final class AliasColumnMap implements ColumnMap
{
	/** @var string[] canonical field names, ordered as declared */
	private array $canonicalFields;

	/** @var string[] canonical names flagged as required */
	private array $requiredFields;

	/** @var array<string, string>  normalized header → canonical */
	private array $reverseIndex;

	/** @var array<string, FieldDescriptor>  canonical → full descriptor */
	private array $descriptors;

	/**
	 * @param string[]                        $canonicalFields
	 * @param string[]                        $requiredFields
	 * @param array<string, string>           $reverseIndex
	 * @param array<string, FieldDescriptor>  $descriptors
	 */
	public function __construct(
		array $canonicalFields,
		array $requiredFields,
		array $reverseIndex,
		array $descriptors
	) {
		$this->canonicalFields = $canonicalFields;
		$this->requiredFields  = $requiredFields;
		$this->reverseIndex    = $reverseIndex;
		$this->descriptors     = $descriptors;
	}

	public static function create(): AliasColumnMapBuilder
	{
		return new AliasColumnMapBuilder();
	}

	public function canonicalFields(): array
	{
		return $this->canonicalFields;
	}

	public function requiredFields(): array
	{
		return $this->requiredFields;
	}

	public function resolve(string $rawHeader): ?string
	{
		$key = HeaderNormalizer::normalize($rawHeader);

		if ($key === '')
		{
			return null;
		}

		return $this->reverseIndex[$key] ?? null;
	}

	public function getDescriptor(string $canonical): ?FieldDescriptor
	{
		return $this->descriptors[$canonical] ?? null;
	}

	public function describe(): array
	{
		$out = [];

		foreach ($this->canonicalFields as $canonical)
		{
			$out[] = $this->descriptors[$canonical]->toArray();
		}

		return $out;
	}
}
