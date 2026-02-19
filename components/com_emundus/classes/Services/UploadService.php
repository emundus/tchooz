<?php
/**
 * @package     Tchooz\Services
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services;

use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use enshrined\svgSanitize\Sanitizer;
use RuntimeException;
use InvalidArgumentException;
use Tchooz\Enums\Upload\UploadFormatEnum;

class UploadService
{
	protected int $maxFilesizeMB;

	protected array $validMimeTypes;

	protected string $uploadDir;

	public function __construct(
		string $uploadDir,
		int $maxFilesizeMB = 10,
		array $validMimeTypes = null
	) {
		// Upload directory must be start by images/emundus/ or tmp/
		if (!preg_match('#^(images/emundus/|tmp/)#', str_replace(DIRECTORY_SEPARATOR, '/', $uploadDir))) {
			throw new InvalidArgumentException('Upload directory must be within images/emundus/ or tmp/');
		}

		$this->uploadDir = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$this->uploadDir = JPATH_SITE . DIRECTORY_SEPARATOR . $this->uploadDir;
		$this->maxFilesizeMB = $maxFilesizeMB;
		$this->validMimeTypes = $validMimeTypes ?? [
			'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/jpg'
		];
	}

	public function createTemporaryFile(string $prefix, UploadFormatEnum $format): string
	{
		if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0755, true) && !is_dir($this->uploadDir))
		{
			throw new RuntimeException('Failed to create upload directory.');
		}

		$safePrefix    = $this->sanitizeFilename($prefix);
		$unique        = uniqid($safePrefix . '_', true);
		$ext           = strtolower($format->value);
		$finalFilename = $unique . '.' . $ext;
		$destination   = $this->uploadDir . $finalFilename;

		$handle = fopen($destination, 'w');
		if ($handle === false)
		{
			throw new RuntimeException('Failed to create temporary file.');
		}
		fclose($handle);

		return $this->toRelativePath($destination);
	}

	public function upload(array $file, ?string $nameForFilename = null, ?string $prefix = null): string
	{
		if (!isset($file['error']) || is_array($file['error'])) {
			throw new RuntimeException('Invalid file upload parameters.');
		}

		if ($file['error'] !== UPLOAD_ERR_OK) {
			throw new RuntimeException($this->codeToMessage($file['error']));
		}

		if (!in_array($file['type'], $this->validMimeTypes, true)) {
			throw new RuntimeException('Invalid file type.');
		}
		
		$bytes = $this->maxFilesizeMB * 1024 * 1024;
		if ($file['size'] > $bytes) {
			throw new RuntimeException(sprintf('File size exceeds %dMB', $this->maxFilesizeMB));
		}

		$basename = $this->sanitizeFilename($file['name']);

		$ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION) ?: '');
		$mimetype = $this->detectMimeType($ext, $file['tmp_name']);

		if ($file['type'] !== $mimetype) {
			throw new RuntimeException('File type does not match file content/extension.');
		}

		if ($mimetype === 'image/svg+xml') {
			$this->sanitizeSvgFile($file['tmp_name']);
		}

		if ($mimetype === 'image/jpeg') {
			$this->stripJpegExif($file['tmp_name']);
		}

		if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0755, true) && !is_dir($this->uploadDir)) {
			throw new RuntimeException('Failed to create upload directory.');
		}

		$safeNamePart = $nameForFilename ? $this->sanitizeFilename($nameForFilename) : '';
		$prefix = $prefix ? $this->sanitizeFilename($prefix) : '';
		if(!empty($prefix) && !empty($safeNamePart))
		{
			$name = $prefix . '_' . strtolower($safeNamePart) . '_' . time() . '_';
			$unique = uniqid($name, true);
		}
		else {
			// Generate a unique filename to avoid collisions
			$unique = uniqid($prefix ?: '');
		}
		$finalFilename = $unique . ($ext ? '.' . $ext : '');

		$destination = $this->uploadDir . $finalFilename;

		if (!move_uploaded_file($file['tmp_name'], $destination)) {
			throw new RuntimeException('Failed to move uploaded file.');
		}

		return $this->toRelativePath($destination);
	}

	public function deleteFile(string $relativePath): bool
	{
		$absolutePath = $this->uploadDir . basename($relativePath);
		if (!file_exists($absolutePath)) {
			throw new InvalidArgumentException('File does not exist.');
		}

		return unlink($absolutePath);
	}

	private function sanitizeFilename(string $name): string
	{
		if (class_exists('HtmlSanitizerSingleton')) {
			$sanitizer = HtmlSanitizerSingleton::getInstance();
			return $sanitizer->sanitize($name);
		}

		// fallback: basic sanitization
		$name = preg_replace('/[^\w\-. ]+/', '', $name);
		$name = preg_replace('/\s+/', '-', $name);
		$name = trim($name);
		return $name === '' ? 'file' : $name;
	}

	private function detectMimeType(string $ext, string $tmpName): string
	{
		$map = [
			'png'  => 'image/png',
			'jpe'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'gif'  => 'image/gif',
			'bmp'  => 'image/bmp',
			'ico'  => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif'  => 'image/tiff',
			'svg'  => 'image/svg+xml',
			'svgz' => 'image/svg+xml',
			'webp' => 'image/webp',
		];

		if ($ext !== '' && array_key_exists($ext, $map)) {
			return $map[$ext];
		}

		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimetype = finfo_file($finfo, $tmpName);
			finfo_close($finfo);
			return $mimetype;
		}

		throw new RuntimeException('Unable to determine file MIME type.');
	}

	private function sanitizeSvgFile(string $tmpName): void
	{
		$sanitizer = new Sanitizer();
		$svg = file_get_contents($tmpName);
		if ($svg === false) {
			throw new RuntimeException('Failed to read uploaded SVG.');
		}

		$clean = $sanitizer->sanitize($svg);
		if ($clean === null) {
			throw new RuntimeException('SVG sanitization failed.');
		}

		file_put_contents($tmpName, $clean);
	}

	protected function stripJpegExif(string $tmpName): void
	{
		$img = @imagecreatefromjpeg($tmpName);
		if ($img === false) {
			return;
		}
		imagejpeg($img, $tmpName, 100);
		imagedestroy($img);
	}

	protected function toRelativePath(string $absolute): string
	{
		if (defined('JPATH_SITE')) {
			$root = rtrim(JPATH_SITE, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			if (strpos($absolute, $root) === 0) {
				return str_replace(DIRECTORY_SEPARATOR, '/', substr($absolute, strlen($root)));
			}
		}

		if (!empty($_SERVER['DOCUMENT_ROOT'])) {
			$root = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			if (strpos($absolute, $root) === 0) {
				return str_replace(DIRECTORY_SEPARATOR, '/', substr($absolute, strlen($root)));
			}
		}

		return str_replace(DIRECTORY_SEPARATOR, '/', $absolute);
	}

	protected function codeToMessage(int $code): string
	{
		return match ($code)
		{
			UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded file exceeds allowed size.',
			UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
			UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
			UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
			UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
			UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
			default => 'Unknown upload error.',
		};
	}
}