<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export;

use Tchooz\Enums\Export\ExportSettingEnum;

class ExportOptions
{
	private string $format;

	private int $campaign;

	private string $exportVersion;

	private array $elements;

	private string $lang;

	/**
	 * Runtime toggles whose keys are declared in {@see ExportSettingEnum}.
	 * Stored as [enum value => casted value]. Use getSetting() rather than
	 * touching the array directly — it falls back to the enum default.
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

	public function getSetting(ExportSettingEnum $key): mixed
	{
		return $this->settings[$key->value] ?? $key->getDefault();
	}

	public function setSetting(ExportSettingEnum $key, mixed $value): void
	{
		$this->settings[$key->value] = $key->cast($value);
	}

	/**
	 * Replace the whole bag from raw input (array or stdClass). Unknown keys are
	 * ignored; known keys are cast through their enum definition.
	 */
	public function setSettings(array|object|null $raw): void
	{
		$this->settings = [];

		if (empty($raw))
		{
			return;
		}

		$raw = is_object($raw) ? (array) $raw : $raw;

		foreach (ExportSettingEnum::cases() as $case)
		{
			if (array_key_exists($case->value, $raw))
			{
				$this->settings[$case->value] = $case->cast($raw[$case->value]);
			}
		}
	}

	/**
	 * @return array<string, mixed> Effective settings (stored values + enum defaults for missing keys).
	 */
	public function getSettings(): array
	{
		$out = [];
		foreach (ExportSettingEnum::cases() as $case)
		{
			$out[$case->value] = $this->settings[$case->value] ?? $case->getDefault();
		}

		return $out;
	}
}