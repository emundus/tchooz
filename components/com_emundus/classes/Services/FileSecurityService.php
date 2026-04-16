<?php
/**
 * @package     Tchooz\Services
 * @subpackage  Security
 *
 * @copyright   Copyright (C) 2015 emundus.fr. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Tchooz\Services;

use Joomla\CMS\Log\Log;

/**
 * Service to detect dangerous active content (macros, scripts, JavaScript)
 * in uploaded files based on their extension.
 */
class FileSecurityService
{
	/**
	 * Map of file extensions to their corresponding check method.
	 */
	private const EXTENSION_HANDLERS = [
		'PDF'  => 'scanPdf',
		'ODT'  => 'scanOpenDocument',
		'ODS'  => 'scanOpenDocument',
		'ODP'  => 'scanOpenDocument',
		'ODG'  => 'scanOpenDocument',
		'ODF'  => 'scanOpenDocument',
		'DOCX' => 'scanOoxml',
		'XLSX' => 'scanOoxml',
		'PPTX' => 'scanOoxml',
		'DOC'  => 'scanLegacyOffice',
		'XLS'  => 'scanLegacyOffice',
		'PPT'  => 'scanLegacyOffice',
		'SVG'  => 'scanSvg',
	];

	/**
	 * Dangerous patterns for PDF files.
	 * These target JavaScript actions, auto-open actions, external launches, etc.
	 *
	 * Note on /OpenAction: the pattern only matches when followed by an inline action
	 * dictionary (<<). Navigation destinations of the form [pageRef /FitH null],
	 * [pageRef /XYZ ...], etc. are benign and are NOT matched.
	 * JavaScript referenced indirectly via an object reference (/OpenAction N N R) is
	 * already covered by the /JS and /JavaScript patterns on the same content.
	 */
	private const PDF_PATTERNS = [
		'/\/JS\s/',
		'/\/JavaScript\s/',
		'/\/OpenAction\s*<</',
		'/\/AA\s/',
		'/\/Launch\s/',
		'/\/SubmitForm\s/',
		'/\/ImportData\s/',
		'/\/RichMedia\s/',
		'/\/XFA\s/',
	];

	/**
	 * Dangerous entry prefixes inside OpenDocument ZIP archives.
	 */
	private const OPENDOCUMENT_DANGEROUS_ENTRIES = [
		'Basic/',
		'Scripts/',
	];

	/**
	 * Dangerous patterns inside OpenDocument XML files (content.xml, styles.xml).
	 */
	private const OPENDOCUMENT_XML_PATTERNS = [
		'/script:event-listener/i',
		'/office:scripts/i',
		'/xlink:href\s*=\s*["\']vnd\.sun\.star\.script/i',
		'/office:events/i',
	];

	/**
	 * Dangerous entry patterns inside OOXML ZIP archives (DOCX, XLSX, PPTX).
	 */
	private const OOXML_DANGEROUS_ENTRIES = [
		'vbaproject.bin',
		'vbadata.xml',
	];

	/**
	 * Dangerous ActiveX directory prefixes in OOXML archives.
	 */
	private const OOXML_ACTIVEX_PREFIXES = [
		'word/activex/',
		'xl/activex/',
		'ppt/activex/',
	];

	/**
	 * Macro-related content types in OOXML [Content_Types].xml.
	 */
	private const OOXML_MACRO_CONTENT_TYPES = [
		'application/vnd.ms-office.vbaProject',
		'application/vnd.ms-word.document.macroEnabled',
		'application/vnd.ms-excel.sheet.macroEnabled',
		'application/vnd.ms-powerpoint.presentation.macroEnabled',
		'application/vnd.openxmlformats-officedocument.oleObject',
	];

	/**
	 * Signatures found in legacy Office binary files (DOC, XLS, PPT) that indicate macros.
	 */
	private const LEGACY_OFFICE_SIGNATURES = [
		'VBA',
		'_VBA_PROJECT',
		'Macros',
		'MODULE_STREAM',
		'ThisDocument',
		'ThisWorkbook',
		'Auto_Open',
		'AutoOpen',
		'AutoExec',
		'Document_Open',
		'Workbook_Open',
	];

	/**
	 * Dangerous patterns inside SVG files.
	 */
	private const SVG_PATTERNS = [
		'/<script[\s>]/i',
		'/on\w+\s*=/i',
		'/javascript\s*:/i',
		'/data\s*:\s*text\/html/i',
		'/<foreignObject[\s>]/i',
		'/<iframe[\s>]/i',
		'/<embed[\s>]/i',
		'/<object[\s>]/i',
		'/xlink:href\s*=\s*["\']javascript:/i',
		'/href\s*=\s*["\']javascript:/i',
	];

	/**
	 * Check if an uploaded file contains dangerous active content.
	 *
	 * @param   string  $filePath  Absolute path to the temporary uploaded file.
	 * @param   string  $fileExt   File extension (without dot).
	 *
	 * @return  bool  True if dangerous content is detected, false otherwise.
	 */
	public function containsDangerousContent(string $filePath, string $fileExt): bool
	{
		$ext = strtoupper(ltrim($fileExt, '.'));

		if (!isset(self::EXTENSION_HANDLERS[$ext])) {
			return false;
		}

		$method = self::EXTENSION_HANDLERS[$ext];

		return $this->{$method}($filePath);
	}

	/**
	 * Get the list of supported file extensions.
	 *
	 * @return  string[]
	 */
	public function getSupportedExtensions(): array
	{
		return array_keys(self::EXTENSION_HANDLERS);
	}

	/**
	 * Check if the given extension is supported for scanning.
	 *
	 * @param   string  $fileExt  File extension (without dot).
	 *
	 * @return  bool
	 */
	public function isExtensionSupported(string $fileExt): bool
	{
		return isset(self::EXTENSION_HANDLERS[strtoupper(ltrim($fileExt, '.'))]);
	}

	/**
	 * Scan a PDF file for JavaScript and dangerous actions.
	 *
	 * @param   string  $filePath  Path to the PDF file.
	 *
	 * @return  bool  True if dangerous content found.
	 */
	private function scanPdf(string $filePath): bool
	{
		$content = @file_get_contents($filePath);

		if ($content === false) {
			return true;
		}

		return $this->matchesAnyPattern($content, self::PDF_PATTERNS);
	}

	/**
	 * Scan OpenDocument files (ODT, ODS, ODP, ODG, ODF) for macros and scripts.
	 *
	 * @param   string  $filePath  Path to the OpenDocument file.
	 *
	 * @return  bool  True if dangerous content found.
	 */
	private function scanOpenDocument(string $filePath): bool
	{
		try {
			$zip = new \ZipArchive();

			if ($zip->open($filePath) !== true) {
				return true;
			}

			// Check for dangerous directory entries (Basic/, Scripts/)
			for ($i = 0; $i < $zip->numFiles; $i++) {
				$entryName = $zip->getNameIndex($i);

				foreach (self::OPENDOCUMENT_DANGEROUS_ENTRIES as $prefix) {
					if (str_starts_with($entryName, $prefix)) {
						$zip->close();

						return true;
					}
				}
			}

			// Check manifest for macro declarations
			$manifest = $zip->getFromName('META-INF/manifest.xml');

			if ($manifest !== false && preg_match('/macro|script|Basic/i', $manifest)) {
				$zip->close();

				return true;
			}

			// Check XML content files for script event listeners
			$xmlFiles = ['content.xml', 'styles.xml'];

			foreach ($xmlFiles as $xmlFile) {
				$xmlContent = $zip->getFromName($xmlFile);

				if ($xmlContent !== false && $this->matchesAnyPattern($xmlContent, self::OPENDOCUMENT_XML_PATTERNS)) {
					$zip->close();

					return true;
				}
			}

			$zip->close();

			return false;
		} catch (\Exception $e) {
			Log::add('FileSecurityService - OpenDocument parsing error: ' . $e->getMessage(), Log::WARNING, 'com_emundus');

			return true;
		}
	}

	/**
	 * Scan Office Open XML files (DOCX, XLSX, PPTX) for macros, VBA and ActiveX.
	 *
	 * @param   string  $filePath  Path to the OOXML file.
	 *
	 * @return  bool  True if dangerous content found.
	 */
	private function scanOoxml(string $filePath): bool
	{
		try {
			$zip = new \ZipArchive();

			if ($zip->open($filePath) !== true) {
				return true;
			}

			for ($i = 0; $i < $zip->numFiles; $i++) {
				$entryName = strtolower($zip->getNameIndex($i));

				// Check for VBA project files
				foreach (self::OOXML_DANGEROUS_ENTRIES as $dangerous) {
					if (str_contains($entryName, $dangerous)) {
						$zip->close();

						return true;
					}
				}

				// Check for ActiveX controls
				foreach (self::OOXML_ACTIVEX_PREFIXES as $prefix) {
					if (str_starts_with($entryName, $prefix)) {
						$zip->close();

						return true;
					}
				}

				// Check for embedded OLE objects
				if (str_contains($entryName, 'oleobject') || str_contains($entryName, 'embeddings/')) {
					$zip->close();

					return true;
				}
			}

			// Check [Content_Types].xml for macro content types
			$contentTypes = $zip->getFromName('[Content_Types].xml');

			if ($contentTypes !== false) {
				foreach (self::OOXML_MACRO_CONTENT_TYPES as $type) {
					if (str_contains($contentTypes, $type)) {
						$zip->close();

						return true;
					}
				}
			}

			$zip->close();

			return false;
		} catch (\Exception $e) {
			Log::add('FileSecurityService - OOXML parsing error: ' . $e->getMessage(), Log::WARNING, 'com_emundus');

			return true;
		}
	}

	/**
	 * Scan legacy Office files (DOC, XLS, PPT) for macros via OLE signatures.
	 *
	 * @param   string  $filePath  Path to the legacy Office file.
	 *
	 * @return  bool  True if dangerous content found.
	 */
	private function scanLegacyOffice(string $filePath): bool
	{
		$content = @file_get_contents($filePath);

		if ($content === false) {
			return true;
		}

		foreach (self::LEGACY_OFFICE_SIGNATURES as $signature) {
			if (str_contains($content, $signature)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Scan SVG files for embedded scripts and dangerous attributes.
	 *
	 * @param   string  $filePath  Path to the SVG file.
	 *
	 * @return  bool  True if dangerous content found.
	 */
	private function scanSvg(string $filePath): bool
	{
		$content = @file_get_contents($filePath);

		if ($content === false) {
			return true;
		}

		return $this->matchesAnyPattern($content, self::SVG_PATTERNS);
	}

	/**
	 * Check if the content matches any of the given regex patterns.
	 *
	 * @param   string    $content   The content to scan.
	 * @param   string[]  $patterns  Array of regex patterns.
	 *
	 * @return  bool  True if any pattern matches.
	 */
	private function matchesAnyPattern(string $content, array $patterns): bool
	{
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $content)) {
				return true;
			}
		}

		return false;
	}
}

