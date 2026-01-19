<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Pdf;

use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Services\Export\ExportOptions;
use Tchooz\Services\Export\HeadersEnum;

class PdfOptions extends ExportOptions
{
	private bool $displayHeader;

	/**
	 * @var array<HeadersEnum>
	 */
	private array $headers;

	private array $pageHeaders;

	private array $attachments = [];

	private bool $displayPageNumbers;

	private string $filename;

	const DEFAULT_HEADERS = [
		HeadersEnum::ID,
		HeadersEnum::FNUM,
		HeadersEnum::EMAIL,
		HeadersEnum::SUBMITTED_DATE,
		HeadersEnum::PRINTED_DATE,
		HeadersEnum::STATUS,
		HeadersEnum::STICKERS,
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
	}

	public static function fromObject(object $options): PdfOptions
	{
		$campaign           = $options->campaign ?? 0;
		$exportVersion      = $options->exportVersion ?? 'default';
		$elements           = $options->elements ? explode(',', $options->elements) : [];
		$lang               = $options->lang ?? 'en-GB';

		$displayHeader      = $options->displayHeader ?? true;
		if(!empty($options->headers) && is_string($options->headers)) {
			$options->headers = explode(',', $options->headers);
		}
		$headers            = $options->headers ?? [];
		if(!is_array($headers)) {
			$headers = [];
		}

		if(!empty($options->synthesis) && is_string($options->synthesis)) {
			$options->synthesis = explode(',', $options->synthesis);
		}
		$synthesis            = $options->synthesis ?: [];

		if(!empty($options->attachments) && is_string($options->attachments)) {
			$options->attachments = explode(',', $options->attachments);
		}
		$attachments          = $options->attachments ?: [];
		
		$displayPageNumbers = $options->displayPageNumbers ?? true;

		$pdfOptions = new PdfOptions($displayHeader, $synthesis, $headers, $displayPageNumbers, $attachments);

		$pdfOptions->setCampaign($campaign);
		$pdfOptions->setExportVersion($exportVersion);
		$pdfOptions->setElements($elements);
		$pdfOptions->setLang($lang);

		$eMConfig              = ComponentHelper::getParams('com_emundus');
		$application_form_name = $eMConfig->get('application_form_name', '');
		$pdfOptions->setFilename($application_form_name);

		return $pdfOptions;
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
}