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
	case ZIP = 'zip';

	public function getLabel(): string
	{
		return match ($this)
		{
			ExportFormatEnum::XLSX => 'COM_EMUNDUS_EXPORT_FORMAT_XLSX',
			ExportFormatEnum::PDF => 'COM_EMUNDUS_EXPORT_FORMAT_PDF',
			ExportFormatEnum::ZIP => 'COM_EMUNDUS_EXPORT_FORMAT_ZIP',
		};
	}

	public function getImage(): string
	{
		return match ($this)
		{
			ExportFormatEnum::XLSX => 'xls_file',
			ExportFormatEnum::PDF => 'pdf_file',
			ExportFormatEnum::ZIP => 'zip_file',
		};
	}

	public function getType(): string
	{
		return match ($this)
		{
			ExportFormatEnum::XLSX => 'excel',
			ExportFormatEnum::PDF => 'pdf',
			ExportFormatEnum::ZIP => 'zip',
		};
	}

	public function getAccessName(): string
	{
		return match ($this)
		{
			ExportFormatEnum::XLSX => 'export_excel',
			ExportFormatEnum::PDF => 'export_pdf',
			ExportFormatEnum::ZIP => 'export_zip',
		};
	}

	public function getSynthesisParameterKey(): string
	{
		return match ($this)
		{
			ExportFormatEnum::XLSX => 'default_synthesis_excel',
			ExportFormatEnum::PDF => 'default_synthesis_pdf',
			ExportFormatEnum::ZIP => 'default_synthesis_zip',
		};
	}
}
