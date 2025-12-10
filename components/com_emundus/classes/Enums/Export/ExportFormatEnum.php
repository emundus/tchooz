<?php
/**
 * @package     Tchooz\Enums\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Export;

enum ExportFormatEnum: string
{
	case XLSX = 'xlsx';
	case PDF = 'pdf';

	public function getLabel(): string
	{
		return match ($this)
		{
			ExportFormatEnum::XLSX => 'COM_EMUNDUS_EXPORT_FORMAT_XLSX',
			ExportFormatEnum::PDF => 'COM_EMUNDUS_EXPORT_FORMAT_PDF',
		};
	}

	public function getImage(): string
	{
		return match ($this)
		{
			ExportFormatEnum::XLSX => 'media/com_emundus/images/icones/filetype/excel.png',
			ExportFormatEnum::PDF => 'media/com_emundus/images/icones/filetype/pdf.png',
		};
	}

	public function getType(): string
	{
		return match ($this)
		{
			ExportFormatEnum::XLSX => 'excel',
			ExportFormatEnum::PDF => 'pdf',
		};
	}

	public function getAccessName(): string
	{
		return match ($this)
		{
			ExportFormatEnum::XLSX => 'export_excel',
			ExportFormatEnum::PDF => 'export_pdf',
		};
	}
}
