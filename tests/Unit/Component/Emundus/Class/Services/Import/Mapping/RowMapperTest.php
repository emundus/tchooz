<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import\Mapping
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import\Mapping;

use PHPUnit\Framework\TestCase;
use Tchooz\Services\Import\Mapping\AliasColumnMap;
use Tchooz\Services\Import\Mapping\RowMapper;

/**
 * @covers \Tchooz\Services\Import\Mapping\RowMapper
 */
class RowMapperTest extends TestCase
{
	private RowMapper $mapper;

	protected function setUp(): void
	{
		$map = AliasColumnMap::create()
			->field('email',     aliases: ['Email', 'Adresse mail'])
			->field('firstname', aliases: ['Prénom'])
			->field('lastname',  aliases: ['Nom'])
			->field('country',   aliases: ['Pays'])
			->build();

		$this->mapper = new RowMapper($map);
	}

	public function testMapTranslatesRawHeadersToCanonicalKeys(): void
	{
		$out = $this->mapper->map([
			'Email'   => 'a@b.fr',
			'Prénom'  => 'Jean',
			'Nom'     => 'Dupont',
			'Pays'    => 'FR',
		]);

		$this->assertSame('a@b.fr',  $out['email']);
		$this->assertSame('Jean',    $out['firstname']);
		$this->assertSame('Dupont',  $out['lastname']);
		$this->assertSame('FR',      $out['country']);
	}

	public function testCanonicalKeysAlwaysAppearEvenWhenAbsentFromRawRow(): void
	{
		// Missing fields must be present as null, not absent — downstream code
		// can rely on `array_key_exists` rather than guarding every access.
		$out = $this->mapper->map(['Email' => 'a@b.fr']);

		$this->assertArrayHasKey('email',     $out);
		$this->assertArrayHasKey('firstname', $out);
		$this->assertArrayHasKey('lastname',  $out);
		$this->assertArrayHasKey('country',   $out);
		$this->assertSame('a@b.fr', $out['email']);
		$this->assertNull($out['firstname']);
		$this->assertNull($out['lastname']);
		$this->assertNull($out['country']);
	}

	public function testUnknownRawHeadersAreSilentlyDropped(): void
	{
		$out = $this->mapper->map([
			'Email'         => 'a@b.fr',
			'Random column' => 'ignored',
			'Foo'           => 'bar',
		]);

		$this->assertSame(['email', 'firstname', 'lastname', 'country'], array_keys($out));
		$this->assertSame('a@b.fr', $out['email']);
	}

	public function testHeaderMatchingIsCaseAndAccentInsensitive(): void
	{
		$out = $this->mapper->map([
			'EMAIL'   => 'a@b.fr',
			'prenom'  => 'Jean',
			'PAYS'    => 'FR',
		]);

		$this->assertSame('a@b.fr', $out['email']);
		$this->assertSame('Jean',   $out['firstname']);
		$this->assertSame('FR',     $out['country']);
	}

	public function testFirstNonEmptyValueWinsForDuplicateHeaders(): void
	{
		// "Email" and "Adresse mail" both map to "email" — if the source has
		// both columns, we must not silently overwrite a real value with null.
		$out = $this->mapper->map([
			'Email'        => 'a@b.fr',
			'Adresse mail' => null,
		]);

		$this->assertSame('a@b.fr', $out['email']);
	}

	public function testEmptyStringDoesNotOverwriteAValueAlreadySet(): void
	{
		$out = $this->mapper->map([
			'Email'        => 'a@b.fr',
			'Adresse mail' => '',
		]);

		$this->assertSame('a@b.fr', $out['email']);
	}

	public function testNonStringHeadersAreIgnored(): void
	{
		// Indexed rows occasionally produce integer keys — make sure we do
		// not throw or mis-route them.
		$out = $this->mapper->map([
			0       => 'should be ignored',
			'Email' => 'a@b.fr',
		]);

		$this->assertSame('a@b.fr', $out['email']);
	}

	// --------------------------------------------------------------------
	// isRowEmpty
	// --------------------------------------------------------------------

	public function testIsRowEmptyDetectsAllNullsAsEmpty(): void
	{
		$row = $this->mapper->map([]);
		$this->assertTrue(RowMapper::isRowEmpty($row));
	}

	public function testIsRowEmptyDetectsAllBlanksAsEmpty(): void
	{
		$row = $this->mapper->map([
			'Email'  => '',
			'Prénom' => '   ',
			'Nom'    => null,
			'Pays'   => '',
		]);
		$this->assertTrue(RowMapper::isRowEmpty($row));
	}

	public function testIsRowEmptyReturnsFalseAsSoonAsOneFieldHasContent(): void
	{
		$row = $this->mapper->map([
			'Email'  => '',
			'Prénom' => 'Jean',
		]);
		$this->assertFalse(RowMapper::isRowEmpty($row));
	}

	public function testIsRowEmptyTreatsZeroAsContent(): void
	{
		// "0" is meaningful business data, not emptiness.
		$row = ['email' => 0, 'firstname' => null, 'lastname' => null, 'country' => null];
		$this->assertFalse(RowMapper::isRowEmpty($row));
	}
}
