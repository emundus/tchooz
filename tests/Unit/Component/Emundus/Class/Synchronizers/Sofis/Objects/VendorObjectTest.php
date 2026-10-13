<?php

namespace Unit\Component\Emundus\Class\Synchronizers\Sofis\Objects;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Synchronizers\MappingTransportInterface;
use Tchooz\Synchronizers\Sofis\Objects\VendorObject;

/**
 * @package     Unit\Component\Emundus\Class\Synchronizers\Sofis\Objects
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Synchronizers\Sofis\Objects\VendorObject
 */
class VendorObjectTest extends UnitTestCase
{
	protected function setUp(): void
	{
		// No dataset needed — the transport and the reference repository are mocked.
	}

	protected function tearDown(): void
	{
		// No dataset created — nothing to clean up.
	}

	/**
	 * @covers \Tchooz\Synchronizers\Sofis\Objects\VendorObject::execute
	 * @return void
	 */
	public function testExecuteWithoutSiretThrowsBeforeSearchingSofis(): void
	{
		$referenceRepository = $this->createMock(ExternalReferenceRepository::class);
		$referenceRepository->expects($this->never())->method('flush');

		// An empty SIRET search would match any vendor without SIRET and give it this file's bank account.
		$transport = $this->createMock(MappingTransportInterface::class);
		$transport->expects($this->never())->method('get');
		$transport->expects($this->never())->method('post');

		// No file in the context: the mapping resolves no data, hence no SIRET.
		$context = $this->createMock(ActionTargetEntity::class);
		$context->method('getFile')->willReturn(null);

		$this->expectException(\DomainException::class);

		(new VendorObject($referenceRepository))->execute(new MappingEntity(1, 'Vendor', 7, 'vendor'), $context, $transport);
	}

	/**
	 * @covers \Tchooz\Synchronizers\Sofis\Objects\VendorObject::vendorUpdates
	 * @return void
	 */
	public function testVendorUpdatesKeepsOnlyChangedNonEmptyUpdatableFields(): void
	{
		$existing = (object) [
			'VendorOrganizationName' => 'Ancien nom',
			'AddressCity'            => 'Paris',
			'AddressStreet'          => 'Rue A',
			'CurrencyCode'           => 'EUR',
			'VendorGroupId'          => 'FRN_P_FR',
		];

		$updates = (new ExposedVendorObject($this->createMock(ExternalReferenceRepository::class)))->exposeVendorUpdates($existing, [
			'VendorOrganizationName' => 'Nouveau nom',
			'AddressCity'            => 'Paris',
			'AddressStreet'          => '',
			'CurrencyCode'           => 'EUR',
			'VendorGroupId'          => 'AUTRE_GROUPE',
			'SiretNumber'            => '23456789010876',
			'IBAN'                   => 'FR7630006000011234567890189',
		]);

		$this->assertSame(['VendorOrganizationName' => 'Nouveau nom'], $updates, 'Only changed vendor fields are patched: unchanged, empty, search-key and bank fields are left out.');
	}

	/**
	 * @covers \Tchooz\Synchronizers\Sofis\Objects\VendorObject::vendorUpdates
	 * @return void
	 */
	public function testVendorUpdatesWhenSofisIsUpToDateReturnsNothing(): void
	{
		$existing = (object) ['VendorOrganizationName' => 'Nom', 'AddressCity' => 'Paris'];

		$updates = (new ExposedVendorObject($this->createMock(ExternalReferenceRepository::class)))->exposeVendorUpdates($existing, [
			'VendorOrganizationName' => 'Nom',
			'AddressCity'            => 'Paris',
		]);

		$this->assertSame([], $updates);
	}
}

/**
 * Exposes VendorObject's update decision.
 */
class ExposedVendorObject extends VendorObject
{
	public function exposeVendorUpdates(object $existing, array $data): array
	{
		return $this->vendorUpdates($existing, $data);
	}
}
