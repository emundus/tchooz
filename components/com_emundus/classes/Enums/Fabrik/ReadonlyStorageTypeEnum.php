<?php
/**
 * @package     Tchooz\Enums\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Fabrik;

/**
 * Column types a read-only element can be stored in. Any source column that is not a date keeps the TEXT storage.
 */
enum ReadonlyStorageTypeEnum: string
{
	case TEXT = 'TEXT';
	case DATETIME = 'DATETIME';
	case DATE = 'DATE';

	public static function fromColumnType(string $columnType): self
	{
		return match (strtolower(trim($columnType)))
		{
			'datetime' => self::DATETIME,
			'date' => self::DATE,
			default => self::TEXT,
		};
	}

	public function isDate(): bool
	{
		return $this !== self::TEXT;
	}
}
