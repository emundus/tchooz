<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export;

class ExportOptions
{
	private string $format;

	private int $campaign;

	private string $exportVersion;

	private array $elements;

	private string $lang;
	//

	public function __construct(
		string $format,
		int    $campaign,
		string $exportVersion,
		array  $elements,
		string $lang)
	{
		$this->format             = $format;
		$this->campaign           = $campaign;
		$this->exportVersion      = $exportVersion;
		$this->elements           = $elements;
		$this->lang               = $lang;
	}

	public static function fromObject(object $options): ExportOptions
	{
		$format             = $options->format ?? 'pdf';
		$campaign           = $options->campaign ?? 0;
		$exportVersion      = $options->exportVersion ?? 'default';
		$elements           = $options->elements ?? [];
		$lang               = $options->lang ?? 'en-GB';

		return new ExportOptions(
			$format,
			$campaign,
			$exportVersion,
			$elements,
			$lang
		);
	}

	public function getFormat(): string
	{
		return $this->format;
	}

	public function setFormat(string $format): void
	{
		$this->format = $format;
	}

	public function getCampaign(): int
	{
		return $this->campaign;
	}

	public function setCampaign(int $campaign): void
	{
		$this->campaign = $campaign;
	}

	public function getExportVersion(): string
	{
		return $this->exportVersion;
	}

	public function setExportVersion(string $exportVersion): void
	{
		$this->exportVersion = $exportVersion;
	}

	public function getElements(): array
	{
		return $this->elements;
	}

	public function setElements(array $elements): void
	{
		$this->elements = $elements;
	}

	public function getLang(): string
	{
		return $this->lang;
	}

	public function setLang(string $lang): void
	{
		$this->lang = $lang;
	}
}