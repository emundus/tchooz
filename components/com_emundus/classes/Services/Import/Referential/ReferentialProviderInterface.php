<?php
/**
 * @package     Tchooz\Services\Import\Referential
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Referential;

/**
 * Source of a dynamic closed list (a "referential") backing an import field.
 */
interface ReferentialProviderInterface
{
	/**
	 * Stable identifier of the referential (e.g. "organizations").
	 */
	public function getKey(): string;

	/**
	 * Human-readable, localized label.
	 */
	public function getLabel(): string;

	/**
	 * The closed list as {value, label} pairs.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public function getEntries(): array;

	/**
	 * Normalizes a raw input to the referential's canonical value, accepting
	 * either the value itself (e.g. an id picked from the XLSX sheet) or its
	 * label (e.g. a name typed in a CSV).
	 */
	public function resolve(string $input): ?string;

	/**
	 * True when $input is not a known value but matches more than one label, so
	 * it cannot be resolved to a single entry.
	 */
	public function isAmbiguousLabel(string $input): bool;

	/**
	 * True when $input is a known value (id) that is ALSO the label of a
	 * different entry — so it cannot be resolved unambiguously without the
	 * "Label [id]" form.
	 */
	public function isAmbiguousValue(string $input): bool;

	/**
	 * The canonical label of a value, or null when the value is unknown.
	 */
	public function labelFor(string $value): ?string;
}