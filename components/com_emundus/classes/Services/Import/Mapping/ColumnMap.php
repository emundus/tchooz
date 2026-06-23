<?php
/**
 * @package     Tchooz\Services\Import\Mapping
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Mapping;

/**
 * Resolves a raw header found in a source file to a canonical field name.
 *
 * Two implementations are expected:
 *   - AliasColumnMap   : declarative, used by static entities (Contact, Organization).
 *   - BracketColumnMap : extracts content between [ ] from headers (Fabrik convention).
 */
interface ColumnMap
{
	/**
	 * Returns the typed descriptor for a canonical field, or null when unknown.
	 *
	 * Consumers that need the raw metadata (type, values, format, examples,
	 * validate flag) should call this rather than re-parsing describe() — it
	 * avoids the array → enum round-trip and exposes the source-of-truth
	 * objects directly.
	 */
	public function getDescriptor(string $canonical): ?FieldDescriptor;


	/**
	 * @return string[]  All canonical field names recognized by this map.
	 */
	public function canonicalFields(): array;

	/**
	 * @return string[]  Subset of canonical fields that must be present and non-empty.
	 */
	public function requiredFields(): array;

	/**
	 * Resolves a raw header to its canonical name, or null when unknown.
	 *
	 * Implementations are expected to be tolerant on case, accents and whitespace.
	 */
	public function resolve(string $rawHeader): ?string;

	/**
	 * Describes the map in a serializable shape so a frontend can document the
	 * expected file format (canonical fields, accepted raw header aliases,
	 * required flag, primitive type with a localized label, closed enum values,
	 * format hint and optional illustrative examples).
	 *
	 * Optional fields (values/format/examples) are omitted from each entry
	 * when not declared, to keep the JSON shape lean.
	 *
	 * @return array<int, array{
	 *     canonical:  string,
	 *     aliases:    string[],
	 *     required:   bool,
	 *     type:       string,
	 *     type_label: string,
	 *     values?:    array<int, array{value: string, label: string}>,
	 *     format?:    string,
	 *     examples?:  array<int, array{value: string, label: string}>
	 * }>
	 */
	public function describe(): array;
}
