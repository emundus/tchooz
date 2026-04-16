<?php

use PHPUnit\Framework\TestCase;
use Tchooz\Services\FileSecurityService;

/**
 * @covers \Tchooz\Services\FileSecurityService
 */
class FileSecurityServiceTest extends TestCase
{
	private FileSecurityService $service;

	private array $tempFiles = [];

	protected function setUp(): void
	{
		parent::setUp();
		$this->service = new FileSecurityService();
	}

	protected function tearDown(): void
	{
		foreach ($this->tempFiles as $file) {
			if (file_exists($file)) {
				unlink($file);
			}
		}

		parent::tearDown();
	}

	/**
	 * Create a temporary file with the given content.
	 */
	private function createTempFile(string $content, string $extension = 'tmp'): string
	{
		$path = sys_get_temp_dir() . '/filesec_test_' . uniqid('', true) . '.' . $extension;
		file_put_contents($path, $content);
		$this->tempFiles[] = $path;

		return $path;
	}

	/**
	 * Create a temporary ZIP file with the given entries.
	 *
	 * @param   array   $entries  Associative array of [entryName => content].
	 * @param   string  $ext      File extension for the temp file.
	 *
	 * @return  string  Path to the created ZIP file.
	 */
	private function createTempZip(array $entries, string $ext = 'zip'): string
	{
		$path = sys_get_temp_dir() . '/filesec_test_' . uniqid('', true) . '.' . $ext;
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::CREATE);

		foreach ($entries as $name => $content) {
			$zip->addFromString($name, $content);
		}

		$zip->close();
		$this->tempFiles[] = $path;

		return $path;
	}

	// =========================================================================
	// Unsupported extensions
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testUnsupportedExtensionReturnsFalse(): void
	{
		$path = $this->createTempFile('some random content', 'txt');
		$this->assertFalse($this->service->containsDangerousContent($path, 'txt'));
	}

	// =========================================================================
	// getSupportedExtensions / isExtensionSupported
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\FileSecurityService::getSupportedExtensions
	 */
	public function testGetSupportedExtensions(): void
	{
		$extensions = $this->service->getSupportedExtensions();
		$this->assertContains('PDF', $extensions);
		$this->assertContains('ODT', $extensions);
		$this->assertContains('DOCX', $extensions);
		$this->assertContains('DOC', $extensions);
		$this->assertContains('SVG', $extensions);
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::isExtensionSupported
	 */
	public function testIsExtensionSupported(): void
	{
		$this->assertTrue($this->service->isExtensionSupported('pdf'));
		$this->assertTrue($this->service->isExtensionSupported('ODT'));
		$this->assertTrue($this->service->isExtensionSupported('.docx'));
		$this->assertFalse($this->service->isExtensionSupported('txt'));
		$this->assertFalse($this->service->isExtensionSupported('mp4'));
	}

	// =========================================================================
	// PDF
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testSafePdfPassesCheck(): void
	{
		$content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF";
		$path = $this->createTempFile($content, 'pdf');
		$this->assertFalse($this->service->containsDangerousContent($path, 'pdf'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 * @dataProvider dangerousPdfPatternsProvider
	 */
	public function testPdfWithDangerousPatterns(string $pattern): void
	{
		$content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog " . $pattern . " >>\nendobj\n%%EOF";
		$path = $this->createTempFile($content, 'pdf');
		$this->assertTrue(
			$this->service->containsDangerousContent($path, 'pdf'),
			"PDF pattern '$pattern' should be detected as dangerous"
		);
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 * @dataProvider beninPdfPatternsProvider
	 */
	public function testPdfWithBeninOpenActionPattern(string $pattern): void
	{
		$content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog " . $pattern . " >>\nendobj\n%%EOF";
		$path = $this->createTempFile($content, 'pdf');
		$this->assertFalse(
			$this->service->containsDangerousContent($path, 'pdf'),
			"PDF pattern '$pattern' should not be detected as dangerous"
		);
	}

	public static function dangerousPdfPatternsProvider(): array
	{
		return [
			'JS action'         => ['/JS '],
			'JavaScript action' => ['/JavaScript '],
			'OpenAction'        => ['/OpenAction << /S /JavaScript '],
			'AA'                => ['/AA '],
			'Launch'            => ['/Launch '],
			'SubmitForm'        => ['/SubmitForm '],
			'ImportData'        => ['/ImportData '],
			'RichMedia'         => ['/RichMedia '],
			'XFA'               => ['/XFA '],
		];
	}

	public static function beninPdfPatternsProvider(): array
	{
		return [
			'OpenAction'        => ['/OpenAction [7 0 R /FitH null]'],
			'MediaBox'          => ['/MediaBox [0 0 1000 1000] '],
			'MetaData'          => ['/Metadata << /Producer (Benin OpenAction) >> '],
			'MediaOverlay'      => ['/MediaOverlay << /Size 0 >> '],
			'EmbeddedFile'      => ['/EmbeddedFile << /Size 0 >> '],
			'PageMode'          => ['/PageMode UseOutlines '],
			'PageLayout'        => ['/PageLayout OneColumn '],
			'Directhon'         => ['/D '],
		];
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testUnreadablePdfReturnsDangerous(): void
	{
		$this->assertTrue(
			$this->service->containsDangerousContent('/nonexistent/path/fake.pdf', 'pdf')
		);
	}

	// =========================================================================
	// OpenDocument (ODT, ODS, ODP)
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testSafeOdtPassesCheck(): void
	{
		$path = $this->createTempZip([
			'content.xml'          => '<office:document-content></office:document-content>',
			'styles.xml'           => '<office:document-styles></office:document-styles>',
			'META-INF/manifest.xml' => '<manifest:manifest></manifest:manifest>',
		], 'odt');

		$this->assertFalse($this->service->containsDangerousContent($path, 'odt'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testOdtWithBasicMacrosDetected(): void
	{
		$path = $this->createTempZip([
			'content.xml'          => '<office:document-content></office:document-content>',
			'Basic/Standard/Module1.xml' => '<script:module>MsgBox "Hello"</script:module>',
			'META-INF/manifest.xml' => '<manifest:manifest></manifest:manifest>',
		], 'odt');

		$this->assertTrue($this->service->containsDangerousContent($path, 'odt'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testOdtWithScriptsDirectoryDetected(): void
	{
		$path = $this->createTempZip([
			'content.xml'          => '<office:document-content></office:document-content>',
			'Scripts/python/myscript.py' => 'print("hello")',
			'META-INF/manifest.xml' => '<manifest:manifest></manifest:manifest>',
		], 'odt');

		$this->assertTrue($this->service->containsDangerousContent($path, 'odt'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testOdtWithManifestMacroRefDetected(): void
	{
		$path = $this->createTempZip([
			'content.xml'          => '<office:document-content></office:document-content>',
			'META-INF/manifest.xml' => '<manifest:manifest><manifest:file-entry manifest:full-path="Basic/macro" /></manifest:manifest>',
		], 'odt');

		$this->assertTrue($this->service->containsDangerousContent($path, 'odt'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testOdtWithScriptEventListenerDetected(): void
	{
		$path = $this->createTempZip([
			'content.xml'          => '<office:document-content><script:event-listener script:language="ooo:script" /></office:document-content>',
			'META-INF/manifest.xml' => '<manifest:manifest></manifest:manifest>',
		], 'odt');

		$this->assertTrue($this->service->containsDangerousContent($path, 'odt'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testOdtWithOfficeScriptsDetected(): void
	{
		$path = $this->createTempZip([
			'content.xml'          => '<office:document-content><office:scripts><script/></office:scripts></office:document-content>',
			'META-INF/manifest.xml' => '<manifest:manifest></manifest:manifest>',
		], 'odt');

		$this->assertTrue($this->service->containsDangerousContent($path, 'odt'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testOdsDetectedSameAsOdt(): void
	{
		$path = $this->createTempZip([
			'content.xml'          => '<office:document-content></office:document-content>',
			'Basic/Standard/Module1.xml' => '<script:module>MsgBox</script:module>',
			'META-INF/manifest.xml' => '<manifest:manifest></manifest:manifest>',
		], 'ods');

		$this->assertTrue($this->service->containsDangerousContent($path, 'ods'));
	}

	// =========================================================================
	// OOXML (DOCX, XLSX, PPTX)
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testSafeDocxPassesCheck(): void
	{
		$path = $this->createTempZip([
			'[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>',
			'word/document.xml'   => '<w:document></w:document>',
		], 'docx');

		$this->assertFalse($this->service->containsDangerousContent($path, 'docx'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testDocxWithVbaProjectDetected(): void
	{
		$path = $this->createTempZip([
			'[Content_Types].xml' => '<?xml version="1.0"?><Types></Types>',
			'word/document.xml'   => '<w:document></w:document>',
			'word/vbaProject.bin' => 'binary vba data',
		], 'docx');

		$this->assertTrue($this->service->containsDangerousContent($path, 'docx'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testDocxWithActiveXDetected(): void
	{
		$path = $this->createTempZip([
			'[Content_Types].xml'     => '<?xml version="1.0"?><Types></Types>',
			'word/document.xml'       => '<w:document></w:document>',
			'word/activeX/activeX1.xml' => '<ax:ocx></ax:ocx>',
		], 'docx');

		$this->assertTrue($this->service->containsDangerousContent($path, 'docx'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testDocxWithOleObjectDetected(): void
	{
		$path = $this->createTempZip([
			'[Content_Types].xml'     => '<?xml version="1.0"?><Types></Types>',
			'word/document.xml'       => '<w:document></w:document>',
			'word/embeddings/oleObject1.bin' => 'ole binary data',
		], 'docx');

		$this->assertTrue($this->service->containsDangerousContent($path, 'docx'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testDocxWithMacroContentTypeDetected(): void
	{
		$path = $this->createTempZip([
			'[Content_Types].xml' => '<?xml version="1.0"?><Types><Override ContentType="application/vnd.ms-office.vbaProject" /></Types>',
			'word/document.xml'   => '<w:document></w:document>',
		], 'docx');

		$this->assertTrue($this->service->containsDangerousContent($path, 'docx'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testXlsxWithVbaDetected(): void
	{
		$path = $this->createTempZip([
			'[Content_Types].xml' => '<?xml version="1.0"?><Types></Types>',
			'xl/workbook.xml'     => '<workbook></workbook>',
			'xl/vbaProject.bin'   => 'binary vba data',
		], 'xlsx');

		$this->assertTrue($this->service->containsDangerousContent($path, 'xlsx'));
	}

	// =========================================================================
	// Legacy Office (DOC, XLS, PPT)
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testSafeDocPassesCheck(): void
	{
		$content = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\x00", 100);
		$path = $this->createTempFile($content, 'doc');
		$this->assertFalse($this->service->containsDangerousContent($path, 'doc'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 * @dataProvider legacyOfficeSignaturesProvider
	 */
	public function testLegacyDocWithMacroSignatures(string $signature): void
	{
		$content = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\x00", 50) . $signature . str_repeat("\x00", 50);
		$path = $this->createTempFile($content, 'doc');
		$this->assertTrue(
			$this->service->containsDangerousContent($path, 'doc'),
			"Legacy Office signature '$signature' should be detected as dangerous"
		);
	}

	public static function legacyOfficeSignaturesProvider(): array
	{
		return [
			'VBA'            => ['_VBA_PROJECT'],
			'Macros'         => ['Macros'],
			'ThisDocument'   => ['ThisDocument'],
			'ThisWorkbook'   => ['ThisWorkbook'],
			'AutoOpen'       => ['AutoOpen'],
			'Auto_Open'      => ['Auto_Open'],
			'AutoExec'       => ['AutoExec'],
			'Document_Open'  => ['Document_Open'],
			'Workbook_Open'  => ['Workbook_Open'],
		];
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testXlsWithMacroDetected(): void
	{
		$content = "\xD0\xCF\x11\xE0" . str_repeat("\x00", 50) . 'ThisWorkbook' . str_repeat("\x00", 50);
		$path = $this->createTempFile($content, 'xls');
		$this->assertTrue($this->service->containsDangerousContent($path, 'xls'));
	}

	// =========================================================================
	// SVG
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testSafeSvgPassesCheck(): void
	{
		$content = '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="red"/></svg>';
		$path = $this->createTempFile($content, 'svg');
		$this->assertFalse($this->service->containsDangerousContent($path, 'svg'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 * @dataProvider dangerousSvgPatternsProvider
	 */
	public function testSvgWithDangerousPatterns(string $svgContent): void
	{
		$path = $this->createTempFile($svgContent, 'svg');
		$this->assertTrue(
			$this->service->containsDangerousContent($path, 'svg'),
			"SVG content should be detected as dangerous"
		);
	}

	public static function dangerousSvgPatternsProvider(): array
	{
		return [
			'script tag' => [
				'<svg xmlns="http://www.w3.org/2000/svg"><script>alert("xss")</script></svg>',
			],
			'onload event' => [
				'<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><circle/></svg>',
			],
			'onclick event' => [
				'<svg xmlns="http://www.w3.org/2000/svg"><circle onclick="alert(1)"/></svg>',
			],
			'javascript href' => [
				'<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><text>click</text></a></svg>',
			],
			'javascript xlink:href' => [
				'<svg xmlns="http://www.w3.org/2000/svg"><a xlink:href="javascript:alert(1)"><text>click</text></a></svg>',
			],
			'foreignObject' => [
				'<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><body xmlns="http://www.w3.org/1999/xhtml"><iframe/></body></foreignObject></svg>',
			],
			'iframe' => [
				'<svg xmlns="http://www.w3.org/2000/svg"><iframe src="http://evil.com"/></svg>',
			],
			'embed' => [
				'<svg xmlns="http://www.w3.org/2000/svg"><embed src="data:text/html,<script>alert(1)</script>"/></svg>',
			],
			'data text/html' => [
				'<svg xmlns="http://www.w3.org/2000/svg"><image href="data: text/html,<script>alert(1)</script>"/></svg>',
			],
		];
	}

	// =========================================================================
	// Extension case insensitivity
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testExtensionIsCaseInsensitive(): void
	{
		$content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /JS (alert) >>\nendobj\n%%EOF";
		$path = $this->createTempFile($content, 'pdf');

		$this->assertTrue($this->service->containsDangerousContent($path, 'pdf'));
		$this->assertTrue($this->service->containsDangerousContent($path, 'PDF'));
		$this->assertTrue($this->service->containsDangerousContent($path, 'Pdf'));
	}

	/**
	 * @covers \Tchooz\Services\FileSecurityService::containsDangerousContent
	 */
	public function testExtensionWithDotPrefix(): void
	{
		$content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /JS (alert) >>\nendobj\n%%EOF";
		$path = $this->createTempFile($content, 'pdf');

		$this->assertTrue($this->service->containsDangerousContent($path, '.pdf'));
	}
}

