<?php
/**
 * @package     Tchooz\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import;

use Joomla\CMS\Language\Text;
use Tchooz\Enums\Import\FieldTypeEnum;
use Tchooz\Services\Import\Mapping\ColumnMap;
use Tchooz\Services\Import\Mapping\FieldDescriptor;
use Tchooz\Services\Import\Referential\ReferentialProviderInterface;

/**
 * Normalizes REFERENTIAL cells to their canonical value (the id).
 *
 * A cell may arrive in three shapes:
 *   - "Label [id]"
 *   - "Label"
 *   - "id"
 *
 * Resolution is done whole-string first, and only then by stripping the
 * trailing "[token]". This order is what disambiguates a label that legitimately
 * contains brackets (an organization literally named "Orga [007]"): the whole
 * string matches a known label, so its brackets are never mistaken for an id.
 */
final class ReferentialValueDecoder
{
	/**
	 * @param array<string, mixed> $row  Canonical row (keyed by canonical names).
	 *
	 * @return array<string, mixed>
	 */
	public function decode(array $row, ColumnMap $columnMap, ImportContext $context): array
	{
		foreach ($row as $canonical => $value)
		{
			$descriptor = $columnMap->getDescriptor($canonical);

			if ($descriptor === null
				|| $descriptor->type !== FieldTypeEnum::REFERENTIAL
				|| $descriptor->referential === null)
			{
				continue;
			}

			$row[$canonical] = $this->resolveCell($value, $descriptor, $context);
		}

		return $row;
	}

	private function resolveCell(mixed $value, FieldDescriptor $descriptor, ImportContext $context): mixed
	{
		if (!is_string($value))
		{
			return $value;
		}

		$referential = $descriptor->referential;

		$trimmed = trim($value);
		if ($trimmed === '')
		{
			return $value;
		}

		$resolved = $referential->resolve($trimmed);
		if ($resolved !== null)
		{
			return $resolved;
		}

		if (preg_match('/^(.*)\[([^\[\]]+)\]\s*$/', $trimmed, $matches) === 1)
		{
			$resolved = $referential->resolve(trim($matches[2]));
			if ($resolved !== null)
			{
				$this->warnOnLabelMismatch($context, $descriptor, $value, trim($matches[1]), $resolved);

				return $resolved;
			}
		}

		return $value;
	}

	/**
	 * The "Label [id]" form lets the typed label disagree with the id it carries
	 * (e.g. "Angleterre [FR]" → France). The value still resolves on the id, but
	 * we surface a non-blocking warning so the integrator can double-check.
	 */
	private function warnOnLabelMismatch(
		ImportContext     $context,
		FieldDescriptor   $descriptor,
		string            $cell,
		string            $typedLabel,
		string            $resolvedValue
	): void
	{
		if ($typedLabel === '')
		{
			return;
		}

		$canonicalLabel = $descriptor->referential->labelFor($resolvedValue);
		if ($canonicalLabel === null || $this->normalize($typedLabel) === $this->normalize($canonicalLabel))
		{
			return;
		}

		$context->addWarning(Text::sprintf(
			'COM_EMUNDUS_IMPORT_REFERENTIAL_LABEL_MISMATCH',
			trim($cell),
			$canonicalLabel,
			$descriptor->referential->getLabel()
		));
	}

	private function normalize(string $value): string
	{
		return mb_strtolower(trim($value));
	}
}