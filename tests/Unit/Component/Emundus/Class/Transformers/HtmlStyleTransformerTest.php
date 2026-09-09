<?php

namespace Unit\Component\Emundus\Class\Transformers;

use Joomla\Tests\Unit\UnitTestCase;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use Tchooz\Transformers\PHPWord\HtmlStyleTransformer;
use Tchooz\Transformers\PHPWord\HtmlXmlTransformer;

class HtmlStyleTransformerTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Transformers\PHPWord\HtmlStyleTransformer::transform
	 * @return void
	 */
	public function testDecimalSizesAreRounded(): void
	{
		$result = HtmlStyleTransformer::transform('<table><tbody><tr><td style="width: 105.6pt; height: 24.0039px;">Montant</td></tr></tbody></table>');

		$this->assertStringContainsString('width: 106pt', $result);
		$this->assertStringNotContainsString('105.6', $result);
		$this->assertStringNotContainsString('height', $result, 'A height reaches Word as a fixed row height it refuses');
	}

	/**
	 * @covers \Tchooz\Transformers\PHPWord\HtmlStyleTransformer::transform
	 * @return void
	 */
	public function testTableWidthIsDropped(): void
	{
		$result = HtmlStyleTransformer::transform('<table style="border-collapse: collapse; width: 38.9858%;"><tbody><tr><td>Montant</td></tr></tbody></table>');

		$this->assertStringNotContainsString('width', $result, 'The width of a table is handed down to every cell that has none');
		$this->assertStringContainsString('border-collapse: collapse', $result);
	}

	/**
	 * @covers \Tchooz\Transformers\PHPWord\HtmlStyleTransformer::transform
	 * @return void
	 */
	public function testWordNoiseIsRemoved(): void
	{
		$result = HtmlStyleTransformer::transform('<p class="MsoNormal" style="mso-pagination: widow-orphan; tab-stops: 361.5pt; text-autospace: ideograph-numeric; margin: .55pt 0cm .0001pt 7.1pt; padding: 0cm 3.5pt;"><span style="font-size: 12.5pt; mso-bidi-font-size: 10.0pt;">Montant</span></p>');

		$this->assertStringNotContainsString('mso-', $result);
		$this->assertStringNotContainsString('tab-stops', $result);
		$this->assertStringNotContainsString('text-autospace', $result);
		$this->assertStringNotContainsString('margin', $result);
		$this->assertStringNotContainsString('padding', $result);
		$this->assertStringContainsString('font-size: 12.5pt', $result, 'A font size is a real intent and Word understands it');
	}

	/**
	 * @covers \Tchooz\Transformers\PHPWord\HtmlStyleTransformer::transform
	 * @return void
	 */
	public function testFontFamilyKeepsOneUnquotedName(): void
	{
		$result = HtmlStyleTransformer::transform('<span style="font-family: \'Calibri\',sans-serif;">Montant</span>');

		$this->assertStringContainsString('font-family: Calibri', $result);
		$this->assertStringNotContainsString("'", $result);
	}

	/**
	 * @covers \Tchooz\Transformers\PHPWord\HtmlStyleTransformer::transform
	 * @return void
	 */
	public function testOnlyHexadecimalColorsAreKept(): void
	{
		$result = HtmlStyleTransformer::transform('<span style="color: black; border-color: windowtext;">Montant</span><span style="color: #abc;">Autre</span><span style="color: #C0FFEE;">Encore</span>');

		$this->assertStringNotContainsString('black', $result);
		$this->assertStringNotContainsString('windowtext', $result);
		$this->assertStringContainsString('color: #aabbcc', $result, 'Word does not accept the shortened form');
		$this->assertStringContainsString('color: #C0FFEE', $result);
	}

	/**
	 * What the whole cleaning is for: PhpWord must not be handed a measurement Word refuses.
	 *
	 * @covers \Tchooz\Transformers\PHPWord\HtmlStyleTransformer::transform
	 * @return void
	 */
	public function testCleanedTableProducesValidWordValues(): void
	{
		$html = '<table style="border-collapse: collapse; width: 38.9858%; height: 276.504px;" border="1">'
			. '<tbody><tr style="height: 24.0039px;">'
			. '<td style="width: 105.6pt; border-color: windowtext; height: 24.0039px;" width="141"><p class="MsoNormal" style="tab-stops: 361.5pt;"><span style="font-family: \'Calibri\',sans-serif; color: black;">10/12/2026 : 7 600€</span></p></td>'
			. '</tr></tbody></table>';

		$cleaned = HtmlXmlTransformer::transform(HtmlStyleTransformer::transform($html));

		$phpWord = new PhpWord();
		$section = $phpWord->addSection();
		Html::addHtml($section, $cleaned);

		$writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
		$file   = tempnam(sys_get_temp_dir(), 'tchooz_word_') . '.docx';
		$writer->save($file);

		$zip = new \ZipArchive();
		$zip->open($file);
		$xml = $zip->getFromName('word/document.xml');
		$zip->close();
		unlink($file);

		$this->assertStringContainsString('10/12/2026 : 7 600€', strip_tags($xml), 'The text of the cell must survive the cleaning');
		$this->assertStringContainsString('<w:tcW w:w="2120" w:type="dxa"/>', $xml, 'The cell keeps its real width, in twips');
		$this->assertStringNotContainsString('<w:trHeight', $xml, 'No fixed row height, it clips the content of the cell');
		$this->assertStringNotContainsString('windowtext', $xml);
		$this->assertStringContainsString('w:ascii="Calibri"', $xml);

		// Only the table is looked at: the page size PhpWord writes for its own section is decimal by
		// nature and never reaches the template of a letter.
		preg_match('/<w:tbl>.*<\/w:tbl>/s', $xml, $table);
		$this->assertSame(0, preg_match_all('/w:(val|w)="[0-9]+\.[0-9]+"/', $table[0] ?? ''), 'Word refuses a measurement that is not a round number');
	}
}
