<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import\Mapping
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import\Mapping;

use PHPUnit\Framework\TestCase;
use Tchooz\Enums\Import\FieldTypeEnum;
use Tchooz\Services\Import\Mapping\AliasColumnMap;

// ---------------------------------------------------------------------------
// Fixture enums kept inline so PHPUnit picks them up alongside the test class.
// ---------------------------------------------------------------------------

/** Backed-string enum exposing getLabel() — exercises the label resolution branch. */
enum StatusEnumFixture: string
{
	case ACTIVE   = 'active';
	case INACTIVE = 'inactive';
	case PENDING  = 'pending';

	public function getLabel(): string
	{
		return match ($this) {
			self::ACTIVE   => 'Actif',
			self::INACTIVE => 'Inactif',
			self::PENDING  => 'En attente',
		};
	}
}

/** Backed-int enum without getLabel() — exercises the case-name fallback. */
enum PriorityEnumFixture: int
{
	case LOW    = 1;
	case MEDIUM = 2;
	case HIGH   = 3;
}

/**
 * @covers \Tchooz\Services\Import\Mapping\AliasColumnMap
 * @covers \Tchooz\Services\Import\Mapping\AliasColumnMapBuilder
 * @covers \Tchooz\Services\Import\Mapping\FieldDescriptor
 */
class AliasColumnMapTest extends TestCase
{
	// --------------------------------------------------------------------
	// Header tolerance — the headline guarantee of the service
	// --------------------------------------------------------------------

	/**
	 * Headline behavior: declaring a canonical "name" must accept any
	 * casing of "name" found in a source file. No alias needed.
	 */
	public function testCanonicalNameItselfMatchesAnyCasing(): void
	{
		$map = AliasColumnMap::create()
			->field('name')
			->build();

		foreach (['name', 'Name', 'NAME', 'NaMe', ' name ', "name\t"] as $variant)
		{
			$this->assertSame('name', $map->resolve($variant), sprintf('Failed to resolve "%s"', $variant));
		}
	}

	public function testAliasesResolveBackToCanonical(): void
	{
		$map = AliasColumnMap::create()
			->field('email', aliases: ['Email', 'Adresse mail', 'E-mail'])
			->build();

		$this->assertSame('email', $map->resolve('Email'));
		$this->assertSame('email', $map->resolve('Adresse mail'));
		$this->assertSame('email', $map->resolve('E-mail'));
		// canonical name itself still works
		$this->assertSame('email', $map->resolve('email'));
	}

	public function testResolutionIsCaseInsensitive(): void
	{
		$map = AliasColumnMap::create()
			->field('country', aliases: ['Pays'])
			->build();

		$this->assertSame('country', $map->resolve('PAYS'));
		$this->assertSame('country', $map->resolve('pays'));
		$this->assertSame('country', $map->resolve('Pays'));
		$this->assertSame('country', $map->resolve('COUNTRY'));
	}

	public function testResolutionIsAccentInsensitive(): void
	{
		$map = AliasColumnMap::create()
			->field('region',  aliases: ['Région'])
			->field('address', aliases: ["Complément d'adresse"])
			->build();

		$this->assertSame('region',  $map->resolve('Region'));
		$this->assertSame('region',  $map->resolve('REGION'));
		$this->assertSame('region',  $map->resolve('Région'));
		$this->assertSame('address', $map->resolve("Complement d'adresse"));
	}

	public function testResolutionIgnoresWhitespaceAndPunctuationVariations(): void
	{
		$map = AliasColumnMap::create()
			->field('postal_code', aliases: ['Code postal'])
			->build();

		$this->assertSame('postal_code', $map->resolve('Code postal'));
		$this->assertSame('postal_code', $map->resolve(' code  postal '));
		$this->assertSame('postal_code', $map->resolve('Code-postal'));
		$this->assertSame('postal_code', $map->resolve('code_postal'));
	}

	public function testUnknownHeaderResolvesToNull(): void
	{
		$map = AliasColumnMap::create()
			->field('name')
			->build();

		$this->assertNull($map->resolve('Unknown column'));
		$this->assertNull($map->resolve(''));
		$this->assertNull($map->resolve('   '));
	}

	// --------------------------------------------------------------------
	// describe() — payload consumed by the frontend to document the format
	// --------------------------------------------------------------------

	public function testDescribeReturnsOneEntryPerCanonicalFieldInDeclarationOrder(): void
	{
		$map = AliasColumnMap::create()
			->field('name',  aliases: ['Nom', 'Name'], required: true)
			->field('email', aliases: ['Email'])
			->field('phone')
			->build();

		$descriptors = $map->describe();

		$this->assertCount(3, $descriptors);
		$this->assertSame('name',  $descriptors[0]['canonical']);
		$this->assertSame('email', $descriptors[1]['canonical']);
		$this->assertSame('phone', $descriptors[2]['canonical']);
	}

	public function testDescribeKeepsOriginalAliasStrings(): void
	{
		$map = AliasColumnMap::create()
			->field('email', aliases: ['Email', 'Adresse mail', 'E-mail'])
			->build();

		$this->assertSame(
			['Email', 'Adresse mail', 'E-mail'],
			$map->describe()[0]['aliases']
		);
	}

	public function testDescribeReportsRequiredFlag(): void
	{
		$map = AliasColumnMap::create()
			->field('name',    required: true)
			->field('comment', required: false)
			->build();

		$descriptors = $map->describe();

		$this->assertTrue($descriptors[0]['required']);
		$this->assertFalse($descriptors[1]['required']);
	}

	public function testDescribeReturnsEmptyAliasListWhenNoneDeclared(): void
	{
		$map = AliasColumnMap::create()
			->field('name')
			->build();

		$this->assertSame([], $map->describe()[0]['aliases']);
	}

	public function testDescribeFiltersOutBlankAliases(): void
	{
		// Builder is lenient with blank/whitespace aliases, but they should
		// not appear in the descriptor consumed by the frontend.
		$map = AliasColumnMap::create()
			->field('name', aliases: ['', '   ', 'Nom'])
			->build();

		$this->assertSame(['Nom'], $map->describe()[0]['aliases']);
	}

	public function testDescribePayloadIsJsonSerializable(): void
	{
		$map = AliasColumnMap::create()
			->field('name', aliases: ['Nom'], required: true)
			->build();

		$json = json_encode($map->describe(), JSON_THROW_ON_ERROR);

		$this->assertJson($json);
		$decoded = json_decode($json, true);
		$this->assertSame('name',  $decoded[0]['canonical']);
		$this->assertSame(['Nom'], $decoded[0]['aliases']);
		$this->assertTrue($decoded[0]['required']);
	}

	// --------------------------------------------------------------------
	// type / values / format — typing metadata exposed via describe()
	// --------------------------------------------------------------------

	public function testDefaultTypeIsStringWhenNoneDeclared(): void
	{
		$map = AliasColumnMap::create()
			->field('name')
			->build();

		$this->assertSame('string', $map->describe()[0]['type']);
	}

	public function testTypeFlowsThroughDescribePayload(): void
	{
		$map = AliasColumnMap::create()
			->field('email',     type: FieldTypeEnum::EMAIL)
			->field('birthdate', type: FieldTypeEnum::DATE)
			->field('count',     type: FieldTypeEnum::INTEGER)
			->build();

		$descriptors = $map->describe();
		$this->assertSame('email',   $descriptors[0]['type']);
		$this->assertSame('date',    $descriptors[1]['type']);
		$this->assertSame('integer', $descriptors[2]['type']);
	}

	public function testTypeLabelIsExposedAlongsideTypeInDescribePayload(): void
	{
		$map = AliasColumnMap::create()
			->field('email',     type: FieldTypeEnum::EMAIL)
			->field('birthdate', type: FieldTypeEnum::DATE)
			->field('name')
			->build();

		$descriptors = $map->describe();

		// Both the raw value and the localized label must always be present so
		// the frontend can pick the format it needs without falling back to its
		// own translation table.
		foreach ($descriptors as $descriptor)
		{
			$this->assertArrayHasKey('type',       $descriptor);
			$this->assertArrayHasKey('type_label', $descriptor);
			$this->assertIsString($descriptor['type_label']);
			$this->assertNotSame('', $descriptor['type_label']);
		}
	}

	public function testFieldTypeEnumExposesLabelForEveryCase(): void
	{
		// Sanity check: getLabel() must resolve to a non-empty string for every
		// case, otherwise the doc sheet would show "" for one of the types.
		foreach (FieldTypeEnum::cases() as $case)
		{
			$this->assertIsString($case->getLabel());
			$this->assertNotSame('', $case->getLabel(), sprintf('Empty label for %s', $case->name));
		}
	}

	public function testFormatHintFlowsThroughDescribePayload(): void
	{
		$map = AliasColumnMap::create()
			->field('country',   format: 'iso-3166-1-alpha-2')
			->field('birthdate', type: FieldTypeEnum::DATE, format: 'YYYY-MM-DD')
			->field('name')
			->build();

		$descriptors = $map->describe();
		$this->assertSame('iso-3166-1-alpha-2', $descriptors[0]['format']);
		$this->assertSame('YYYY-MM-DD',         $descriptors[1]['format']);
		// Fields without a format must NOT have the key at all (stable shape).
		$this->assertArrayNotHasKey('format', $descriptors[2]);
	}

	public function testEnumValuesAreInlinedFromFlatStringArray(): void
	{
		$map = AliasColumnMap::create()
			->field('priority', type: FieldTypeEnum::ENUM, values: ['low', 'medium', 'high'])
			->build();

		$values = $map->describe()[0]['values'];

		$this->assertCount(3, $values);
		$this->assertSame(['value' => 'low',    'label' => 'low'],    $values[0]);
		$this->assertSame(['value' => 'medium', 'label' => 'medium'], $values[1]);
		$this->assertSame(['value' => 'high',   'label' => 'high'],   $values[2]);
	}

	public function testEnumClassResolvesValuesAndUsesGetLabelWhenAvailable(): void
	{
		$map = AliasColumnMap::create()
			->field('status', type: FieldTypeEnum::ENUM, values: StatusEnumFixture::class)
			->build();

		$values = $map->describe()[0]['values'];

		$this->assertSame(['value' => 'active',   'label' => 'Actif'],      $values[0]);
		$this->assertSame(['value' => 'inactive', 'label' => 'Inactif'],    $values[1]);
		$this->assertSame(['value' => 'pending',  'label' => 'En attente'], $values[2]);
	}

	public function testIntBackedEnumWithoutGetLabelFallsBackToCaseName(): void
	{
		$map = AliasColumnMap::create()
			->field('priority', type: FieldTypeEnum::ENUM, values: PriorityEnumFixture::class)
			->build();

		$values = $map->describe()[0]['values'];

		// Int values are stringified, labels fall back to the case name.
		$this->assertSame(['value' => '1', 'label' => 'LOW'],    $values[0]);
		$this->assertSame(['value' => '2', 'label' => 'MEDIUM'], $values[1]);
		$this->assertSame(['value' => '3', 'label' => 'HIGH'],   $values[2]);
	}

	public function testNonEnumFieldsHaveNoValuesKeyInDescriptor(): void
	{
		$map = AliasColumnMap::create()
			->field('name')
			->field('birthdate', type: FieldTypeEnum::DATE)
			->build();

		foreach ($map->describe() as $descriptor)
		{
			$this->assertArrayNotHasKey('values', $descriptor);
		}
	}

	public function testEnumTypeWithoutValuesIsRejectedAtBuildTime(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/declared as ENUM but no values/i');

		AliasColumnMap::create()
			->field('status', type: FieldTypeEnum::ENUM);
	}

	public function testValuesProvidedForNonEnumTypeIsRejectedAtBuildTime(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/carries values but its type is/i');

		AliasColumnMap::create()
			->field('comment', type: FieldTypeEnum::STRING, values: ['foo']);
	}

	public function testUnknownEnumClassNameIsRejectedAtBuildTime(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		AliasColumnMap::create()
			->field('status', type: FieldTypeEnum::ENUM, values: 'No\\Such\\Enum')
			->build();
	}

	// --------------------------------------------------------------------
	// examples — illustrative samples (open list) for non-ENUM types
	// --------------------------------------------------------------------

	public function testExamplesAcceptFlatStringArrayValueEqualsLabel(): void
	{
		$map = AliasColumnMap::create()
			->field('phone', format: 'E.164', examples: ['+33612345678', '+447911123456'])
			->build();

		$examples = $map->describe()[0]['examples'];

		$this->assertSame(['value' => '+33612345678',  'label' => '+33612345678'],  $examples[0]);
		$this->assertSame(['value' => '+447911123456', 'label' => '+447911123456'], $examples[1]);
	}

	public function testExamplesAcceptAssociativeArrayValueToLabel(): void
	{
		$map = AliasColumnMap::create()
			->field('country',
				format:   'iso-3166-1-alpha-2',
				examples: ['FR' => 'France', 'GB' => 'United Kingdom']
			)
			->build();

		$examples = $map->describe()[0]['examples'];

		$this->assertSame(['value' => 'FR', 'label' => 'France'],         $examples[0]);
		$this->assertSame(['value' => 'GB', 'label' => 'United Kingdom'], $examples[1]);
	}

	public function testExamplesAcceptStructuredArray(): void
	{
		$map = AliasColumnMap::create()
			->field('birthdate',
				type:     FieldTypeEnum::DATE,
				format:   'YYYY-MM-DD',
				examples: [
					['value' => '2026-04-29', 'label' => '29 avril 2026'],
					['value' => '1990-01-15', 'label' => '15 janvier 1990'],
				]
			)
			->build();

		$examples = $map->describe()[0]['examples'];

		$this->assertSame(['value' => '2026-04-29', 'label' => '29 avril 2026'],     $examples[0]);
		$this->assertSame(['value' => '1990-01-15', 'label' => '15 janvier 1990'],   $examples[1]);
	}

	public function testExamplesStructuredEntryWithoutValueIsRejected(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/must have a "value" key/');

		AliasColumnMap::create()
			->field('phone', examples: [['label' => 'no value here']])
			->build();
	}

	public function testExamplesAreOmittedFromDescriptorWhenNotDeclared(): void
	{
		$map = AliasColumnMap::create()
			->field('name')
			->field('phone', format: 'E.164', examples: ['+33612345678'])
			->build();

		$this->assertArrayNotHasKey('examples', $map->describe()[0]);
		$this->assertArrayHasKey('examples',    $map->describe()[1]);
	}

	public function testExamplesOnEnumFieldAreRejectedAtBuildTime(): void
	{
		// ENUM exposes a closed list via `values` — illustrative examples would be redundant.
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/examples are not allowed/i');

		AliasColumnMap::create()
			->field('status',
				type: FieldTypeEnum::ENUM,
				values: ['active', 'inactive'],
				examples: ['active']
			);
	}

	public function testFullDescriptorShapeIsJsonStable(): void
	{
		$map = AliasColumnMap::create()
			->field('email',  aliases: ['Email'],  required: true, type: FieldTypeEnum::EMAIL)
			->field('status', aliases: ['Statut'], type: FieldTypeEnum::ENUM, values: StatusEnumFixture::class)
			->field('country', aliases: ['Pays'], format: 'iso-3166-1-alpha-2',
				examples: ['FR' => 'France', 'GB' => 'United Kingdom'])
			->build();

		$json    = json_encode($map->describe(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
		$decoded = json_decode($json, true);

		$this->assertSame('email', $decoded[0]['type']);
		$this->assertArrayNotHasKey('values',   $decoded[0]);
		$this->assertArrayNotHasKey('format',   $decoded[0]);
		$this->assertArrayNotHasKey('examples', $decoded[0]);

		$this->assertSame('enum', $decoded[1]['type']);
		$this->assertCount(3, $decoded[1]['values']);
		$this->assertArrayNotHasKey('format',   $decoded[1]);
		$this->assertArrayNotHasKey('examples', $decoded[1]);

		$this->assertSame('string', $decoded[2]['type']);
		$this->assertArrayNotHasKey('values', $decoded[2]);
		$this->assertSame('iso-3166-1-alpha-2', $decoded[2]['format']);
		$this->assertCount(2, $decoded[2]['examples']);
		$this->assertSame(['value' => 'FR', 'label' => 'France'], $decoded[2]['examples'][0]);
	}

	// --------------------------------------------------------------------
	// Field declarations
	// --------------------------------------------------------------------

	public function testCanonicalFieldsAreReturnedInDeclarationOrder(): void
	{
		$map = AliasColumnMap::create()
			->field('zeta')
			->field('alpha')
			->field('mu')
			->build();

		$this->assertSame(['zeta', 'alpha', 'mu'], $map->canonicalFields());
	}

	public function testRequiredFieldsExposesOnlyRequiredOnes(): void
	{
		$map = AliasColumnMap::create()
			->field('name',     required: true)
			->field('email',    required: true)
			->field('comment')
			->build();

		$this->assertSame(['name', 'email'], $map->requiredFields());
	}

	public function testRequiredFieldsIsEmptyWhenNoneFlagged(): void
	{
		$map = AliasColumnMap::create()
			->field('a')
			->field('b')
			->build();

		$this->assertSame([], $map->requiredFields());
	}

	// --------------------------------------------------------------------
	// Builder safety: detect misconfigurations early
	// --------------------------------------------------------------------

	public function testDeclaringTheSameCanonicalTwiceThrows(): void
	{
		$builder = AliasColumnMap::create()->field('name');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Canonical field "name" declared twice.');

		$builder->field('name');
	}

	public function testEmptyCanonicalNameThrows(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		AliasColumnMap::create()->field('');
	}

	public function testAliasConflictBetweenTwoCanonicalsThrows(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/already resolves to "first_name"/');

		AliasColumnMap::create()
			->field('first_name', aliases: ['Prénom'])
			->field('given_name', aliases: ['Prénom'])
			->build();
	}

	public function testCanonicalNameOfOneFieldCollidingWithAliasOfAnotherThrows(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		AliasColumnMap::create()
			->field('email', aliases: ['Adresse mail'])
			->field('mail',  aliases: ['Email'])  // "Email" normalises to "email" → conflict with first canonical
			->build();
	}

	public function testEmptyAliasIsSilentlyIgnored(): void
	{
		// Builder must not blow up when an alias normalises to empty string;
		// it just doesn't index it (canonical still resolves).
		$map = AliasColumnMap::create()
			->field('name', aliases: ['', '   '])
			->build();

		$this->assertSame('name', $map->resolve('name'));
		$this->assertNull($map->resolve(''));
	}

	public function testRedeclaringSameAliasOnSameCanonicalIsAllowed(): void
	{
		// Indexing the same alias for the same canonical twice is harmless —
		// the conflict guard only fires for *different* canonicals.
		$map = AliasColumnMap::create()
			->field('email', aliases: ['Email', 'Email'])
			->build();

		$this->assertSame('email', $map->resolve('Email'));
	}
}
