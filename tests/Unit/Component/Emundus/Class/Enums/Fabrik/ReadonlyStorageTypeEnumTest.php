<?php

namespace Unit\Component\Emundus\Class\Enums\Fabrik;

use PHPUnit\Framework\TestCase;
use Tchooz\Enums\Fabrik\ReadonlyStorageTypeEnum;

/**
 * @package     Unit\Component\Emundus\Class\Enums\Fabrik
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Enums\Fabrik\ReadonlyStorageTypeEnum
 */
class ReadonlyStorageTypeEnumTest extends TestCase
{
	public static function columnTypeProvider(): array
	{
		return [
			'datetime'           => ['datetime', ReadonlyStorageTypeEnum::DATETIME],
			'datetime uppercase' => ['DATETIME', ReadonlyStorageTypeEnum::DATETIME],
			'date'               => ['date', ReadonlyStorageTypeEnum::DATE],
			'varchar'            => ['varchar', ReadonlyStorageTypeEnum::TEXT],
			'text'               => ['text', ReadonlyStorageTypeEnum::TEXT],
			'int'                => ['int', ReadonlyStorageTypeEnum::TEXT],
			'timestamp'          => ['timestamp', ReadonlyStorageTypeEnum::TEXT],
			'missing column'     => ['', ReadonlyStorageTypeEnum::TEXT],
		];
	}

	/**
	 * @dataProvider columnTypeProvider
	 */
	public function testFromColumnType_KeepsDatesAndFallsBackToText(string $columnType, ReadonlyStorageTypeEnum $expected): void
	{
		$this->assertSame($expected, ReadonlyStorageTypeEnum::fromColumnType($columnType));
	}

	public function testIsDate_WhenDateType_ReturnsTrue(): void
	{
		$this->assertTrue(ReadonlyStorageTypeEnum::DATETIME->isDate());
		$this->assertTrue(ReadonlyStorageTypeEnum::DATE->isDate());
	}

	public function testIsDate_WhenText_ReturnsFalse(): void
	{
		$this->assertFalse(ReadonlyStorageTypeEnum::TEXT->isDate());
	}
}
