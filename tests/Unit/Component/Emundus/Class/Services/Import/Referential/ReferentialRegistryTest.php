<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import\Referential
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import\Referential;

use PHPUnit\Framework\TestCase;
use Tchooz\Services\Import\Referential\ReferentialRegistry;
use Tchooz\Services\Import\Referential\Source\ContactReferentialSource;
use Tchooz\Services\Import\Referential\Source\CountryReferentialSource;
use Tchooz\Services\Import\Referential\Source\OrganizationReferentialSource;

/**
 * Discovery is asserted without ever calling getEntries(), so no source ever
 * touches the database.
 *
 * @covers \Tchooz\Services\Import\Referential\ReferentialRegistry
 */
class ReferentialRegistryTest extends TestCase
{
	protected function setUp(): void
	{
		ReferentialRegistry::resetDefault();
	}

	protected function tearDown(): void
	{
		ReferentialRegistry::resetDefault();
	}

	public function testDefaultAutoDiscoversTheSourceClasses(): void
	{
		$registry = ReferentialRegistry::default();

		$this->assertTrue($registry->has(OrganizationReferentialSource::KEY), 'Organizations source should be discovered');
		$this->assertTrue($registry->has(CountryReferentialSource::KEY), 'Countries source should be discovered');
		$this->assertTrue($registry->has(ContactReferentialSource::KEY), 'Contacts source should be discovered');
	}

	public function testGetReturnsAProviderWhoseKeyMatches(): void
	{
		$provider = ReferentialRegistry::default()->get(OrganizationReferentialSource::KEY);

		$this->assertSame(OrganizationReferentialSource::KEY, $provider->getKey(), 'get() should return the matching source');
	}

	public function testGetReturnsTheSameSharedInstance(): void
	{
		$first  = ReferentialRegistry::default()->get(CountryReferentialSource::KEY);
		$second = ReferentialRegistry::default()->get(CountryReferentialSource::KEY);

		$this->assertSame($first, $second, 'The registry must hand out one shared instance per referential');
	}

	public function testGetUnknownKeyThrows(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		ReferentialRegistry::default()->get('does-not-exist');
	}
}
