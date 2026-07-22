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

	/**
	 * Runtime toggles for the "Options" step. Stored as a flat
	 * array<string, mixed>; casting + defaults are owned by the per-format
	 * schema in {@see \Tchooz\Services\Export\OptionsSchema\AbstractOptionsSchema}.
	 *
	 * @var array<string, mixed>
	 */
	private array $settings = [];
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

	public function getSetting(string $key, mixed $default = null): mixed
	{
		return $this->settings[$key] ?? $default;
	}

	public function setSetting(string $key, mixed $value): void
	{
		$this->settings[$key] = $value;
	}

	/**
	 * Replace the whole bag from already-cast input. Callers (controllers,
	 * services) should run the raw POST payload through the matching
	 * {@see \Tchooz\Services\Export\OptionsSchema\AbstractOptionsSchema::cast()}
	 * before handing it over — this method does not re-cast.
	 *
	 * @param   array<string, mixed>|object|null  $raw
	 */
	public function setSettings(array|object|null $raw): void
	{
		if (empty($raw))
		{
			$this->settings = [];
			return;
		}

		$this->settings = is_object($raw) ? (array) $raw : $raw;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getSettings(): array
	{
		return $this->settings;
	}
}