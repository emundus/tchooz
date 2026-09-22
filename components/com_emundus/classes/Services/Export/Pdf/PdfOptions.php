<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Pdf;

use Tchooz\Services\Export\ExportOptions;
use Tchooz\Services\Export\HeadersEnum;
use Tchooz\Services\Export\OptionsSchema\PdfOptionsSchema;

class PdfOptions extends ExportOptions
{
	private bool $displayHeader;

	/**
	 * @var string[] HeadersEnum values or Fabrik element ids, as resolved by Export::getData().
	 */
	private array $headers;

	private array $pageHeaders;

	private array $attachments = [];

	private bool $displayPageNumbers;

	private string $filename = '';

	/** Step types to render. Null keeps the service default: every published step type. */
	private ?array $stepTypes = null;

	/** When true every attachment of the file is appended, whatever $attachments holds. */
	private bool $allAttachments = false;

	/**
	 * Set by callers whose authorization already happened upstream, typically an automation authorized
	 * when it was configured. Deliberately absent from fromObject(): no HTTP payload can raise it.
	 */
	private bool $skipAccessCheck = false;

	const DEFAULT_HEADERS = [
		HeadersEnum::ID->value,
		HeadersEnum::FNUM->value,
		HeadersEnum::EMAIL->value,
		HeadersEnum::SUBMITTED_DATE->value,
		HeadersEnum::PRINTED_DATE->value,
		HeadersEnum::STATUS->value,
		HeadersEnum::STICKERS->value,
	];

	public function __construct(
		bool  $displayHeader = true,
		array $headers = self::DEFAULT_HEADERS,
		array $pageHeaders = [],
		bool  $displayPageNumbers = true,
		array $attachments = []
	)
	{
		parent::__construct(
			'pdf',
			0,
			'default',
			[],
			'en-GB'
		);
		$this->displayHeader      = $displayHeader;
		$this->headers            = $headers;
		$this->pageHeaders        = $pageHeaders;
		$this->displayPageNumbers = $displayPageNumbers;
		$this->attachments        = $attachments;
		$this->filename           = PdfOptionsSchema::defaultFilename();
	}

	public static function fromObject(object $options): PdfOptions
	{
		$campaign           = $options->campaign ?? 0;
		$exportVersion      = $options->exportVersion ?? 'default';
		$lang               = $options->lang ?? 'en-GB';

		$elements    = self::toIdList($options->elements ?? null);
		$headers     = self::toIdList($options->headers ?? null);
		$synthesis   = self::toIdList($options->synthesis ?? null);
		$attachments = self::toIdList($options->attachments ?? null);

		$rawSettings = $options->settings ?? null;
		if (is_string($rawSettings))
		{
			$rawSettings = json_decode($rawSettings, true);
		}
		if (is_object($rawSettings))
		{
			$rawSettings = (array) $rawSettings;
		}
		$schema   = new PdfOptionsSchema();
		$settings = $schema->cast(is_array($rawSettings) ? $rawSettings : []);

		// Typed properties stay the canonical source for service callers; settings
		// payload supersedes the legacy raw $options->displayHeader/... fallbacks.
		$displayHeader      = $settings[PdfOptionsSchema::DISPLAY_HEADER]       ?? ($options->displayHeader ?? true);
		$displayPageNumbers = $settings[PdfOptionsSchema::DISPLAY_PAGE_NUMBERS] ?? ($options->displayPageNumbers ?? true);

		$pdfOptions = new PdfOptions($displayHeader, $synthesis, $headers, $displayPageNumbers, $attachments);

		$pdfOptions->setCampaign($campaign);
		$pdfOptions->setExportVersion($exportVersion);
		$pdfOptions->setElements($elements);
		$pdfOptions->setLang($lang);

		$filename = $settings[PdfOptionsSchema::FILENAME] ?? '';
		$pdfOptions->setFilename($filename !== '' ? $filename : PdfOptionsSchema::defaultFilename());

		$pdfOptions->setSettings($settings);

		return $pdfOptions;
	}

	/**
	 * Normalize an element/header/attachment collection. The HTTP frontier sends comma separated
	 * strings, saved export templates store JSON arrays: both shapes are accepted.
	 */
	private static function toIdList(mixed $value): array
	{
		if (is_array($value))
		{
			return array_values($value);
		}

		if (!is_string($value) || $value === '')
		{
			return [];
		}

		$decoded = json_decode($value, true);

		return is_array($decoded) ? array_values($decoded) : explode(',', $value);
	}

	public function isDisplayHeader(): bool
	{
		return $this->displayHeader;
	}

	public function setDisplayHeader(bool $displayHeader): void
	{
		$this->displayHeader = $displayHeader;
	}

	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function setHeaders(array $headers): void
	{
		$this->headers = $headers;
	}

	public function getPageHeaders(): array
	{
		return $this->pageHeaders;
	}

	public function setPageHeaders(array $pageHeaders): void
	{
		$this->pageHeaders = $pageHeaders;
	}

	public function isDisplayPageNumbers(): bool
	{
		return $this->displayPageNumbers;
	}

	public function setDisplayPageNumbers(bool $displayPageNumbers): void
	{
		$this->displayPageNumbers = $displayPageNumbers;
	}

	public function getFilename(): string
	{
		return $this->filename;
	}

	public function setFilename(string $filename): void
	{
		$this->filename = $filename;
	}

	public function getAttachments(): array
	{
		return $this->attachments;
	}

	public function setAttachments(array $attachments): void
	{
		$this->attachments = $attachments;
	}

	public function getStepTypes(): ?array
	{
		return $this->stepTypes;
	}

	public function setStepTypes(?array $stepTypes): void
	{
		$this->stepTypes = $stepTypes;
	}

	public function isAllAttachments(): bool
	{
		return $this->allAttachments;
	}

	public function setAllAttachments(bool $allAttachments): void
	{
		$this->allAttachments = $allAttachments;
	}

	public function isSkipAccessCheck(): bool
	{
		return $this->skipAccessCheck;
	}

	public function setSkipAccessCheck(bool $skipAccessCheck): void
	{
		$this->skipAccessCheck = $skipAccessCheck;
	}
}