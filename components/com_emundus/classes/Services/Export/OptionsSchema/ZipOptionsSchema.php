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

class ZipOptionsSchema extends AbstractOptionsSchema
{
	public const DISPLAY_HEADER                 = 'display_header';
	public const DISPLAY_PAGE_NUMBERS           = 'display_page_numbers';
	public const CONCAT_ATTACHMENTS_WITH_FORM   = 'concat_attachments_with_form';
	public const CONVERT_DOCX_TO_PDF            = 'convert_docx_to_pdf';
	public const FILENAME                       = 'filename';

	protected function getFormatFields(): array
	{
		$group = ExportTabEnum::OPTIONS->toFieldGroup();

		return [
			new BooleanField(
				name: self::DISPLAY_HEADER,
				label: 'COM_EMUNDUS_EXPORTS_OPTION_DISPLAY_HEADER',
				required: false,
				group: $group,
			),
			new BooleanField(
				name: self::DISPLAY_PAGE_NUMBERS,
				label: 'COM_EMUNDUS_EXPORTS_OPTION_DISPLAY_PAGE_NUMBERS',
				required: false,
				group: $group,
			),
			new BooleanField(
				name: self::CONCAT_ATTACHMENTS_WITH_FORM,
				label: 'COM_EMUNDUS_EXPORTS_CONCAT_ATTACHMENTS_WITH_FORMS',
				required: false,
				group: $group,
			),
			new BooleanField(
				name: self::CONVERT_DOCX_TO_PDF,
				label: 'COM_EMUNDUS_EXPORTS_CONVERT_DOCX_TO_PDF',
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
		$applicationFormName = (string) $emConfig->get('application_form_name', 'application_form_pdf');

		return [
			self::DISPLAY_HEADER               => true,
			self::DISPLAY_PAGE_NUMBERS         => true,
			self::CONCAT_ATTACHMENTS_WITH_FORM => false,
			self::CONVERT_DOCX_TO_PDF          => false,
			self::FILENAME                     => $applicationFormName,
		];
	}
}
