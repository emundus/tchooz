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

	/**
	 * @covers \Tchooz\Synchronizers\Sofis\Objects\VendorObject::setDefaultBankAccount
	 * @return void
	 */
	public function testSetDefaultBankAccountPatchesAnAlreadyKnownAccountInsteadOfCreatingIt(): void
	{
		// Back to a previous IBAN: the account already exists in Sofis, a POST would be a duplicate.
		$transport = $this->createMock(MappingTransportInterface::class);
		$transport->method('getBaseUrl')->willReturn('https://sofis.test');
		$transport->method('get')->willReturn([
			'status' => 200,
			'data'   => (object) ['value' => [(object) ['VendorBankAccountId' => '1234567890', 'dataAreaId' => 'fond']]],
		]);
		$transport->expects($this->never())->method('post');
		$transport->expects($this->once())->method('patch')
			->with(
				$this->stringContains('VendorBankAccounts(' . rawurlencode("VendorAccountNumber='F-FD-1'") . '%2C' . rawurlencode("VendorBankAccountId='1234567890'") . '%2C' . rawurlencode("dataAreaId='fond'") . ')'),
				json_encode(['IsDefaultBankAccount' => 'Yes'])
			)
			->willReturn(['status' => 204]);

		(new ExposedVendorObject($this->createMock(ExternalReferenceRepository::class)))
			->exposeSetDefaultBankAccount($transport, 'F-FD-1', ['dataAreaId' => 'FOND', 'IBAN' => 'FR7630006000011234567890'], '1234567890');
	}

	/**
	 * @covers \Tchooz\Synchronizers\Sofis\Objects\VendorObject::setDefaultBankAccount
	 * @return void
	 */
	public function testSetDefaultBankAccountCreatesAnUnknownAccountAsDefault(): void
	{
		$transport = $this->createMock(MappingTransportInterface::class);
		$transport->method('get')->willReturn(['status' => 200, 'data' => (object) ['value' => []]]);
		$transport->expects($this->never())->method('patch');
		$transport->expects($this->once())->method('post')
			->with(
				$this->stringContains('VendorBankAccounts'),
				$this->callback(fn($body) => json_decode($body, true)['IsDefaultBankAccount'] === 'Yes'
					&& json_decode($body, true)['VendorBankAccountId'] === '1234567890')
			)
			->willReturn(['status' => 201, 'data' => (object) ['VendorBankAccountId' => '1234567890']]);

		(new ExposedVendorObject($this->createMock(ExternalReferenceRepository::class)))
			->exposeSetDefaultBankAccount($transport, 'F-FD-1', ['dataAreaId' => 'FOND', 'IBAN' => 'FR7630006000011234567890'], '1234567890');
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

	public function exposeSetDefaultBankAccount(MappingTransportInterface $transport, string $vendorAccountNumber, array $data, string $bankAccountId): void
	{
		$this->setDefaultBankAccount($transport, $vendorAccountNumber, $data, $bankAccountId);
	}
}
