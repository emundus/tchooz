<?php
/**
 * @package     Tchooz\Services\Import\Referential
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Referential;

/**
 * Generic referential provider configured with a key, a label and a closure
 * that loads the {value, label} pairs.
 */
final class CallableReferentialProvider extends AbstractReferentialProvider
{
	/** @var \Closure(): array<int, array{value: string, label: string}> */
	private \Closure $loader;

	public function __construct(
		private readonly string $key,
		private readonly string $label,
		\Closure $loader
	) {
		$this->loader = $loader;
	}

	public function getKey(): string
	{
		return $this->key;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	protected function loadEntries(): array
	{
		return ($this->loader)();
	}
}