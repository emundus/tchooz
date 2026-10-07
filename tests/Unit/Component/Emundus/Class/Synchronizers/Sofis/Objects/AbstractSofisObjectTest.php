<?php

namespace Unit\Component\Emundus\Class\Synchronizers\Sofis\Objects;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Synchronizers\MappingTransportInterface;
use Tchooz\Synchronizers\Sofis\Objects\VendorObject;

/**
 * @package     Unit\Component\Emundus\Class\Synchronizers\Sofis\Objects
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Synchronizers\Sofis\Objects\AbstractSofisObject
 */
class AbstractSofisObjectTest extends UnitTestCase
{
	private ExposedSofisObject $object;

	protected function setUp(): void
	{
		$this->object = new ExposedSofisObject($this->createMock(ExternalReferenceRepository::class));
	}

	protected function tearDown(): void
	{
		// No dataset created — nothing to clean up.
	}

	// -------------------------------------------------------------------------
	// entityPath()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Synchronizers\Sofis\Objects\AbstractSofisObject::entityPath
	 * @return void
	 */
	public function testEntityPathRoutesEachEntitySetToItsMarioApi(): void
	{
		$this->assertSame('vendors/v1/VendorsV2', $this->object->exposeEntityPath('VendorsV2'));
		$this->assertSame('vendors/v1/VendorBankAccounts', $this->object->exposeEntityPath('VendorBankAccounts'));
		$this->assertSame('financialDimensions/v1/FinancialDimensionValues', $this->object->exposeEntityPath('FinancialDimensionValues'));
		$this->assertSame('purchaseOrders/v1/PurchaseOrderHeadersV2', $this->object->exposeEntityPath('PurchaseOrderHeadersV2'));
		$this->assertSame('purchaseOrders/v1/PurchaseOrderLinesV2', $this->object->exposeEntityPath('PurchaseOrderLinesV2'));
	}

	/**
	 * @covers \Tchooz\Synchronizers\Sofis\Objects\AbstractSofisObject::entityPath
	 * @return void
	 */
	public function testEntityPathWhenEntitySetIsNotExposedByMarioThrows(): void
	{
		$this->expectException(\LogicException::class);

		$this->object->exposeEntityPath('CustomersV3');
	}

	/**
	 * @covers \Tchooz\Synchronizers\Sofis\Objects\AbstractSofisObject::search
	 * @return void
	 */
	public function testSearchCallsTheGatewayRouteCrossCompanyAndReturnsRecords(): void
	{
		$transport = new RecordingSofisTransport(['status' => 200, 'data' => (object) ['value' => [(object) ['VendorAccountNumber' => 'F-1']]]]);
		$filter    = "SiretNumber eq '23456789010876'";

		$records = $this->object->exposeSearch($transport, 'VendorsV2', $filter);

		$this->assertSame('vendors/v1/VendorsV2?$filter=' . rawurlencode($filter) . '&cross-company=true', $transport->calls[0]['url'], 'The search must target the MARIO route, never the raw Dynamics data/ path.');
		$this->assertCount(1, $records);
		$this->assertSame('F-1', $records[0]->VendorAccountNumber);
	}

	// -------------------------------------------------------------------------
	// normalizeSiret()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Synchronizers\Sofis\Objects\AbstractSofisObject::normalizeSiret
	 * @return void
	 */
	public function testNormalizeSiretKeepsDigitsOnly(): void
	{
		$this->assertSame('23456789010876', $this->object->exposeNormalizeSiret('234 567 890 10876'));
		$this->assertSame('23456789010876', $this->object->exposeNormalizeSiret(' 234.567-890 10876 '));
		$this->assertSame('23456789010876', $this->object->exposeNormalizeSiret(23456789010876));
	}

	/**
	 * @covers \Tchooz\Synchronizers\Sofis\Objects\AbstractSofisObject::normalizeSiret
	 * @return void
	 */
	public function testNormalizeSiretWhenEmptyReturnsEmptyString(): void
	{
		$this->assertSame('', $this->object->exposeNormalizeSiret(''));
		$this->assertSame('', $this->object->exposeNormalizeSiret(null));
		$this->assertSame('', $this->object->exposeNormalizeSiret('   '));
	}

	// -------------------------------------------------------------------------
	// mask()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Synchronizers\Sofis\Objects\AbstractSofisObject::mask
	 * @return void
	 */
	public function testMaskKeepsOnlyLastFourCharactersOfBankIdentifiers(): void
	{
		$masked = $this->object->exposeMask([
			'IBAN'                => 'FR7630006000011234567890189',
			'BankAccountNumber'   => '12345678901',
			'VendorAccountNumber' => 'F-FD-000000001',
		]);

		$this->assertSame('****0189', $masked['IBAN']);
		$this->assertSame('****8901', $masked['BankAccountNumber']);
		$this->assertSame('F-FD-000000001', $masked['VendorAccountNumber'], 'Non-sensitive fields must be logged as-is.');
	}
}

/**
 * Exposes the protected helpers of AbstractSofisObject through a concrete Sofis object.
 */
class ExposedSofisObject extends VendorObject
{
	public function exposeEntityPath(string $entitySet): string
	{
		return $this->entityPath($entitySet);
	}

	public function exposeSearch(MappingTransportInterface $transport, string $entitySet, string $filter): array
	{
		return $this->search($transport, $entitySet, $filter);
	}

	public function exposeNormalizeSiret(mixed $siret): string
	{
		return $this->normalizeSiret($siret);
	}

	public function exposeMask(array $data): array
	{
		return $this->mask($data);
	}
}

/**
 * Returns a canned response and records every call.
 */
class RecordingSofisTransport implements MappingTransportInterface
{
	public array $calls = [];

	public function __construct(private array $response)
	{
	}

	public function getBaseUrl(): string
	{
		return 'https://gateway.example.com/sofis';
	}

	public function get(string $url, array $params = [], array $headers = [])
	{
		$this->calls[] = ['method' => 'GET', 'url' => $url];

		return $this->response;
	}

	public function post($url, $body = null, $headers = array(), $asMultipart = false)
	{
		$this->calls[] = ['method' => 'POST', 'url' => $url, 'body' => $body];

		return $this->response;
	}

	public function patch($url, $body = null)
	{
		$this->calls[] = ['method' => 'PATCH', 'url' => $url, 'body' => $body];

		return $this->response;
	}
}
