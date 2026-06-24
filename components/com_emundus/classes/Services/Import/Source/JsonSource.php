<?php
/**
 * @package     Tchooz\Services\Import\Source
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Source;

/**
 * Reads a JSON file or string structured as an array of objects.
 *
 * Each object is treated as one row, keyed by its property names. Headers
 * are inferred from the union of keys across all objects, so a sparse JSON
 * (where some rows omit fields) still produces a stable header list.
 */
final class JsonSource implements ImportSourceInterface
{
	private ArraySource $inner;

	private function __construct(ArraySource $inner)
	{
		$this->inner = $inner;
	}

	public static function fromFile(string $filePath, ?string $name = null): self
	{
		if (!is_readable($filePath))
		{
			throw new \RuntimeException(sprintf('JSON file "%s" is not readable.', $filePath));
		}

		$content = file_get_contents($filePath);

		if ($content === false)
		{
			throw new \RuntimeException(sprintf('Failed to read JSON file "%s".', $filePath));
		}

		return self::fromString($content, $name ?? basename($filePath));
	}

	public static function fromString(string $json, string $name = 'json'): self
	{
		try
		{
			$decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		}
		catch (\JsonException $e)
		{
			throw new \RuntimeException('Invalid JSON: ' . $e->getMessage(), 0, $e);
		}

		if (!is_array($decoded))
		{
			throw new \RuntimeException('JSON root must be an array of objects.');
		}

		$rows    = [];
		$headers = [];

		foreach ($decoded as $entry)
		{
			if (!is_array($entry))
			{
				throw new \RuntimeException('JSON rows must be objects.');
			}

			foreach (array_keys($entry) as $key)
			{
				if (is_string($key) && !in_array($key, $headers, true))
				{
					$headers[] = $key;
				}
			}

			$rows[] = $entry;
		}

		// Ensure every row carries every header (null for missing keys), so
		// downstream RowMapper sees a consistent shape.
		foreach ($rows as &$row)
		{
			foreach ($headers as $header)
			{
				if (!array_key_exists($header, $row))
				{
					$row[$header] = null;
				}
			}
		}
		unset($row);

		return new self(new ArraySource($rows, $headers, $name));
	}

	public function getName(): string
	{
		return $this->inner->getName();
	}

	public function getRawHeaders(): array
	{
		return $this->inner->getRawHeaders();
	}

	public function getIterator(): \Iterator
	{
		return $this->inner->getIterator();
	}
}
