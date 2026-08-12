<?php
/**
 * @package     Tchooz\Services\Export\OptionsSchema
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\OptionsSchema;

use InvalidArgumentException;
use Tchooz\Enums\Export\ExportFormatEnum;

class OptionsSchemaFactory
{
	/**
	 * Resolve the schema for a given export format. Accepts the raw POST value
	 * (string) or an already-typed enum, so controllers do not have to coerce
	 * before calling.
	 */
	public static function for(ExportFormatEnum|string $format): AbstractOptionsSchema
	{
		$enum = $format instanceof ExportFormatEnum
			? $format
			: ExportFormatEnum::tryFrom((string) $format);

		if ($enum === null)
		{
			throw new InvalidArgumentException(sprintf('Unknown export format "%s"', (string) $format));
		}

		return match ($enum)
		{
			ExportFormatEnum::XLSX => new ExcelOptionsSchema(),
			ExportFormatEnum::PDF  => new PdfOptionsSchema(),
			ExportFormatEnum::ZIP  => new ZipOptionsSchema(),
		};
	}
}
