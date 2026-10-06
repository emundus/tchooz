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
}
