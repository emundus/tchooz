<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Fields
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Entities\Fields;

use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Fields\WysiwygField;

/**
 * @covers \Tchooz\Entities\Fields\WysiwygField
 */
class WysiwygFieldTest extends TestCase
{
	private function singleLineField(): WysiwygField
	{
		$field = new WysiwygField('agreementText', 'COM_EMUNDUS_LABEL');
		$field->setSingleLine(true);
		$field->setAllowedMarkup(['b', 'strong', 'i', 'em', 'u', 'a'], ['href', 'target', 'rel'], ['https']);

		return $field;
	}

	private function sanitize(?string $value): string
	{
		return $this->singleLineField()->sanitizeValue($value);
	}

	// --------------------------------------------------------------------
	// No declared markup, no rewriting
	// --------------------------------------------------------------------

	public function testValueIsLeftAsTypedWhenNoMarkupIsDeclared(): void
	{
		$field = new WysiwygField('run_email_body', 'COM_EMUNDUS_LABEL');

		$this->assertSame(
			'<p>Bonjour <span style="color:red">vous</span></p>',
			$field->sanitizeValue('<p>Bonjour <span style="color:red">vous</span></p>'),
			'A field that declares no markup accepts everything, as the rich text editors do'
		);
	}

	// --------------------------------------------------------------------
	// Allowed markup survives
	// --------------------------------------------------------------------

	public function testAllowedInlineTagsAreKept(): void
	{
		$this->assertSame(
			'<strong>a</strong> <em>b</em> <u>c</u> <b>d</b> <i>e</i>',
			$this->sanitize('<strong>a</strong> <em>b</em> <u>c</u> <b>d</b> <i>e</i>'),
			'Bold, italic and underline are the formatting the editor offers'
		);
	}

	public function testLinksKeepTheirAllowedAttributes(): void
	{
		$this->assertSame(
			'<a href="https://example.org" target="_blank">CGU</a>',
			$this->sanitize('<a href="https://example.org" target="_blank">CGU</a>'),
			'A link without its href is not a link any more'
		);
	}

	// --------------------------------------------------------------------
	// Single line
	// --------------------------------------------------------------------

	public function testWrappingParagraphIsRemoved(): void
	{
		$this->assertSame(
			'Je lis et j accepte',
			$this->sanitize('<p>Je lis et j accepte</p>'),
			'The editor wraps its content in a paragraph, which is not inline markup'
		);
	}

	public function testBlockBoundariesBecomeSpacesRatherThanGluingWords(): void
	{
		$this->assertSame('Ligne 1 Ligne 2', $this->sanitize('<p>Ligne 1</p><p>Ligne 2</p>'), 'Two paragraphs');
		$this->assertSame('Avant apres', $this->sanitize('<p>Avant<br>apres</p>'), 'A line break');
		$this->assertSame('Titre suite', $this->sanitize('<h1>Titre</h1>suite'), 'A heading');
	}

	public function testNewlinesNeverReachTheOutput(): void
	{
		$this->assertSame('a b', $this->sanitize("a\n\tb"), 'The value is stored on a single line');
	}

	// --------------------------------------------------------------------
	// Everything else is dropped
	// --------------------------------------------------------------------

	public function testDisallowedTagsAreDroppedButTheirTextIsKept(): void
	{
		$this->assertSame(
			'texte colore',
			$this->sanitize('texte <span style="color:red">colore</span>'),
			'Only the markup is refused, never the wording the integrator typed'
		);
	}

	public function testScriptAndStyleAreDroppedWithTheirContent(): void
	{
		$this->assertSame('ok', $this->sanitize('<script>alert(1)</script>ok'), 'Script content is not wording');
		$this->assertSame('ok', $this->sanitize('<style>p{color:red}</style>ok'), 'Style content is not wording');
	}

	public function testStyleAttributeIsDropped(): void
	{
		$this->assertSame(
			'<strong>a</strong>',
			$this->sanitize('<strong style="font-size:40px">a</strong>'),
			'Only the listed attributes are allowed through'
		);
	}

	// --------------------------------------------------------------------
	// Link targets
	// --------------------------------------------------------------------

	public function testExecutableSchemesNeverKeepTheirTarget(): void
	{
		foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', 'data:text/html;base64,x', 'vbscript:msgbox'] as $href)
		{
			$this->assertSame(
				'clic',
				$this->sanitize('<a href="' . $href . '">clic</a>'),
				'A scheme carrying code is never a link target: ' . $href
			);
		}
	}

	public function testOnlyAllowedSchemesKeepTheirTarget(): void
	{
		$this->assertSame(
			'<a href="https://exemple.fr/cgu">CGU</a>',
			$this->sanitize('<a href="https://exemple.fr/cgu">CGU</a>'),
			'A secure absolute link is the one form that survives'
		);

		foreach (['http://exemple.fr', '//evil.com', 'exemple.fr/cgu', 'mailto:a@b.fr'] as $href)
		{
			$this->assertSame(
				'texte',
				$this->sanitize('<a href="' . $href . '">texte</a>'),
				'Outside the allowed schemes the wording stays but the link goes: ' . $href
			);
		}
	}

	public function testEachLinkIsJudgedOnItsOwn(): void
	{
		$this->assertSame(
			'Je lis les CGU et la <a href="https://y.fr">charte</a>',
			$this->sanitize('<p>Je lis les <a href="http://x.fr">CGU</a> et la <a href="https://y.fr">charte</a></p>'),
			'One refused link does not take the valid ones down with it'
		);
	}

	// --------------------------------------------------------------------
	// Empty input
	// --------------------------------------------------------------------

	public function testEmptyInputGivesAnEmptyString(): void
	{
		foreach ([null, '', '   ', '<p></p>', "\n"] as $value)
		{
			$this->assertSame(
				'',
				trim($this->sanitize($value)),
				'An empty result lets the caller omit the value: ' . var_export($value, true)
			);
		}
	}
}
