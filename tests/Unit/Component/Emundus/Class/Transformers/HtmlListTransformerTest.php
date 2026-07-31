<?php

namespace Unit\Component\Emundus\Class\Transformers;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Transformers\PHPWord\HtmlListTransformer;

class HtmlListTransformerTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Transformers\HtmlListTransformer::transform
	 * @return void
	 */
	public function testContentWithoutListIsUnchanged(): void
	{
		$html = '<p>blablabla</p>';
		$this->assertEquals($html, HtmlListTransformer::transform($html));
	}

	/**
	 * @covers \Tchooz\Transformers\HtmlListTransformer::transform
	 * @return void
	 */
	public function testUnorderedListBecomesTextBullets(): void
	{
		$result  = HtmlListTransformer::transform('<ul><li>Pomme</li><li>Fraise</li><li>Poire</li></ul>');
		// saveHTML may emit glyphs as HTML entities; decode before asserting on the character itself.
		$decoded = html_entity_decode($result, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		// No real list tags remain, every item carries a literal bullet.
		$this->assertStringNotContainsString('<ul', $result);
		$this->assertStringNotContainsString('<li', $result);
		$this->assertEquals(3, substr_count($decoded, '•'));
		$this->assertStringContainsString('Pomme', $result);
		$this->assertStringContainsString('Fraise', $result);
		$this->assertStringContainsString('Poire', $result);
	}

	/**
	 * @covers \Tchooz\Transformers\HtmlListTransformer::transform
	 * @return void
	 */
	public function testOrderedListIsNumbered(): void
	{
		$result = HtmlListTransformer::transform('<ol><li>Un</li><li>Deux</li></ol>');

		$this->assertStringNotContainsString('<ol', $result);
		$this->assertStringContainsString('1. ', $result);
		$this->assertStringContainsString('2. ', $result);
	}

	/**
	 * @covers \Tchooz\Transformers\HtmlListTransformer::transform
	 * @return void
	 */
	public function testOrderedListHonoursStartAttribute(): void
	{
		$result = HtmlListTransformer::transform('<ol start="3"><li>Trois</li><li>Quatre</li></ol>');

		$this->assertStringContainsString('3. ', $result);
		$this->assertStringContainsString('4. ', $result);
	}

	/**
	 * @covers \Tchooz\Transformers\HtmlListTransformer::transform
	 * @return void
	 */
	public function testInlineFormattingInsideItemsIsPreserved(): void
	{
		$result = HtmlListTransformer::transform('<ul><li>Texte <strong>gras</strong></li></ul>');

		$this->assertStringContainsString('<strong>gras</strong>', $result);
	}

	/**
	 * @covers \Tchooz\Transformers\HtmlListTransformer::transform
	 * @return void
	 */
	public function testNestedListIsIndentedAndKeepsOrder(): void
	{
		$result  = HtmlListTransformer::transform(
			'<ul><li>Niveau 1<ul><li>Niveau 2</li></ul></li><li>Autre</li></ul>'
		);
		$decoded = html_entity_decode($result, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		$this->assertStringNotContainsString('<ul', $result);
		// The sub-item uses the second-level glyph and is indented with a non-breaking space.
		$this->assertStringContainsString('◦', $decoded);
		$this->assertStringContainsString("\u{00A0}", $decoded);
		// Order is preserved: parent, then its child, then the following sibling.
		$this->assertLessThan(strpos($result, 'Niveau 2'), strpos($result, 'Niveau 1'));
		$this->assertLessThan(strpos($result, 'Autre'), strpos($result, 'Niveau 2'));
	}

	/**
	 * @covers \Tchooz\Transformers\HtmlListTransformer::transform
	 * @return void
	 */
	public function testSurroundingContentIsKept(): void
	{
		$result = HtmlListTransformer::transform('<p>Avant</p><ul><li>Item</li></ul><p>Après</p>');

		$this->assertStringContainsString('Avant', $result);
		$this->assertStringContainsString('Après', $result);
		$this->assertStringContainsString('Item', $result);
	}

	/**
	 * A block child (<p>, <div>, …) inside an <li> must keep the marker on the same line as its text,
	 * never produce an invalid <p> nested inside a <p> (which splits the bullet onto its own line).
	 *
	 * @covers \Tchooz\Transformers\HtmlListTransformer::transform
	 * @return void
	 */
	public function testBlockChildInsideItemKeepsMarkerWithText(): void
	{
		$result  = HtmlListTransformer::transform('<ul><li><p>Premier item</p></li><li>Deuxième</li></ul>');
		$decoded = html_entity_decode($result, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		// No nested <p> inside a <p>.
		$this->assertStringNotContainsString('<p><p', $result);
		$this->assertDoesNotMatchRegularExpression('/<p>[^<]*<p[\s>]/', $result);
		// The marker stays glued to the item text.
		$this->assertStringContainsString('• Premier item', $decoded);
		$this->assertStringContainsString('• Deuxième', $decoded);
	}

	/**
	 * A <li> split into several block children renders one paragraph per block; the marker is on the first.
	 *
	 * @covers \Tchooz\Transformers\HtmlListTransformer::transform
	 * @return void
	 */
	public function testMultiParagraphItemRendersEachParagraph(): void
	{
		$result  = HtmlListTransformer::transform('<ul><li><p>Ligne A</p><p>Ligne B</p></li></ul>');
		$decoded = html_entity_decode($result, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		$this->assertStringNotContainsString('<p><p', $result);
		// Marker only on the first line; the continuation line carries the text without a marker.
		$this->assertStringContainsString('• Ligne A', $decoded);
		$this->assertStringContainsString('Ligne B', $decoded);
		$this->assertEquals(1, substr_count($decoded, '•'));
		$this->assertLessThan(strpos($result, 'Ligne B'), strpos($result, 'Ligne A'));
	}

	/**
	 * A nested ordered list continues its parent's numbering (1…7, 8, 9, 10) instead of restarting at 1,
	 * matching how the rich-text editor renders multi-level ordered lists.
	 *
	 * @covers \Tchooz\Transformers\HtmlListTransformer::transform
	 * @return void
	 */
	public function testNestedOrderedListNumberingIsContinuous(): void
	{
		$result = HtmlListTransformer::transform(
			'<ol><li>a</li><li>b</li><li>c<ol><li>d</li><li>e</li></ol></li><li>f</li></ol>'
		);

		$this->assertStringNotContainsString('<ol', $result);
		// Top level: a=1, b=2, c=3 ; nested continues: d=4, e=5 ; then back to top: f=6.
		foreach (['1. a', '2. b', '3. c', '4. d', '5. e', '6. f'] as $expected)
		{
			$this->assertStringContainsString($expected, $result);
		}
		// The nested items keep one extra indentation level.
		$decoded = html_entity_decode($result, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$this->assertStringContainsString("\u{00A0}4. d", $decoded);
	}

	/**
	 * A list nested directly inside another list (sibling of <li>, invalid HTML some editors emit)
	 * must still have its items rendered, not silently dropped.
	 *
	 * @covers \Tchooz\Transformers\HtmlListTransformer::transform
	 * @return void
	 */
	public function testSiblingNestedListIsNotDropped(): void
	{
		$result  = HtmlListTransformer::transform('<ul><li>Parent</li><ul><li>Enfant</li></ul></ul>');
		$decoded = html_entity_decode($result, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		$this->assertStringNotContainsString('<ul', $result);
		$this->assertStringContainsString('Parent', $result);
		// The orphan sub-list keeps its content and is indented one level deeper.
		$this->assertStringContainsString('Enfant', $result);
		$this->assertStringContainsString('◦', $decoded);
		$this->assertLessThan(strpos($result, 'Enfant'), strpos($result, 'Parent'));
	}
}