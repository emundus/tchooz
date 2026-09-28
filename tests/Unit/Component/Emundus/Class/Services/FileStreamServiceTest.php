<?php

namespace Unit\Component\Emundus\Class\Services;

use PHPUnit\Framework\TestCase;
use Tchooz\Services\FileStreamService;

/**
 * @covers \Tchooz\Services\FileStreamService
 */
class FileStreamServiceTest extends TestCase
{
	private const LAST_MODIFIED = 'Thu, 24 Sep 2026 10:00:00 GMT';

	/**
	 * @covers \Tchooz\Services\FileStreamService::resolveRange
	 * @dataProvider rangesProvider
	 */
	public function testResolveRange(?string $range, ?string $ifRange, array|null|false $expected): void
	{
		$this->assertSame($expected, (new FileStreamService())->resolveRange($range, $ifRange, self::LAST_MODIFIED, 1000));
	}

	public static function rangesProvider(): array
	{
		return [
			'no range'                    => [null, null, null],
			'bounded range'               => ['bytes=0-499', null, [0, 499]],
			'open-ended range (resume)'   => ['bytes=600-', null, [600, 999]],
			'suffix range'                => ['bytes=-200', null, [800, 999]],
			'suffix larger than file'     => ['bytes=-5000', null, [0, 999]],
			'end clamped to file size'    => ['bytes=900-5000', null, [900, 999]],
			'start beyond the file'       => ['bytes=1000-', null, false],
			'empty suffix'                => ['bytes=-0', null, false],
			'start after end'             => ['bytes=500-100', null, null],
			'multiple ranges ignored'     => ['bytes=0-10,20-30', null, null],
			'other unit ignored'          => ['items=0-10', null, null],
			'if-range matching the file'  => ['bytes=600-', self::LAST_MODIFIED, [600, 999]],
			'if-range on a changed file'  => ['bytes=600-', 'Wed, 23 Sep 2026 10:00:00 GMT', null],
		];
	}

	/**
	 * @covers \Tchooz\Services\FileStreamService::resolveRange
	 */
	public function testEmptyFileIsSentWhole(): void
	{
		$this->assertNull((new FileStreamService())->resolveRange('bytes=0-', null, self::LAST_MODIFIED, 0));
	}

	/**
	 * @covers \Tchooz\Services\FileStreamService::stream
	 */
	public function testStreamFailsBeforeSendingAnythingWhenTheFileCannotBeOpened(): void
	{
		$this->expectException(\RuntimeException::class);

		(new FileStreamService())->stream(sys_get_temp_dir() . '/' . uniqid('missing_') . '.zip', 'missing.zip', 'application/zip', true);
	}
}
