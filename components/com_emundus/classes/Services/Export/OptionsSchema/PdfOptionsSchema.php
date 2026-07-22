<?php
/**
 * @package     Tchooz\Services\Export\OptionsSchema
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\OptionsSchema;

use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Enums\Export\ExportTabEnum;

class PdfOptionsSchema extends AbstractOptionsSchema
{
	public const DISPLAY_HEADER          = 'display_header';
	public const DISPLAY_PAGE_NUMBERS    = 'display_page_numbers';
	public const FILENAME                = 'filename';
	public const DISPLAY_EVALUATOR_NAME  = 'display_evaluator_name';

	protected function getFormatFields(): array
	{
		$group = ExportTabEnum::OPTIONS->toFieldGroup();

		return [
			new BooleanField(
				name: self::DISPLAY_PAGE_NUMBERS,
				label: 'COM_EMUNDUS_EXPORTS_OPTION_DISPLAY_PAGE_NUMBERS',
				required: false,
				group: $group,
			),
			new BooleanField(
				name: self::DISPLAY_EVALUATOR_NAME,
				label: 'COM_EMUNDUS_EXPORTS_DISPLAY_EVALUATOR_NAME',
				required: false,
				group: $group,
			),
			new StringField(
				name: self::FILENAME,
				label: 'COM_EMUNDUS_EXPORTS_OPTION_FILENAME',
				required: false,
				group: $group,
				maxLength: 255,
			),
		];
	}

	protected function getFormatDefaults(): array
	{
		$emConfig            = ComponentHelper::getParams('com_emundus');
		$applicationFormName = (string) $emConfig->get('application_form_name', '');

		if ($applicationFormName === 'application_form_pdf')
		{
			$applicationFormName .= '_[FNUM]';
		}

		return [
			self::DISPLAY_HEADER         => true,
			self::DISPLAY_PAGE_NUMBERS   => true,
			self::DISPLAY_EVALUATOR_NAME => true,
			self::FILENAME               => $applicationFormName,
		];
	}
}
