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
 * Read-only description of a canonical import field.
 *
 * Carries the data consumed by the frontend through ColumnMap::describe():
 * which raw headers are accepted, whether the field is required, the
 * expected primitive type, the closed list of values when applicable, an
 * optional format hint and an optional list of illustrative examples.
 *
 * For ENUM types `$values` is a list of {value, label} pairs (closed list).
 * For non-ENUM types `$examples` carries optional illustrative samples
 * (open list, displayed as helper text alongside the format hint).
 */
final class FieldDescriptor
{
	/**
	 * @param string                                               $canonical
	 * @param string[]                                             $aliases
	 * @param bool                                                 $required
	 * @param FieldTypeEnum                                        $type
	 * @param array<int, array{value: string, label: string}>|null $values    Closed list (ENUM only).
	 * @param string|null                                          $format
	 * @param array<int, array{value: string, label: string}>|null $examples  Open list (non-ENUM).
	 * @param bool                                                 $validate  When false, the pipeline's
	 *                                                                        generic TypeValidator skips
	 *                                                                        this field — the type/format
	 *                                                                        remains in the documentation
	 *                                                                        but is not enforced at runtime.
	 *                                                                        Escape hatch for legacy data
	 *                                                                        or tolerant inputs.
	 * @param string|null                                          $label     Translation key (com_emundus.ini)
	 *                                                                        providing a human-readable name for
	 *                                                                        the field. Null falls back to the
	 *                                                                        canonical name on the frontend.
	 */
	public function __construct(
		public readonly string         $canonical,
		public readonly array          $aliases,
		public readonly bool           $required,
		public readonly FieldTypeEnum  $type,
		public readonly ?array         $values = null,
		public readonly ?string        $format = null,
		public readonly ?array         $examples = null,
		public readonly bool           $validate = true,
		public readonly ?string        $label = null
	) {}

	/**
	 * Serializable shape sent to the frontend. Empty/null optionals are
	 * omitted to keep the JSON shape lean.
	 */
	public function toArray(): array
	{
		$out = [
			'canonical'  => $this->canonical,
			'aliases'    => $this->aliases,
			'required'   => $this->required,
			'type'       => $this->type->value,
			'type_label' => $this->type->getLabel(),
		];

		if ($this->label !== null && $this->label !== '')
		{
			$out['label'] = $this->label;
		}

		if ($this->values !== null)
		{
			$out['values'] = $this->values;
		}

		if ($this->format !== null && $this->format !== '')
		{
			$out['format'] = $this->format;
		}

		if ($this->examples !== null && $this->examples !== [])
		{
			$out['examples'] = $this->examples;
		}

		return $out;
	}
}
