<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import\Mapping
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import\Mapping;

use PHPUnit\Framework\TestCase;
use Tchooz\Services\Import\Mapping\HeaderNormalizer;

/**
 * @covers \Tchooz\Services\Import\Mapping\HeaderNormalizer
 */
class HeaderNormalizerTest extends TestCase
{
	public function testEmptyAndWhitespaceInputsReturnEmptyString(): void
	{
		$this->assertSame('', HeaderNormalizer::normalize(''));
		$this->assertSame('', HeaderNormalizer::normalize('   '));
		$this->assertSame('', HeaderNormalizer::normalize("\t\n"));
	}

	public function testCaseIsLowered(): void
	{
		$this->assertSame('name', HeaderNormalizer::normalize('Name'));
		$this->assertSame('name', HeaderNormalizer::normalize('NAME'));
		$this->assertSame('name', HeaderNormalizer::normalize('NaMe'));
	}

	public function testWhitespaceIsTrimmedAndCollapsed(): void
	{
		$this->assertSame('name', HeaderNormalizer::normalize(' name '));
		$this->assertSame('first_name', HeaderNormalizer::normalize('first   name'));
		$this->assertSame('first_name', HeaderNormalizer::normalize("first\tname"));
	}

	public function testAccentsAreStripped(): void
	{
		$this->assertSame('region', HeaderNormalizer::normalize('Région'));
		$this->assertSame('francais', HeaderNormalizer::normalize('Français'));
		$this->assertSame('naive', HeaderNormalizer::normalize('naïve'));
		$this->assertSame('ecu', HeaderNormalizer::normalize('écu'));
	}

	/**
	 * Regression: accent stripping used to go through
	 * iconv('ASCII//TRANSLIT'), whose output depends on the active locale. Under
	 * "C" it renders "é" as "'e", so "Prénom" normalised to "pr_enom" while
	 * "Prenom" gave "prenom" — the two spellings no longer matched and accented
	 * column headers were silently dropped as unrecognised.
	 */
	public function testAccentStrippingDoesNotDependOnTheLocale(): void
	{
		$previous = setlocale(LC_CTYPE, 0);
		setlocale(LC_CTYPE, 'C');

		try
		{
			$this->assertSame('prenom', HeaderNormalizer::normalize('Prénom'));
			$this->assertSame('region', HeaderNormalizer::normalize('Région'));
			$this->assertSame('publie', HeaderNormalizer::normalize('Publié'));
			$this->assertSame(
				'complement_d_adresse',
				HeaderNormalizer::normalize("Complément d'adresse")
			);
		}
		finally
		{
			setlocale(LC_CTYPE, $previous);
		}
	}

	public function testAccentedAndUnaccentedSpellingsShareTheSameKey(): void
	{
		foreach ([['Prénom', 'Prenom'], ['Région', 'Region'], ['Numéro', 'Numero']] as [$accented, $plain])
		{
			$this->assertSame(
				HeaderNormalizer::normalize($plain),
				HeaderNormalizer::normalize($accented),
				sprintf('"%s" and "%s" must resolve to the same column', $accented, $plain)
			);
		}
	}

	public function testNonAlphanumericRunsBecomeSingleUnderscore(): void
	{
		$this->assertSame('e_mail', HeaderNormalizer::normalize('e-mail'));
		$this->assertSame('e_mail', HeaderNormalizer::normalize('E-Mail'));
		$this->assertSame('adresse_e_mail', HeaderNormalizer::normalize('Adresse e-mail'));
		$this->assertSame('a_b_c', HeaderNormalizer::normalize('a@b!c'));
		$this->assertSame('first_name', HeaderNormalizer::normalize('first.name'));
	}

	public function testLeadingAndTrailingUnderscoresAreStripped(): void
	{
		$this->assertSame('test', HeaderNormalizer::normalize('___test___'));
		$this->assertSame('test', HeaderNormalizer::normalize('-test-'));
		$this->assertSame('test', HeaderNormalizer::normalize('  -- test -- '));
	}

	public function testInvisibleCharactersAreStripped(): void
	{
		// Zero-width space, non-breaking space, narrow no-break space
		$this->assertSame('email', HeaderNormalizer::normalize("e\u{200B}mail"));
		$this->assertSame('email', HeaderNormalizer::normalize("\u{00A0}email\u{00A0}"));
		$this->assertSame('e_mail', HeaderNormalizer::normalize("e\u{202F}mail"));
	}

	public function testDigitsArePreserved(): void
	{
		$this->assertSame('phone_1', HeaderNormalizer::normalize('phone_1'));
		$this->assertSame('phone1', HeaderNormalizer::normalize('Phone1'));
		$this->assertSame('address_2', HeaderNormalizer::normalize('Address 2'));
	}

	public function testUnicodeLettersOutsideAsciiAreCollapsed(): void
	{
		// We don't claim to preserve every script — we just guarantee a stable
		// reproducible key. CJK characters collapse to underscores, which is
		// fine because both sides go through the same normalization.
		$normalized = HeaderNormalizer::normalize('名前 Name');
		$this->assertSame('name', $normalized);
	}

	public function testIdempotency(): void
	{
		$once  = HeaderNormalizer::normalize('Adresse e-mail');
		$twice = HeaderNormalizer::normalize($once);
		$this->assertSame($once, $twice);
	}
}
