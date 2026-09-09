<?php

namespace Unit\Component\Emundus\Class\Transformers;

use Joomla\Tests\Unit\UnitTestCase;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use Tchooz\Transformers\PHPWord\HtmlXmlTransformer;

class HtmlXmlTransformerTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Transformers\PHPWord\HtmlXmlTransformer::transform
	 * @return void
	 */
	public function testVoidTagsAreClosed(): void
	{
		$result = HtmlXmlTransformer::transform('<p>Ligne<br>Suite</p><table><colgroup><col style="width: 20%;"></colgroup><tbody><tr><td>Cellule</td></tr></tbody></table>');

		$this->assertStringContainsString('<br/>', $result);
		$this->assertStringContainsString('<col style="width: 20%;"/>', $result);
		$this->assertStringContainsString('Cellule', $result);
	}

	/**
	 * @covers \Tchooz\Transformers\PHPWord\HtmlXmlTransformer::transform
	 * @return void
	 */
	public function testEmptyContentIsUnchanged(): void
	{
		$this->assertEquals('', HtmlXmlTransformer::transform(''));
	}

	/**
	 * The point of the transformer: what a rich-text editor emits must survive PhpWord's xml parser.
	 *
	 * @covers \Tchooz\Transformers\PHPWord\HtmlXmlTransformer::transform
	 * @return void
	 */
	public function testEditorContentIsReadableByPhpWord(): void
	{
		$html = '<table border="1"><colgroup><col style="width: 50%;"><col style="width: 50%;"></colgroup>'
			. '<tbody><tr><td><strong>Test</strong></td><td>d&rsquo;un tableau</td></tr></tbody></table>'
			. '<p>Un paragraphe<br>sur deux lignes</p>';

		// The raw fragment is not fed to PhpWord here: its xml parser raises warnings on the void
		// tags before returning nothing, which is exactly what the transformer exists to avoid.
		$phpWord = new PhpWord();
		$section = $phpWord->addSection();
		Html::addHtml($section, HtmlXmlTransformer::transform($html));
		$elements = $section->getElements();

		$this->assertCount(2, $elements, 'The table and the paragraph should both be read');
		$this->assertInstanceOf('PhpOffice\PhpWord\Element\Table', $elements[0]);
		$this->assertInstanceOf('PhpOffice\PhpWord\Element\TextRun', $elements[1]);
	}
}
