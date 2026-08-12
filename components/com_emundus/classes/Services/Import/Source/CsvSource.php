<?php
/**
 * @package     Tchooz\Services\Import\Source
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Source;

/**
 * Streams a CSV file row by row.
 *
 * Detects delimiter and encoding heuristically when not specified, so the
 * caller can usually just hand over a file path. The first non-empty line
 * is treated as the header row.
 */
final class CsvSource implements ImportSourceInterface
{
	/** @var string[] */
	private array $headers = [];

	private string $name;

	private string $filePath;

	private string $delimiter;

	private string $encoding;

	private bool $headersRead = false;

	public function __construct(
		string  $filePath,
		?string $delimiter = null,
		?string $encoding  = null,
		?string $name      = null
	)
	{
		if (!is_readable($filePath))
		{
			throw new \RuntimeException(sprintf('CSV file "%s" is not readable.', $filePath));
		}

		$this->filePath  = $filePath;
		$this->name      = $name ?? basename($filePath);
		$this->delimiter = $delimiter ?? self::detectDelimiter($filePath);
		$this->encoding  = $encoding ?? self::detectEncoding($filePath);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getRawHeaders(): array
	{
		if (!$this->headersRead)
		{
			$this->readHeaders();
		}

		return $this->headers;
	}

	public function getIterator(): \Iterator
	{
		$handle = fopen($this->filePath, 'r');

		if ($handle === false)
		{
			throw new \RuntimeException(sprintf('Failed to open CSV file "%s".', $this->filePath));
		}

		try
		{
			$headers = fgetcsv($handle, 0, $this->delimiter);
			if ($headers === false)
			{
				return;
			}

			$this->headers     = array_map(fn ($h) => $this->convertEncoding((string) $h), $headers);
			$this->headersRead = true;

			$rowNumber = 1;

			while (($row = fgetcsv($handle, 0, $this->delimiter)) !== false)
			{
				$rowNumber++;

				$assoc = [];
				foreach ($this->headers as $index => $header)
				{
					if ($header === '')
					{
						continue;
					}

					$value = $row[$index] ?? null;

					if (is_string($value))
					{
						$value = $this->convertEncoding($value);
					}

					$assoc[$header] = $value;
				}

				yield $rowNumber => $assoc;
			}
		}
		finally
		{
			fclose($handle);
		}
	}

	private function readHeaders(): void
	{
		$handle = fopen($this->filePath, 'r');

		if ($handle === false)
		{
			throw new \RuntimeException(sprintf('Failed to open CSV file "%s".', $this->filePath));
		}

		try
		{
			$headers = fgetcsv($handle, 0, $this->delimiter);
			if ($headers === false)
			{
				$this->headers = [];
			}
			else
			{
				$this->headers = array_map(fn ($h) => $this->convertEncoding((string) $h), $headers);
			}
		}
		finally
		{
			fclose($handle);
		}

		$this->headersRead = true;
	}

	private function convertEncoding(string $value): string
	{
		if ($this->encoding === 'UTF-8')
		{
			return $value;
		}

		$converted = @mb_convert_encoding($value, 'UTF-8', $this->encoding);

		return $converted === false ? $value : $converted;
	}

	private static function detectDelimiter(string $filePath, int $checkLines = 5): string
	{
		$candidates = [',', ';', "\t", '|'];
		$best       = ',';
		$bestScore  = 0;

		$handle = fopen($filePath, 'r');
		if ($handle === false)
		{
			return $best;
		}

		try
		{
			$lines = [];
			while (count($lines) < $checkLines && ($line = fgets($handle)) !== false)
			{
				$line = rtrim($line, "\r\n");
				if ($line !== '')
				{
					$lines[] = $line;
				}
			}

			foreach ($candidates as $candidate)
			{
				$score = 0;
				foreach ($lines as $line)
				{
					$score += substr_count($line, $candidate);
				}
				if ($score > $bestScore)
				{
					$bestScore = $score;
					$best      = $candidate;
				}
			}
		}
		finally
		{
			fclose($handle);
		}

		return $best;
	}

	private static function detectEncoding(string $filePath): string
	{
		$content = file_get_contents($filePath, false, null, 0, 8192);
		if ($content === false)
		{
			return 'UTF-8';
		}

		$encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);

		return $encoding ?: 'UTF-8';
	}
}
