<?php

use PHPUnit\Framework\TestCase;
use Tchooz\Services\UploadService;

/**
 * @covers \Tchooz\Services\UploadService
 */
class UploadServiceTest extends TestCase
{
	private string $uploadDir;

	private array $tempFiles = [];

	private array $createdDirs = [];

	protected function setUp(): void
	{
		parent::setUp();

		// Create a temporary upload directory within the expected path structure
		$this->uploadDir = 'images/emundus/test_upload_' . uniqid('', true) . '/';

		$absoluteDir = JPATH_SITE . DIRECTORY_SEPARATOR . $this->uploadDir;
		if (!is_dir($absoluteDir)) {
			mkdir($absoluteDir, 0755, true);
			$this->createdDirs[] = $absoluteDir;
		}
	}

	protected function tearDown(): void
	{
		foreach ($this->tempFiles as $file) {
			if (file_exists($file)) {
				unlink($file);
			}
		}

		// Clean up created directories (reverse order)
		foreach (array_reverse($this->createdDirs) as $dir) {
			if (is_dir($dir)) {
				// Remove all files in the directory first
				$files = glob($dir . '*');
				foreach ($files as $file) {
					if (is_file($file)) {
						unlink($file);
					}
				}
				rmdir($dir);
			}
		}

		parent::tearDown();
	}

	/**
	 * Get the absolute path to a test asset file.
	 */
	private function getAssetPath(string $filename): string
	{
		return dirname(__DIR__, 5) . '/assets/' . $filename;
	}

	/**
	 * Simulate an uploaded file array from a real file on disk.
	 * We copy the file to a temp location to simulate $_FILES['tmp_name'].
	 */
	private function simulateUploadedFile(string $sourcePath, string $originalName, string $mimeType): array
	{
		$tmpName = sys_get_temp_dir() . '/upload_test_' . uniqid('', true) . '_' . basename($sourcePath);
		copy($sourcePath, $tmpName);
		$this->tempFiles[] = $tmpName;

		return [
			'name'     => $originalName,
			'type'     => $mimeType,
			'tmp_name' => $tmpName,
			'error'    => UPLOAD_ERR_OK,
			'size'     => filesize($tmpName),
		];
	}

	// =========================================================================
	// Security: PDF with XSS / JavaScript
	// =========================================================================

	/**
	 * Test that uploading a PDF containing JavaScript triggers a rejection.
	 * Uses the xssPDF.pdf test asset which contains /OpenAction and /JavaScript patterns.
	 * @covers \Tchooz\Services\UploadService::upload
	 */
	public function testUploadRejectsPdfWithJavaScript(): void
	{
		$assetPath = $this->getAssetPath('xssPDF.pdf');
		$this->assertFileExists($assetPath, 'Test asset xssPDF.pdf must exist');

		$uploadService = new UploadService($this->uploadDir, 10, ['application/pdf']);

		$file = $this->simulateUploadedFile($assetPath, 'xssPDF.pdf', 'application/pdf');

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('File contains potentially dangerous active content');

		$uploadService->upload($file);
	}

	/**
	 * Test that uploading a safe PDF is not rejected by the security check.
	 * @covers \Tchooz\Services\UploadService::upload
	 */
	public function testUploadAcceptsSafePdf(): void
	{
		$assetPath = $this->getAssetPath('pdf_test_file.pdf');
		$this->assertFileExists($assetPath, 'Test asset pdf_test_file.pdf must exist');

		$uploadService = new UploadService($this->uploadDir, 10, ['application/pdf']);

		$file = $this->simulateUploadedFile($assetPath, 'pdf_test_file.pdf', 'application/pdf');

		// The upload() method uses move_uploaded_file() which only works for real HTTP uploads.
		// In a unit test context this will throw "Failed to move uploaded file." which is
		// AFTER the security check. If we reach that error, it means the security check passed.
		try {
			$uploadService->upload($file);
			// If by some chance it succeeds, that's fine too
			$this->assertTrue(true);
		} catch (\RuntimeException $e) {
			// "Failed to move uploaded file." is expected — it means the security scan passed
			$this->assertStringNotContainsString(
				'dangerous active content',
				$e->getMessage(),
				'A safe PDF should not be flagged as dangerous'
			);
		}
	}

	// =========================================================================
	// Security: Crafted dangerous PDFs
	// =========================================================================

	/**
	 * Test that a PDF with /JS action is rejected.
	 * @covers \Tchooz\Services\UploadService::upload
	 */
	public function testUploadRejectsPdfWithJSAction(): void
	{
		$content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /JS (app.alert(1)) >>\nendobj\n%%EOF";
		$tmpFile = sys_get_temp_dir() . '/upload_test_js_' . uniqid('', true) . '.pdf';
		file_put_contents($tmpFile, $content);
		$this->tempFiles[] = $tmpFile;

		$uploadService = new UploadService($this->uploadDir, 10, ['application/pdf']);

		$file = [
			'name'     => 'malicious.pdf',
			'type'     => 'application/pdf',
			'tmp_name' => $tmpFile,
			'error'    => UPLOAD_ERR_OK,
			'size'     => filesize($tmpFile),
		];

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('File contains potentially dangerous active content');

		$uploadService->upload($file);
	}
}

