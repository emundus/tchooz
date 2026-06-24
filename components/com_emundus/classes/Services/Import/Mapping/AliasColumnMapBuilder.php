<?php
/**
 * @package     Tchooz\Services\Import\Mapping
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Mapping;

use Tchooz\Enums\Import\FieldTypeEnum;

/**
 * Fluent builder for AliasColumnMap.
 *
 * Conflicts are surfaced eagerly: if the same alias resolves to two distinct
 * canonical fields, build() throws — so the developer fixes the map rather
 * than discovering it at runtime on a customer's file.
 */
final class AliasColumnMapBuilder
{
	/** @var array<string, array{
	 *     label:    ?string,
	 *     required: bool,
	 *     aliases:  string[],
	 *     type:     FieldTypeEnum,
	 *     values:   array<string>|string|null,
	 *     format:   ?string,
	 *     examples: array<mixed>|null,
	 *     validate: bool
	 * }>
	 */
	private array $fields = [];

	/**
	 * Declares a canonical field and the raw headers that resolve to it.
	 *
	 * @param   string                            $canonical  Canonical name used everywhere in code.
	 * @param   string[]                          $aliases    Raw headers expected in source files.
	 *                                                        The canonical name itself is always
	 *                                                        accepted, no need to repeat it.
	 * @param   bool                              $required   Pipeline rejects rows missing this field.
	 * @param   FieldTypeEnum                     $type       Primitive type hint exposed to the frontend.
	 * @param   array<string>|string|null         $values     Required iff $type === ENUM. Accepts either
	 *                                                        a flat string[] of allowed values, or the
	 *                                                        FQCN of a BackedEnum which is then expanded
	 *                                                        to its cases (and ::getLabel() when available).
	 * @param   string|null                       $format     Optional free-form format hint (e.g.
	 *                                                        "iso-3166-1-alpha-2", "YYYY-MM-DD").
	 * @param   array<mixed>|null                 $examples   Illustrative samples for non-ENUM types.
	 *                                                        Accepts three forms, all normalized to
	 *                                                        {value, label} pairs:
	 *                                                          - string[]:      value = label
	 *                                                          - array<v=>l>:   associative (value => label)
	 *                                                          - structured:    array<{value, label}>
	 * @param   bool                              $validate   Set to false to keep the type as documentation
	 *                                                        while disabling the pipeline's generic
	 *                                                        TypeValidator on this field (legacy data,
	 *                                                        permissive inputs).
	 * @param   string|null                       $label      Translation key (stored in com_emundus.ini)
	 *                                                        giving a human-readable name for the field.
	 *                                                        Null falls back to the canonical name on the
	 *                                                        frontend.
	 */
	public function field(
		string                       $canonical,
		array                        $aliases = [],
		bool                         $required = false,
		FieldTypeEnum                $type = FieldTypeEnum::STRING,
		array|string|null            $values = null,
		?string                      $format = null,
		?array                       $examples = null,
		bool                         $validate = true,
		?string                      $label = null,
	): self
	{
		if ($canonical === '')
		{
			throw new \InvalidArgumentException('Canonical field name cannot be empty.');
		}

		if (isset($this->fields[$canonical]))
		{
			throw new \InvalidArgumentException(sprintf('Canonical field "%s" declared twice.', $canonical));
		}

		// Tight contract: ENUM ↔ values, otherwise neither.
		if ($type === FieldTypeEnum::ENUM && $values === null)
		{
			throw new \InvalidArgumentException(sprintf(
				'Field "%s" is declared as ENUM but no values were provided.', $canonical
			));
		}
		if ($type !== FieldTypeEnum::ENUM && $values !== null)
		{
			throw new \InvalidArgumentException(sprintf(
				'Field "%s" carries values but its type is "%s" instead of ENUM.',
				$canonical,
				$type->value
			));
		}

		// ENUM uses its closed `values` list — illustrative `examples` would be redundant.
		if ($type === FieldTypeEnum::ENUM && $examples !== null)
		{
			throw new \InvalidArgumentException(sprintf(
				'Field "%s" is declared as ENUM; use `values` to expose the closed list — examples are not allowed.',
				$canonical
			));
		}

		$this->fields[$canonical] = [
			'label'    => $label,
			'required' => $required,
			'aliases'  => $aliases,
			'type'     => $type,
			'values'   => $values,
			'format'   => $format,
			'examples' => $examples,
			'validate' => $validate,
		];

		return $this;
	}

	public function build(): AliasColumnMap
	{
		$canonicalOrder = array_keys($this->fields);
		$requiredFields = [];
		$descriptors    = [];
		$reverseIndex   = [];

		foreach ($this->fields as $name => $config)
		{
			if ($config['required'])
			{
				$requiredFields[] = $name;
			}

			$aliases = $config['aliases'];
			if ($config['label'] !== null && $config['label'] !== '')
			{
				array_unshift($aliases, $config['label']);
			}
			$aliases = $this->cleanAliases($aliases);

			$descriptors[$name] = new FieldDescriptor(
				canonical: $name,
				aliases: $aliases,
				required: $config['required'],
				type: $config['type'],
				values: $config['values']   !== null ? $this->resolveEnumValues($config['values']) : null,
				format: $config['format'],
				examples: $config['examples'] !== null ? $this->resolveExamples($config['examples']) : null,
				validate: $config['validate'],
				label: $config['label']
			);

			// The canonical name itself is a valid header.
			$this->indexAlias($reverseIndex, $name, $name);

			foreach ($aliases as $alias)
			{
				$this->indexAlias($reverseIndex, $alias, $name);
			}
		}

		return new AliasColumnMap($canonicalOrder, $requiredFields, $reverseIndex, $descriptors);
	}

	/**
	 * @param string[] $aliases
	 *
	 * @return string[]
	 */
	private function cleanAliases(array $aliases): array
	{
		$aliases = array_filter($aliases, static fn ($a) => is_string($a) && trim($a) !== '');

		return array_values(array_unique($aliases));
	}

	/**
	 * Normalizes a values declaration into a list of {value, label} pairs.
	 *
	 * @param array<string>|string $values
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	private function resolveEnumValues(array|string $values): array
	{
		// Enum class name: expand to cases and use getLabel() when available.
		if (is_string($values))
		{
			if (!enum_exists($values))
			{
				throw new \InvalidArgumentException(sprintf(
					'Values "%s" is neither an array nor a known enum class.',
					$values
				));
			}

			$out = [];
			foreach ($values::cases() as $case)
			{
				$rawValue = $case instanceof \BackedEnum ? $case->value : $case->name;
				$label    = method_exists($case, 'getLabel') ? $case->getLabel() : $case->name;
				$out[]    = [
					'value' => (string) $rawValue,
					'label' => (string) $label,
				];
			}

			return $out;
		}

		// Flat array: each entry is its own label.
		$out = [];
		foreach ($values as $entry)
		{
			$entry = (string) $entry;
			$out[] = [
				'value' => $entry,
				'label' => $entry,
			];
		}

		return $out;
	}

	/**
	 * Normalizes the three accepted examples shapes into {value, label} pairs:
	 *   - string[]                              → value = label
	 *   - associative array<value => label>    → as-is
	 *   - structured array<{value, label}>     → as-is
	 *
	 * @param array<mixed> $examples
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	private function resolveExamples(array $examples): array
	{
		$out = [];

		foreach ($examples as $key => $entry)
		{
			if (is_int($key))
			{
				// indexed: either a plain scalar or a structured {value, label} array
				if (is_array($entry))
				{
					if (!isset($entry['value']))
					{
						throw new \InvalidArgumentException(
							'Structured example entries must have a "value" key.'
						);
					}
					$out[] = [
						'value' => (string) $entry['value'],
						'label' => isset($entry['label']) ? (string) $entry['label'] : (string) $entry['value'],
					];
				}
				else
				{
					$out[] = [
						'value' => (string) $entry,
						'label' => (string) $entry,
					];
				}
			}
			else
			{
				// associative: $key is the value, $entry is the label
				$out[] = [
					'value' => (string) $key,
					'label' => (string) $entry,
				];
			}
		}

		return $out;
	}

	/**
	 * @param array<string, string> $reverseIndex
	 */
	private function indexAlias(array &$reverseIndex, string $alias, string $canonical): void
	{
		$key = HeaderNormalizer::normalize($alias);

		if ($key === '')
		{
			return;
		}

		if (isset($reverseIndex[$key]) && $reverseIndex[$key] !== $canonical)
		{
			throw new \InvalidArgumentException(sprintf(
				'Alias "%s" already resolves to "%s", cannot remap it to "%s".',
				$alias,
				$reverseIndex[$key],
				$canonical
			));
		}

		$reverseIndex[$key] = $canonical;
	}
}
