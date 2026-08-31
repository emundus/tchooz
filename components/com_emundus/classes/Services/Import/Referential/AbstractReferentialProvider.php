<?php
/**
 * @package     Tchooz\Services\Import\Referential
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Referential;

/**
 * Shared resolution logic for referential providers.
 */
abstract class AbstractReferentialProvider implements ReferentialProviderInterface
{
	/**
	 * @var array<int, array{value: string, label: string}>|null
	 */
	private ?array $entries = null;

	/**
	 * @var array<string, string>|null  normalized key => canonical value
	 */
	private ?array $valueByKey = null;

	/**
	 * @var array<string, array<int, string>>|null  normalized label => values sharing that label
	 */
	private ?array $valuesByLabel = null;

	/**
	 * @var array<string, string>|null  normalized value => its canonical label
	 */
	private ?array $labelByValue = null;

	/**
	 * Loads the raw closed list. Called once; the result is memoized.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	abstract protected function loadEntries(): array;

	public function getEntries(): array
	{
		if ($this->entries === null)
		{
			$this->entries = $this->loadEntries();
		}

		return $this->entries;
	}

	public function resolve(string $input): ?string
	{
		[$matchedValue, $valuesMatchingLabel] = $this->findMatches($input);

		if ($matchedValue !== null)
		{
			// The input is a known value (id), but if a label points to a different
			// entry it is also a name → genuinely ambiguous, refuse to guess.
			return $this->hasConflictingLabel($matchedValue, $valuesMatchingLabel) ? null : $matchedValue;
		}

		return count($valuesMatchingLabel) === 1 ? $valuesMatchingLabel[0] : null;
	}

	public function isAmbiguousLabel(string $input): bool
	{
		[$matchedValue, $valuesMatchingLabel] = $this->findMatches($input);

		return $matchedValue === null && count($valuesMatchingLabel) > 1;
	}

	public function isAmbiguousValue(string $input): bool
	{
		[$matchedValue, $valuesMatchingLabel] = $this->findMatches($input);

		return $matchedValue !== null && $this->hasConflictingLabel($matchedValue, $valuesMatchingLabel);
	}

	public function labelFor(string $value): ?string
	{
		$this->buildIndexes();

		return $this->labelByValue[$this->normalize($value)] ?? null;
	}

	/**
	 * Finds what an input matches in both indexes.
	 *
	 * @return array{0: ?string, 1: array<int, string>}  [canonical value if the input is a known value,
	 *                                                     values whose label equals the input]
	 */
	private function findMatches(string $input): array
	{
		$this->buildIndexes();

		$key = $this->normalize($input);
		if ($key === '')
		{
			return [null, []];
		}

		return [$this->valueByKey[$key] ?? null, $this->valuesByLabel[$key] ?? []];
	}

	/**
	 * True when one of the values found by label is a different entry than the
	 * value directly matched — i.e. the input is both a value and another's label.
	 *
	 * @param array<int, string> $valuesMatchingLabel
	 */
	private function hasConflictingLabel(string $matchedValue, array $valuesMatchingLabel): bool
	{
		foreach ($valuesMatchingLabel as $value)
		{
			if ($value !== $matchedValue)
			{
				return true;
			}
		}

		return false;
	}

	private function buildIndexes(): void
	{
		if ($this->valueByKey !== null)
		{
			return;
		}

		$this->valueByKey    = [];
		$this->valuesByLabel = [];
		$this->labelByValue  = [];

		foreach ($this->getEntries() as $entry)
		{
			$normalizedValue = $this->normalize($entry['value']);

			$this->valueByKey[$normalizedValue]   = $entry['value'];
			$this->labelByValue[$normalizedValue] = $entry['label'];

			$normalizedLabel = $this->normalize($entry['label']);
			if ($normalizedLabel !== '')
			{
				$this->valuesByLabel[$normalizedLabel][] = $entry['value'];
			}
		}
	}

	private function normalize(string $value): string
	{
		return mb_strtolower(trim($value));
	}
}