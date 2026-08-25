<?php

use PHPUnit\Framework\TestCase;
use Tchooz\Services\Import\Source\CsvSource;
use Tchooz\Services\Import\Source\ImportSourceFactory;
use Tchooz\Services\Import\Source\JsonSource;

/**
 * @covers \Tchooz\Services\Import\Source\ImportSourceFactory
 */
class ImportSourceFactoryTest extends TestCase
{
	private array $tempFiles = [];

	protected function tearDown(): void
	{
		foreach ($this->tempFiles as $file)
		{
			if (file_exists($file))
			{
				unlink($file);
			}
		}

		parent::tearDown();
	}

	private function tempFile(string $content, string $ext): string
	{
		$path = sys_get_temp_dir() . '/import_src_' . uniqid('', true) . '.' . $ext;
		file_put_contents($path, $content);
		$this->tempFiles[] = $path;

		return $path;
	}

	public function testSupportsKnownFormatsCaseInsensitively(): void
	{
		foreach (['csv', 'xlsx', 'xls', 'json', 'CSV', ' Json '] as $format)
		{
			$this->assertTrue(ImportSourceFactory::supports($format), $format);
		}
	}

	public function testDoesNotSupportUnknownFormats(): void
	{
		foreach (['', 'txt', 'ods', 'pdf', 'array'] as $format)
		{
			$this->assertFalse(ImportSourceFactory::supports($format), $format);
		}
	}

	public function testFromFileBuildsCsvSource(): void
	{
		$path = $this->tempFile("email,Nom\na@test.com,Doe\n", 'csv');

		$this->assertInstanceOf(CsvSource::class, ImportSourceFactory::fromFile($path, 'csv', 'contacts.csv'));
	}

	public function testFromFileBuildsJsonSource(): void
	{
		$path = $this->tempFile('[{"email":"a@test.com","Nom":"Doe"}]', 'json');

		$this->assertInstanceOf(JsonSource::class, ImportSourceFactory::fromFile($path, 'json', 'contacts.json'));
	}

	public function testFromFileThrowsOnUnsupportedFormat(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		ImportSourceFactory::fromFile('/does/not/matter.ods', 'ods');
	}
}
