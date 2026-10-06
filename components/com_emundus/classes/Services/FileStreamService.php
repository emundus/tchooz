<?php
/**
 * @package     Tchooz\Services
 *
 * @copyright   (C) eMundus
 * @license     GNU/GPL
 */

namespace Tchooz\Services;

/**
 * Sends a file to the client with its size and single byte-range support (206 / 416), so a large
 * download cut halfway can be resumed by the browser or a download manager instead of restarting.
 */
class FileStreamService
{
	private const CHUNK_SIZE = 1024 * 1024;

	/**
	 * Upper bound for one download, in seconds: a slow client may not hold a PHP worker forever.
	 */
	private const MAX_STREAM_DURATION = 3600;

	/**
	 * Ends the request once the file is sent.
	 *
	 * @throws \RuntimeException  When the file cannot be opened, before any header is sent.
	 */
	public function stream(string $path, string $downloadName, string $mimeType, bool $asAttachment = false): never
	{
		// Opened first: once headers are out, a failure could only produce a truncated download
		$handle = @fopen($path, 'rb');
		if ($handle === false)
		{
			throw new \RuntimeException('Cannot open file for streaming: ' . basename($path));
		}

		$stat         = fstat($handle);
		$size         = (int) $stat['size'];
		$lastModified = gmdate('D, d M Y H:i:s', (int) $stat['mtime']) . ' GMT';
		$range        = $this->resolveRange($_SERVER['HTTP_RANGE'] ?? null, $_SERVER['HTTP_IF_RANGE'] ?? null, $lastModified, $size);

		while (ob_get_level() > 0)
		{
			ob_end_clean();
		}

		header('Content-Type: ' . $mimeType);
		header('Content-Disposition: ' . ($asAttachment ? 'attachment' : 'inline') . '; filename="' . str_replace(['"', "\r", "\n"], '', basename($downloadName)) . '"');
		header('Last-Modified: ' . $lastModified);
		header('Accept-Ranges: bytes');
		// Stops the browser from sniffing an uploaded file into HTML or script and running it on the platform domain
		header('X-Content-Type-Options: nosniff');
		header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header('Expires: 0');

		if ($range === false)
		{
			fclose($handle);
			http_response_code(416);
			header('Content-Range: bytes */' . $size);
			exit;
		}

		[$start, $end] = $range ?? [0, $size - 1];
		if ($range !== null)
		{
			http_response_code(206);
			header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
		}
		header('Content-Length: ' . max(0, $end - $start + 1));

		set_time_limit(self::MAX_STREAM_DURATION);

		$remaining = $end - $start + 1;
		fseek($handle, $start);

		while ($remaining > 0 && !feof($handle) && !connection_aborted())
		{
			$chunk = fread($handle, min(self::CHUNK_SIZE, $remaining));
			if ($chunk === false || $chunk === '')
			{
				break;
			}

			echo $chunk;
			flush();
			$remaining -= strlen($chunk);
		}

		fclose($handle);
		exit;
	}

	/**
	 * Unsupported or malformed ranges are ignored, as HTTP allows: the whole file is sent instead.
	 * A range asked with an If-Range that no longer matches the file is ignored too, so a resumed
	 * download never glues together pieces of two different versions.
	 *
	 * @return array{0: int, 1: int}|null|false  inclusive byte range to send, null for the whole file,
	 *                                           false when the range lies outside the file (416).
	 */
	public function resolveRange(?string $rangeHeader, ?string $ifRange, string $lastModified, int $size): array|null|false
	{
		if (empty($rangeHeader) || $size <= 0)
		{
			return null;
		}

		if (!empty($ifRange) && trim($ifRange) !== $lastModified)
		{
			return null;
		}

		// Only a single range is served; multipart/byteranges responses are not worth their complexity here.
		if (!preg_match('/^bytes=(\d*)-(\d*)$/', trim($rangeHeader), $matches) || ($matches[1] === '' && $matches[2] === ''))
		{
			return null;
		}

		if ($matches[1] === '')
		{
			$suffix = (int) $matches[2];
			if ($suffix === 0)
			{
				return false;
			}

			return [max(0, $size - $suffix), $size - 1];
		}

		$start = (int) $matches[1];
		$end   = $matches[2] === '' ? $size - 1 : min((int) $matches[2], $size - 1);

		if ($start >= $size)
		{
			return false;
		}

		if ($start > $end)
		{
			return null;
		}

		return [$start, $end];
	}
}
