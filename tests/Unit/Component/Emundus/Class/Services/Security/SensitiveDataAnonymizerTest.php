<?php

namespace Unit\Component\Emundus\Class\Services\Security;

use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\TestCase;
use Tchooz\Attributes\SensitiveData;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Enums\Security\SensitiveDataStrategy;
use Tchooz\Services\Security\SensitiveDataAnonymizer;

/**
 * @package     Unit\Component\Emundus\Class\Services\Security
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Security\SensitiveDataAnonymizer
 *
 * Pure unit tests: DB is mocked with predictable quote()/quoteName() so no
 * query ever runs. The service is exercised through its two public builders
 * (expressionFor + buildSetExpressions) and through the entity-scanning path
 * against the three currently-decorated entities.
 */
class SensitiveDataAnonymizerTest extends TestCase
{
	private SensitiveDataAnonymizer $anonymizer;

	protected function setUp(): void
	{
		parent::setUp();

		$db = $this->createMock(DatabaseInterface::class);
		$db->method('quote')->willReturnCallback(
			static fn($text): string => "'" . $text . "'"
		);
		$db->method('quoteName')->willReturnCallback(
			static fn($name): string => '`' . $name . '`'
		);

		$this->anonymizer = new SensitiveDataAnonymizer($db);
	}

	// -------------------------------------------------------------------------
	// expressionFor — one strategy at a time
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Security\SensitiveDataAnonymizer::expressionFor
	 * @return void
	 */
	public function testExpressionForEmptyStringReturnsSingleQuotedEmpty(): void
	{
		$this->assertSame(
			"''",
			$this->anonymizer->expressionFor(SensitiveDataStrategy::EMPTY_STRING, '`id`'),
			'EMPTY_STRING must render as a bare empty single-quoted string, safe for any text column.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Security\SensitiveDataAnonymizer::expressionFor
	 * @return void
	 */
	public function testExpressionForNullValueReturnsBareNull(): void
	{
		$this->assertSame(
			'NULL',
			$this->anonymizer->expressionFor(SensitiveDataStrategy::NULL_VALUE, '`id`'),
			'NULL_VALUE must render as a bare SQL NULL, meant for columns that are actually nullable.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Security\SensitiveDataAnonymizer::expressionFor
	 * @return void
	 */
	public function testExpressionForFakeFirstnameSeedsEltDirectlyOnIdColumn(): void
	{
		$expression = $this->anonymizer->expressionFor(SensitiveDataStrategy::FAKE_FIRSTNAME, '`id`');

		$this->assertStringStartsWith('ELT(1 + MOD(`id`, ', $expression, 'FAKE_FIRSTNAME must seed ELT() directly with the id column.');
		$this->assertStringContainsString(
			"'Jean'",
			$expression,
			'The first-name pool must include the canonical entries so every id maps to a real fake name.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Security\SensitiveDataAnonymizer::expressionFor
	 * @return void
	 */
	public function testExpressionForFakeLastnameSeedsWithAShiftedIdSoPoolsDoNotStaySynchronised(): void
	{
		$expression = $this->anonymizer->expressionFor(SensitiveDataStrategy::FAKE_LASTNAME, '`id`');

		$this->assertStringContainsString(
			'FLOOR(`id` / ' . count(SensitiveDataAnonymizer::FIRST_NAMES) . ')',
			$expression,
			'FAKE_LASTNAME must divide the id by the first-name pool size before feeding MOD, so (firstName, lastName) do not collide on every multiple of the pool.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Security\SensitiveDataAnonymizer::expressionFor
	 * @return void
	 */
	public function testExpressionForFakeEmailProducesAddressAtInvalidDomain(): void
	{
		$this->assertSame(
			"CONCAT('anon_', `id`, '@anonymized.invalid')",
			$this->anonymizer->expressionFor(SensitiveDataStrategy::FAKE_EMAIL, '`id`'),
			'FAKE_EMAIL must build an address at the ".invalid" non-routable domain so pre-prod platforms cannot mail out.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Security\SensitiveDataAnonymizer::expressionFor
	 * @return void
	 */
	public function testExpressionForFakeOrganizationNameIsUniquePerRow(): void
	{
		$this->assertSame(
			"CONCAT('Organisation ', `id`)",
			$this->anonymizer->expressionFor(SensitiveDataStrategy::FAKE_ORGANIZATION_NAME, '`id`'),
			'FAKE_ORGANIZATION_NAME must embed the row id so two rows never collide on a UNIQUE-constrained name column.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Security\SensitiveDataAnonymizer::expressionFor
	 * @return void
	 */
	public function testExpressionForUniquePlaceholderIncludesColumnNameToAvoidCollisions(): void
	{
		$this->assertSame(
			"CONCAT('anon_identifier_code_', `id`)",
			$this->anonymizer->expressionFor(SensitiveDataStrategy::UNIQUE_PLACEHOLDER, '`id`', 'identifier_code'),
			'UNIQUE_PLACEHOLDER must embed both the row id and the column name so two UNIQUE columns on the same row never collide.'
		);
	}

	// -------------------------------------------------------------------------
	// buildSetExpressions — reflection scan of a decorated entity
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Security\SensitiveDataAnonymizer::buildSetExpressions
	 * @return void
	 */
	public function testBuildSetExpressionsReturnsOneFragmentPerSensitiveProperty(): void
	{
		$fragments = $this->anonymizer->buildSetExpressions(_SensitiveTestFixture::class, 'id');

		$this->assertCount(
			3,
			$fragments,
			'Every property flagged with #[SensitiveData] must yield exactly one SET fragment (structural properties do not).'
		);
	}

	/**
	 * @covers \Tchooz\Services\Security\SensitiveDataAnonymizer::buildSetExpressions
	 * @return void
	 */
	public function testBuildSetExpressionsMapsPropertyNameToSqlColumnByConvention(): void
	{
		$fragments = $this->anonymizer->buildSetExpressions(_SensitiveTestFixture::class, 'id');
		$joined    = implode(' | ', $fragments);

		$this->assertStringContainsString('`phone` = ', $joined, 'The PHP property name is expected to double as the SQL column name (project convention).');
		$this->assertStringContainsString('`email` = ', $joined, 'Every decorated property must land as a SET on the same-named column.');
		$this->assertStringContainsString('`firstname` = ', $joined, 'Property named firstname must map to a SET on `firstname`.');
	}

	/**
	 * @covers \Tchooz\Services\Security\SensitiveDataAnonymizer::buildSetExpressions
	 * @return void
	 */
	public function testBuildSetExpressionsRoutesEachStrategyToItsExpression(): void
	{
		$fragments = $this->anonymizer->buildSetExpressions(_SensitiveTestFixture::class, 'id');
		$joined    = implode(' | ', $fragments);

		$this->assertStringContainsString("`phone` = ''", $joined, 'Default EMPTY_STRING strategy must produce the empty-string SET.');
		$this->assertStringContainsString("`email` = CONCAT('anon_'", $joined, 'FAKE_EMAIL strategy must produce the fake-email CONCAT.');
		$this->assertStringContainsString('`firstname` = ELT(', $joined, 'FAKE_FIRSTNAME must produce an ELT() expression seeded on the id.');
	}

	/**
	 * @covers \Tchooz\Services\Security\SensitiveDataAnonymizer::buildSetExpressions
	 * @return void
	 */
	public function testBuildSetExpressionsReturnsEmptyArrayOnAnUndecoratedClass(): void
	{
		$fragments = $this->anonymizer->buildSetExpressions(_SensitiveUndecoratedFixture::class, 'id');

		$this->assertSame(
			[],
			$fragments,
			'A class with no #[SensitiveData] property must yield no SET fragment (anonymise() then no-ops).'
		);
	}

	// -------------------------------------------------------------------------
	// Registered entities — decoration coverage
	// -------------------------------------------------------------------------

	/**
	 * @return void
	 */
	public function testContactEntityDeclaresAllExpectedSensitiveProperties(): void
	{
		$fragments = $this->anonymizer->buildSetExpressions(ContactEntity::class, 'id');
		$joined    = implode(' | ', $fragments);

		foreach (['firstname', 'lastname', 'email', 'phone_1', 'birthdate', 'fonction', 'service', 'profile_picture'] as $col) {
			$this->assertStringContainsString('`' . $col . '`', $joined, 'ContactEntity must declare `' . $col . '` as sensitive.');
		}
	}

	/**
	 * @return void
	 */
	public function testContactEntityKeepsStructuralColumnsUntouched(): void
	{
		$fragments = $this->anonymizer->buildSetExpressions(ContactEntity::class, 'id');
		$joined    = implode(' | ', $fragments);

		foreach (['id', 'user_id', 'gender', 'status', 'published'] as $col) {
			$this->assertStringNotContainsString('`' . $col . '` =', $joined, 'ContactEntity structural column `' . $col . '` must not be flagged sensitive.');
		}
	}

	/**
	 * @return void
	 */
	public function testAddressEntityAnonymisesEveryLocationColumnButKeepsCountryFk(): void
	{
		$fragments = $this->anonymizer->buildSetExpressions(AddressEntity::class, 'id');
		$joined    = implode(' | ', $fragments);

		foreach (['street_address', 'extended_address', 'locality', 'region', 'postal_code', 'description'] as $col) {
			$this->assertStringContainsString('`' . $col . '`', $joined, 'AddressEntity must anonymise `' . $col . '`.');
		}

		$this->assertStringNotContainsString('`country` =', $joined, 'AddressEntity.country is a FK to a reference table, not PII: it must be preserved.');
	}

	/**
	 * @return void
	 */
	public function testOrganizationEntityUsesUniquePlaceholderForIdentifierCode(): void
	{
		$fragments = $this->anonymizer->buildSetExpressions(OrganizationEntity::class, 'id');
		$joined    = implode(' | ', $fragments);

		$this->assertStringContainsString(
			"`identifier_code` = CONCAT('anon_identifier_code_',",
			$joined,
			'OrganizationEntity.identifier_code may carry a UNIQUE constraint: anonymisation must produce distinct values per row.'
		);
		$this->assertStringContainsString(
			"`name` = CONCAT('Organisation ',",
			$joined,
			'OrganizationEntity.name must use the FAKE_ORGANIZATION_NAME strategy to stay unique per row.'
		);
	}
}

// -----------------------------------------------------------------------------
// Test fixtures (kept local: they only exist to exercise the reflection scan)
// -----------------------------------------------------------------------------

/**
 * Two sensitive properties (default EMPTY_STRING and FAKE_EMAIL), one plain
 * property (never touched), one FAKE_FIRSTNAME - exercises every branch of
 * buildSetExpressions() without depending on a real entity's shape.
 */
class _SensitiveTestFixture
{
	private int $id;

	#[SensitiveData(SensitiveDataStrategy::FAKE_FIRSTNAME)]
	private string $firstname;

	#[SensitiveData(SensitiveDataStrategy::FAKE_EMAIL)]
	private string $email;

	#[SensitiveData]
	private ?string $phone;

	private ?string $notes;
}

/**
 * Undecorated fixture: buildSetExpressions must return an empty array so
 * anonymise() short-circuits without ever running an empty UPDATE.
 */
class _SensitiveUndecoratedFixture
{
	private int $id;

	private string $firstname;

	private ?string $notes;
}
