<?php

namespace Unit\Component\Emundus\Class\Services\Mapping;

use Joomla\CMS\User\User;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\AssociationDefinition;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\MappingResolution;
use Tchooz\Entities\Mapping\SynchronizerMappingObjectDefinition;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Factories\Mapping\MappingObjectFactory;
use Tchooz\Factories\Synchronizer\SynchronizerFactory;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Mapping\AssociationReoslvers\UserIdResolver;
use Tchooz\Services\Mapping\MappingExecutor;
use Tchooz\Synchronizers\FileUploadInterface;
use Tchooz\Synchronizers\Mapping\MappingObjectInterface;
use Tchooz\Synchronizers\Mapping\SupportsAssociationsInterface;
use Tchooz\Synchronizers\MappingTransportInterface;
use Tchooz\Synchronizers\ObjectSearchInterface;

/**
 * @package     Unit\Component\Emundus\Class\Services\Mapping
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Mapping\MappingExecutor
 */
class MappingExecutorTest extends UnitTestCase
{
	private SynchronizerRepository $synchronizerRepository;

	private SynchronizerFactory $synchronizerFactory;

	private MappingObjectFactory $mappingObjectFactory;

	private ExternalReferenceRepository $externalReferenceRepository;

	private FakeMappingObject $object;

	private FakeExecutorTransport $transport;

	protected function setUp(): void
	{
		$this->synchronizerRepository      = $this->createMock(SynchronizerRepository::class);
		$this->synchronizerFactory         = $this->createMock(SynchronizerFactory::class);
		$this->mappingObjectFactory        = $this->createMock(MappingObjectFactory::class);
		$this->externalReferenceRepository = $this->createMock(ExternalReferenceRepository::class);

		$this->object    = new FakeMappingObject();
		$this->transport = new FakeExecutorTransport();

		$this->synchronizerRepository->method('getById')->willReturn(new SynchronizerEntity(1, 'hubspot', 'Test', '', [], [], true, true));
		$this->synchronizerFactory->method('getApiInstance')->willReturn($this->transport);
		$this->mappingObjectFactory->method('make')->willReturn($this->object);
	}

	protected function tearDown(): void
	{
		// No dataset created — nothing to clean up.
	}

	private function makeExecutor(): MappingExecutor
	{
		return new MappingExecutor(
			$this->synchronizerRepository,
			$this->synchronizerFactory,
			$this->mappingObjectFactory,
			$this->externalReferenceRepository
		);
	}

	private function makeMapping(string $targetObject = 'contact'): MappingEntity
	{
		return new MappingEntity(1, 'Mapping', 1, $targetObject, [], []);
	}

	private function makeContext(): ActionTargetEntity
	{
		// A context without a file makes MappingService return an empty payload without DB access.
		return new ActionTargetEntity(new User(), null, 0);
	}

	// -------------------------------------------------------------------------
	// Routing: PATCH vs POST
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Mapping\MappingExecutor::execute
	 * @return void
	 */
	public function testExecuteWhenResolutionIsPatchSendsPatchAndReturnsTrue(): void
	{
		$this->object->resolution      = new MappingResolution(ApiMethodEnum::PATCH, 5, 'HS1');
		$this->transport->patchResponse = ['status' => 200, 'data' => null];

		$result = $this->makeExecutor()->execute($this->makeMapping(), $this->makeContext());

		$this->assertTrue($result, 'A successful PATCH must return true.');
		$this->assertCount(1, $this->transport->patchCalls, 'Exactly one PATCH request must be sent.');
		$this->assertSame('https://api.hubapi.com/crm/v3/objects/contacts/HS1', $this->transport->patchCalls[0], 'The PATCH must target the object route suffixed by its id.');
	}

	/**
	 * @covers \Tchooz\Services\Mapping\MappingExecutor::execute
	 * @return void
	 */
	public function testExecuteWhenResolutionIsPostPersistsReferenceAndReturnsTrue(): void
	{
		$this->object->resolution        = new MappingResolution(ApiMethodEnum::POST, 5);
		$this->object->createdReference  = new ExternalReferenceEntity(0, 'jos_emundus_users.user_id', '5', 'HSNEW', 1, 'contacts', 'hs_object_id');
		$this->transport->postResponse   = ['status' => 201, 'data' => null];

		$this->externalReferenceRepository->expects($this->once())->method('flush')->willReturn(true);

		$result = $this->makeExecutor()->execute($this->makeMapping(), $this->makeContext());

		$this->assertTrue($result, 'A successful POST with a created reference must return true.');
		$this->assertCount(1, $this->transport->postCalls, 'Exactly one POST request must be sent.');
	}

	/**
	 * @covers \Tchooz\Services\Mapping\MappingExecutor::execute
	 * @return void
	 */
	public function testExecuteWhenPostSucceedsWithoutCreatedReferenceReturnsTrueWithoutFlush(): void
	{
		$this->object->resolution       = new MappingResolution(ApiMethodEnum::POST, 5);
		$this->object->createdReference = null;
		$this->transport->postResponse  = ['status' => 200, 'data' => null];

		$this->externalReferenceRepository->expects($this->never())->method('flush');

		$result = $this->makeExecutor()->execute($this->makeMapping(), $this->makeContext());

		$this->assertTrue($result, 'A successful POST without a tracked reference must still return true.');
	}

	// -------------------------------------------------------------------------
	// Associations
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Mapping\MappingExecutor::execute
	 * @return void
	 */
	public function testExecuteWhenObjectHasAssociationsAppliesThem(): void
	{
		$this->object->resolution       = new MappingResolution(ApiMethodEnum::POST, 5);
		$this->object->createdReference = new ExternalReferenceEntity(0, 'col', '5', 'HSNEW', 1, 'deals', 'hs_object_id');
		$this->object->associations     = [new AssociationDefinition('contact', new UserIdResolver())];
		$this->object->applyResult      = true;
		$this->transport->postResponse  = ['status' => 201, 'data' => null];
		$this->externalReferenceRepository->method('flush')->willReturn(true);

		$result = $this->makeExecutor()->execute($this->makeMapping('deal'), $this->makeContext());

		$this->assertTrue($result, 'A successful POST with successful associations must return true.');
		$this->assertTrue($this->object->applyCalled, 'The executor must drive associations when the object declares them.');
	}

	/**
	 * @covers \Tchooz\Services\Mapping\MappingExecutor::execute
	 * @return void
	 */
	public function testExecuteWhenAssociationFailsThrowsRuntimeException(): void
	{
		$this->object->resolution       = new MappingResolution(ApiMethodEnum::POST, 5);
		$this->object->createdReference = new ExternalReferenceEntity(0, 'col', '5', 'HSNEW', 1, 'deals', 'hs_object_id');
		$this->object->associations     = [new AssociationDefinition('contact', new UserIdResolver())];
		$this->object->applyResult      = false;
		$this->transport->postResponse  = ['status' => 201, 'data' => null];
		$this->externalReferenceRepository->method('flush')->willReturn(true);

		$this->expectException(\RuntimeException::class);

		$this->makeExecutor()->execute($this->makeMapping('deal'), $this->makeContext());
	}

	// -------------------------------------------------------------------------
	// Guards
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Mapping\MappingExecutor::execute
	 * @return void
	 */
	public function testExecuteWhenTargetObjectEmptyReturnsFalse(): void
	{
		$result = $this->makeExecutor()->execute($this->makeMapping(''), $this->makeContext());

		$this->assertFalse($result, 'A mapping without a target object must not be executed.');
	}

	/**
	 * @covers \Tchooz\Services\Mapping\MappingExecutor::execute
	 * @return void
	 */
	public function testExecuteWhenSynchronizerNotFoundThrowsDomainException(): void
	{
		$repository = $this->createMock(SynchronizerRepository::class);
		$repository->method('getById')->willReturn(null);

		$executor = new MappingExecutor($repository, $this->synchronizerFactory, $this->mappingObjectFactory, $this->externalReferenceRepository);

		$this->expectException(\DomainException::class);

		$executor->execute($this->makeMapping(), $this->makeContext());
	}

	/**
	 * @covers \Tchooz\Services\Mapping\MappingExecutor::execute
	 * @return void
	 */
	public function testExecuteWhenTransportDoesNotSupportMappingThrowsDomainException(): void
	{
		$factory = $this->createMock(SynchronizerFactory::class);
		$factory->method('getApiInstance')->willReturn(new \stdClass());

		$executor = new MappingExecutor($this->synchronizerRepository, $factory, $this->mappingObjectFactory, $this->externalReferenceRepository);

		$this->expectException(\DomainException::class);

		$executor->execute($this->makeMapping(), $this->makeContext());
	}
}

/**
 * Configurable mapping object used to drive the executor without any connector logic.
 */
class FakeMappingObject implements MappingObjectInterface, SupportsAssociationsInterface
{
	public SynchronizerMappingObjectDefinition $definition;

	public MappingResolution $resolution;

	public array $payload = ['properties' => []];

	public ?ExternalReferenceEntity $createdReference = null;

	public array $associations = [];

	public bool $applyResult = true;

	public bool $applyCalled = false;

	public function __construct()
	{
		$this->definition = new SynchronizerMappingObjectDefinition(
			'fake',
			'FAKE_LABEL',
			'/crm/v3/objects/contacts',
			new ExternalReferenceEntity(0, 'col', '', '', null, 'contacts', 'hs_object_id'),
			[ApiMethodEnum::GET, ApiMethodEnum::POST]
		);
		$this->resolution = new MappingResolution(ApiMethodEnum::POST);
	}

	public function getName(): string
	{
		return 'fake';
	}

	public function getDefinition(): SynchronizerMappingObjectDefinition
	{
		return $this->definition;
	}

	public function validate(MappingEntity $mapping): void
	{
	}

	public function resolveExistence(MappingEntity $mapping, ActionTargetEntity $context, MappingTransportInterface $transport): MappingResolution
	{
		return $this->resolution;
	}

	public function buildPayload(array $mappedData, MappingEntity $mapping, ActionTargetEntity $context): array
	{
		return $this->payload;
	}

	public function buildCreatedReference(mixed $response, mixed $internalId, MappingEntity $mapping): ?ExternalReferenceEntity
	{
		return $this->createdReference;
	}

	public function getAssociations(): array
	{
		return $this->associations;
	}

	public function applyAssociations(ExternalReferenceEntity $createdReference, mixed $internalId, ActionTargetEntity $context, MappingTransportInterface&ObjectSearchInterface $transport): bool
	{
		$this->applyCalled = true;

		return $this->applyResult;
	}
}

/**
 * Fake transport capturing requests and returning configurable responses.
 */
class FakeExecutorTransport implements MappingTransportInterface, ObjectSearchInterface, FileUploadInterface
{
	public array $postCalls = [];

	public array $patchCalls = [];

	public array $postResponse = ['status' => 200, 'data' => null];

	public array $patchResponse = ['status' => 200, 'data' => null];

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
		$this->postCalls[] = $url;

		return $this->postResponse;
	}

	public function patch($url, $body = null)
	{
		$this->patchCalls[] = $url;

		return $this->patchResponse;
	}

	public function searchObjects(string $searchedObject, array $filters, int $limit = 1, array $returnedProperties = []): array
	{
		return [];
	}

	public function uploadFile(\Tchooz\Entities\Upload\UploadEntity $upload, int $synchronizerId): ?string
	{
		return 'https://file.example/' . $upload->getId();
	}
}
