<?php
/**
 * @package     Tchooz\Enums\Upload
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Upload;

enum UploadFormatEnum: string
{
	case CSV = 'csv';
	case XLSX = 'xlsx';
	case PDF = 'pdf';

	public function getLabel(): string
	{
		return match ($this)
		{
			UploadFormatEnum::CSV => 'COM_EMUNDUS_UPLOAD_FORMAT_CSV',
			UploadFormatEnum::XLSX => 'COM_EMUNDUS_UPLOAD_FORMAT_XLSX',
			UploadFormatEnum::PDF => 'COM_EMUNDUS_UPLOAD_FORMAT_PDF',
		};
	}

	public function getMimeTypes(): array
	{
		return match ($this)
		{
			UploadFormatEnum::CSV => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
			UploadFormatEnum::XLSX => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
			UploadFormatEnum::PDF => ['application/pdf'],
		};
	}
}
