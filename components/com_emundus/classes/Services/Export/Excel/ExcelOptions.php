<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Excel;

use Tchooz\Enums\Export\ExportModeEnum;

class ExcelOptions
{
	private ExportModeEnum $mode;

	public function __construct(ExportModeEnum $mode)
	{
		$this->mode = $mode;
	}

	public static function fromObject(object $options): ExcelOptions
	{
		$mode = ExportModeEnum::from($options->mode ?? ExportModeEnum::GROUP_CONCAT->value);

		return new ExcelOptions($mode);
	}

	public function getMode(): ExportModeEnum
	{
		return $this->mode;
	}

	public function setMode(ExportModeEnum $mode): void
	{
		$this->mode = $mode;
	}
}