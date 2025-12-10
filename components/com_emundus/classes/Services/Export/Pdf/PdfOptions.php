<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Pdf;

class PdfOptions
{
	private bool $displayHeader;

	/**
	 * @var array<PdfHeadersEnum>
	 */
	private array $headers;

	private bool $displayPageNumbers;

	const DEFAULT_HEADERS = [
		PdfHeadersEnum::ID,
		PdfHeadersEnum::FNUM,
		PdfHeadersEnum::EMAIL,
		PdfHeadersEnum::SUBMITTED_DATE,
		PdfHeadersEnum::PRINTED_DATE,
		PdfHeadersEnum::STATUS,
	];

	public function __construct(bool $displayHeader = true, array $headers = self::DEFAULT_HEADERS, bool $displayPageNumbers = true)
	{
		$this->displayHeader      = $displayHeader;
		$this->headers            = $headers;
		$this->displayPageNumbers = $displayPageNumbers;
	}

	public static function fromObject(object $options): PdfOptions
	{
		$displayHeader      = $options->displayHeader ?? true;
		$headers            = $options->headers ?? self::DEFAULT_HEADERS;
		$displayPageNumbers = $options->displayPageNumbers ?? true;

		return new PdfOptions($displayHeader, $headers, $displayPageNumbers);
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

	public function isDisplayPageNumbers(): bool
	{
		return $this->displayPageNumbers;
	}

	public function setDisplayPageNumbers(bool $displayPageNumbers): void
	{
		$this->displayPageNumbers = $displayPageNumbers;
	}
}