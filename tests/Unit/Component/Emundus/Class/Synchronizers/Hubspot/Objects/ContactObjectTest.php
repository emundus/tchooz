<?php

namespace Unit\Component\Emundus\Class\Synchronizers\Hubspot\Objects;

use Joomla\CMS\User\User;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Synchronizers\Hubspot\Objects\ContactObject;
use Tchooz\Synchronizers\MappingTransportInterface;
use Tchooz\Synchronizers\ObjectSearchInterface;

/**
 * @package     Unit\Component\Emundus\Class\Synchronizers\Hubspot\Objects
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Synchronizers\Hubspot\Objects\ContactObject
 */
class ContactObjectTest extends UnitTestCase
{
	private ExternalReferenceRepository $externalReferenceRepository;

	private EmundusUserRepository $emundusUserRepository;

	protected function setUp(): void
	{
		$this->externalReferenceRepository = $this->createMock(ExternalReferenceRepository::class);
		$this->emundusUserRepository       = $this->createMock(EmundusUserRepository::class);
	}

	protected function tearDown(): void
	{
		// No dataset created — nothing to clean up.
	}

	private function makeObject(): ContactObject
	{
		return new ContactObject($this->externalReferenceRepository, $this->emundusUserRepository);
	}

	private function makeContext(int $userId): ActionTargetEntity
	{
		$context = $this->createMock(ActionTargetEntity::class);
		$context->method('getUserIdFromFile')->willReturn($userId);

		return $context;
	}

	// -------------------------------------------------------------------------
	// resolveExistence()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Synchronizers\Hubspot\Objects\ContactObject::resolveExistence
	 * @return void
	 */
	public function testResolveExistenceWhenStoredReferenceExistsReturnsPatchWithObjectId(): void
	{
		$storedReference = new ExternalReferenceEntity(1, 'jos_emundus_users.user_id', '5', 'HS999', 7, 'contacts', 'hs_object_id');
		$this->externalReferenceRepository->method('get')->willReturn([$storedReference]);

		$object   = $this->makeObject();
		$mapping  = new MappingEntity(1, 'Mapping', 7, 'contact', [], []);

		// A stored reference short-circuits before the transport is used.
		$resolution = $object->resolveExistence($mapping, $this->makeContext(5), new SearchCapableTransport());

		$this->assertSame(ApiMethodEnum::PATCH, $resolution->method, 'A stored reference must switch the request to PATCH.');
		$this->assertSame('HS999', $resolution->objectId, 'The object id must come from the stored reference.');
		$this->assertNull($resolution->discoveredReference, 'No new reference is discovered when one already exists.');
	}

	/**
	 * @covers \Tchooz\Synchronizers\Hubspot\Objects\ContactObject::resolveExistence
	 * @return void
	 */
	public function testResolveExistenceWhenTransportHasNoSearchReturnsPost(): void
	{
		$this->externalReferenceRepository->method('get')->willReturn([]);

		$object   = $this->makeObject();
		$mapping  = new MappingEntity(1, 'Mapping', 7, 'contact', [], []);
		$transport = new SearchlessTransport();

		$resolution = $object->resolveExistence($mapping, $this->makeContext(5), $transport);

		$this->assertSame(ApiMethodEnum::POST, $resolution->method, 'Without search capability the request stays a creation (POST).');
		$this->assertNull($resolution->objectId, 'No object id is resolved without a stored reference or search.');
	}

	/**
	 * @covers \Tchooz\Synchronizers\Hubspot\Objects\ContactObject::resolveExistence
	 * @return void
	 */
	public function testResolveExistenceWhenNoUserFoundReturnsPost(): void
	{
		$this->externalReferenceRepository->method('get')->willReturn([]);
		$this->emundusUserRepository->method('getByUserId')->willReturn(null);

		$object    = $this->makeObject();
		$mapping   = new MappingEntity(1, 'Mapping', 7, 'contact', [], []);
		$transport = new SearchCapableTransport();

		$resolution = $object->resolveExistence($mapping, $this->makeContext(5), $transport);

		$this->assertSame(ApiMethodEnum::POST, $resolution->method, 'When the user cannot be resolved, the request stays a creation.');
	}

	/**
	 * @covers \Tchooz\Synchronizers\Hubspot\Objects\ContactObject::resolveExistence
	 * @return void
	 */
	public function testResolveExistenceWhenContactFoundByEmailReturnsPatchAndDiscoveredReference(): void
	{
		$this->externalReferenceRepository->method('get')->willReturn([]);

		$user        = new User();
		$user->email = 'applicant@example.com';
		$this->emundusUserRepository->method('getByUserId')->willReturn(new EmundusUserEntity(0, $user, 'John', 'Doe'));

		$object            = $this->makeObject();
		$mapping           = new MappingEntity(1, 'Mapping', 7, 'contact', [], []);
		$transport         = new SearchCapableTransport();
		$transport->result = [(object) ['id' => 'HSABC']];

		$resolution = $object->resolveExistence($mapping, $this->makeContext(5), $transport);

		$this->assertSame(ApiMethodEnum::PATCH, $resolution->method, 'A contact found by email must switch the request to PATCH.');
		$this->assertSame('HSABC', $resolution->objectId, 'The object id must be the HubSpot id found by email.');
		$this->assertNotNull($resolution->discoveredReference, 'A reference must be discovered to be persisted by the executor.');
		$this->assertSame('HSABC', $resolution->discoveredReference->getReference(), 'The discovered reference must point to the found HubSpot id.');
		$this->assertSame('5', $resolution->discoveredReference->getInternId(), 'The discovered reference must carry the internal user id.');
		$this->assertSame(7, $resolution->discoveredReference->getSynchronizerId(), 'The discovered reference must carry the synchronizer id.');
	}

	// -------------------------------------------------------------------------
	// buildPayload() / validate()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Synchronizers\Hubspot\Objects\ContactObject::buildPayload
	 * @return void
	 */
	public function testBuildPayloadWrapsMappedDataInProperties(): void
	{
		$object  = $this->makeObject();
		$mapping = new MappingEntity(1, 'Mapping', 7, 'contact', [], []);

		$payload = $object->buildPayload(['firstname' => 'John', 'lastname' => 'Doe'], $mapping, $this->makeContext(5));

		$this->assertSame(['properties' => ['firstname' => 'John', 'lastname' => 'Doe']], $payload, 'Contact payload must wrap mapped data in a "properties" envelope.');
	}

	/**
	 * @covers \Tchooz\Synchronizers\Hubspot\Objects\ContactObject::validate
	 * @return void
	 */
	public function testValidateWhenNoRequiredFieldsDoesNotThrow(): void
	{
		$object  = $this->makeObject();
		$mapping = new MappingEntity(1, 'Mapping', 7, 'contact', [], []);

		$object->validate($mapping);

		$this->addToAssertionCount(1); // reaching here means no exception was thrown
	}
}

/**
 * Fake HubSpot transport supporting object search.
 */
class SearchCapableTransport implements MappingTransportInterface, ObjectSearchInterface
{
	public array $result = [];

	public function getBaseUrl(): string
	{
		return 'https://api.hubapi.com';
	}

	public function get(string $url, array $params = [], array $headers = [])
	{
		return ['status' => 200, 'data' => null];
	}

	public function post($url, $body = null, $headers = array(), $asMultipart = false)
	{
		return ['status' => 200, 'data' => null];
	}

	public function patch($url, $body = null)
	{
		return ['status' => 200, 'data' => null];
	}

	public function searchObjects(string $searchedObject, array $filters, int $limit = 1, array $returnedProperties = []): array
	{
		return $this->result;
	}
}

/**
 * Fake HubSpot transport WITHOUT object-search capability.
 */
class SearchlessTransport implements MappingTransportInterface
{
	public function getBaseUrl(): string
	{
		return 'https://api.hubapi.com';
	}

	public function get(string $url, array $params = [], array $headers = [])
	{
		return ['status' => 200, 'data' => null];
	}

	public function post($url, $body = null, $headers = array(), $asMultipart = false)
	{
		return ['status' => 200, 'data' => null];
	}

	public function patch($url, $body = null)
	{
		return ['status' => 200, 'data' => null];
	}
}
