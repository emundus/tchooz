<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Excel;

use Tchooz\Services\Export\ExportOptions;
use Tchooz\Services\Export\HeadersEnum;

class ExcelOptions extends ExportOptions
{
	private array $synthesis = [];

	const DEFAULT_SYNTHESIS = [
		HeadersEnum::FNUM,
		HeadersEnum::STATUS,
		HeadersEnum::LASTNAME,
		HeadersEnum::FIRSTNAME,
		HeadersEnum::EMAIL,
	];

	public function __construct(array $synthesis = [])
	{
		parent::__construct(
			'xlsx',
			0,
			'default',
			[],
			'en-GB'
		);
		$this->synthesis = $synthesis;
	}

	public static function fromObject(object $options): ExcelOptions
	{
		$campaign           = $options->campaign ?? 0;
		$exportVersion      = $options->export_version ?? 'default';
		$elements           = $options->elements ? explode(',', $options->elements) : [];
		$lang               = $options->lang ?? 'en-GB';

		if(!empty($options->synthesis) && is_string($options->synthesis)) {
			$options->synthesis = explode(',', $options->synthesis);
		}
		$synthesis            = $options->synthesis ? $options->synthesis : [];

		$excelOptions = new ExcelOptions($synthesis);

		$excelOptions->setCampaign($campaign);
		$excelOptions->setExportVersion($exportVersion);
		$excelOptions->setElements($elements);
		$excelOptions->setLang($lang);

		return $excelOptions;
	}

	public function getSynthesis(): array
	{
		return $this->synthesis;
	}

	public function setSynthesis(array $synthesis): void
	{
		$this->synthesis = $synthesis;
	}
}