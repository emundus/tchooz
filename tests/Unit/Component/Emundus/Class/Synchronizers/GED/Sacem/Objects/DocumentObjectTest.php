<?php

namespace Unit\Component\Emundus\Class\Synchronizers\GED\Sacem\Objects;

use Joomla\CMS\Language\Text;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionExecutionMessage;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Enums\Automation\ActionMessageTypeEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Repositories\Upload\UploadRepository;
use Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject;
use Tchooz\Synchronizers\GED\Sacem\SacemSynchronizer;

/**
 * @package     Unit\Component\Emundus\Class\Synchronizers\GED\Sacem\Objects
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject
 */
class DocumentObjectTest extends UnitTestCase
{
	private const SYNCHRONIZER_ID = 12;

	private const ATTACHMENT_ID = 33;

	private const FNUM = '2026030412345678901234';

	/** Target attributes of the GED payload, in declaration order. */
	private const TARGET_FIELDS = ['annee', 'codeProg', 'etat', 'nomBenef', 'numPers', 'refDemande', 'typeDoc', 'typePorteur'];

	private ExternalReferenceRepository $externalReferenceRepository;

	private UploadRepository $uploadRepository;

	private array $temporaryFiles = [];

	protected function setUp(): void
	{
		$this->externalReferenceRepository = $this->createMock(ExternalReferenceRepository::class);
		$this->uploadRepository            = $this->createMock(UploadRepository::class);
	}

	protected function tearDown(): void
	{
		foreach ($this->temporaryFiles as $file)
		{
			if (file_exists($file))
			{
				unlink($file);
			}
		}

		$this->temporaryFiles = [];
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * A DocumentObject whose mapped attributes are provided by the test instead of resolved from the
	 * database. The documents themselves come from the (mocked) upload repository, as in production.
	 */
	private function makeObject(array $mappedData = []): DocumentObject
	{
		return new class($this->externalReferenceRepository, $this->uploadRepository, $mappedData ?: $this->makeMetadata()) extends DocumentObject {
			public function __construct(
				ExternalReferenceRepository $externalReferenceRepository,
				UploadRepository $uploadRepository,
				private array $mappedData
			) {
				parent::__construct($externalReferenceRepository, $uploadRepository);
			}

			protected function resolveMappedData(MappingEntity $mapping, ActionTargetEntity $context): array
			{
				return $this->mappedData;
			}
		};
	}

	/**
	 * A fully configured connector: a document type chosen, and one mapping row per target attribute.
	 */
	private function makeMapping(array $params = ['attachment_id' => self::ATTACHMENT_ID], array $targets = self::TARGET_FIELDS): MappingEntity
	{
		$rows = [];

		foreach ($targets as $index => $target)
		{
			$rows[] = new MappingRowEntity($index + 1, 1, $index, ConditionTargetTypeEnum::STATICVALUE, 'source', $target);
		}

		return new MappingEntity(1, 'Déversement GED', self::SYNCHRONIZER_ID, 'document', $params, $rows);
	}

	private function makeMetadata(array $overrides = []): array
	{
		return array_merge([
			'annee'       => '2026',
			'codeProg'    => 'PROG1',
			'etat'        => '1',
			'nomBenef'    => 'Camille DURAND',
			'numPers'     => '4242',
			'refDemande'  => self::FNUM,
			'typeDoc'     => 'CONV',
			'typePorteur' => 'GPD',
		], $overrides);
	}

	private function makeUpload(int $id, string $extension = 'pdf'): UploadEntity
	{
		$path = sys_get_temp_dir() . '/sacem-ged-test-' . $id . '.' . $extension;
		file_put_contents($path, '%PDF-1.4');
		$this->temporaryFiles[] = $path;

		$upload = $this->createMock(UploadEntity::class);
		$upload->method('getId')->willReturn($id);
		$upload->method('getExtension')->willReturn($extension);
		$upload->method('getFileInternalPath')->willReturn($path);
		$upload->method('getFilename')->willReturn('convention-' . $id . '.' . $extension);
		$upload->method('getDescription')->willReturn('Convention signée');
		$upload->method('getTimedate')->willReturn(new \DateTimeImmutable('2026-03-04 10:00:00'));

		return $upload;
	}

	/**
	 * Expectations are built with Text::sprintf() too: whether the language files happen to be loaded
	 * by the rest of the suite or not, the assertion compares like with like.
	 *
	 * @param   array<ActionExecutionMessage>  $messages
	 */
	private function messageTexts(array $messages, ?ActionMessageTypeEnum $type = null): array
	{
		$texts = [];

		foreach ($messages as $message)
		{
			if ($type === null || $message->getType() === $type)
			{
				$texts[] = $message->getMessage();
			}
		}

		return $texts;
	}

	private function makeContext(string $fnum = self::FNUM): ActionTargetEntity
	{
		$context = $this->createMock(ActionTargetEntity::class);
		$context->method('getFile')->willReturn($fnum);

		return $context;
	}

	// -------------------------------------------------------------------------
	// getDefinition()
	// -------------------------------------------------------------------------

	/**
	 * The right-hand column carries GED attributes only: the document itself is not an attribute, it
	 * is resolved from the chosen type and the file in context.
	 *
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::buildDefinition
	 * @return void
	 */
	public function testDefinitionDeclaresTheGedAttributesAsRequiredTargets(): void
	{
		$fields = $this->makeObject()->getDefinition()->getAvailableFields();

		$this->assertSame(self::TARGET_FIELDS, array_map(static fn($field) => $field->getName(), $fields));

		foreach ($fields as $field)
		{
			$this->assertTrue($field->isRequired(), $field->getName() . ' must be required.');
		}
	}

	/**
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::buildDefinition
	 * @return void
	 */
	public function testDefinitionAsksTheConnectorForADocumentType(): void
	{
		$required = $this->makeObject()->getDefinition()->getRequiredFields();

		$this->assertCount(1, $required);
		$this->assertSame('attachment_id', $required[0]->getName());
		$this->assertTrue($required[0]->isRequired());
	}

	/**
	 * The two Sacem codes are fixed values the admin can still override, so they are carried as
	 * defaults rather than hardcoded in the payload.
	 *
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::buildDefinition
	 * @return void
	 */
	public function testDefinitionPreFillsTheSacemClassificationCodes(): void
	{
		$defaults = [];

		foreach ($this->makeObject()->getDefinition()->getAvailableFields() as $field)
		{
			$defaults[$field->getName()] = $field->getDefaultValue();
		}

		$this->assertSame('CONV', $defaults['typeDoc']);
		$this->assertSame('GPD', $defaults['typePorteur']);
		$this->assertNull($defaults['refDemande']);
	}

	// -------------------------------------------------------------------------
	// validate()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::validate
	 * @return void
	 */
	public function testValidateRejectsAConnectorWithoutADocumentType(): void
	{
		$this->expectException(\DomainException::class);

		$this->makeObject()->validate($this->makeMapping([]));
	}

	/**
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::validate
	 * @return void
	 */
	public function testValidateRejectsAMappingLeavingARequiredAttributeUnmapped(): void
	{
		$incomplete = array_values(array_diff(self::TARGET_FIELDS, ['refDemande']));

		$this->expectException(\DomainException::class);

		$this->makeObject()->validate($this->makeMapping(['attachment_id' => self::ATTACHMENT_ID], $incomplete));
	}

	/**
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::validate
	 * @return void
	 */
	public function testValidateAcceptsAFullyConfiguredConnector(): void
	{
		$this->makeObject()->validate($this->makeMapping());

		$this->assertTrue(true, 'A fully configured connector validates without throwing.');
	}

	// -------------------------------------------------------------------------
	// execute()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::execute
	 * @return void
	 */
	public function testExecuteDepositsTheDocumentsOfTheChosenTypeHeldByTheFile(): void
	{
		$this->uploadRepository->expects($this->once())
			->method('getBy')
			->with(['fnum' => self::FNUM, 'attachment_id' => self::ATTACHMENT_ID])
			->willReturn([$this->makeUpload(101)]);

		$signedHeaders = ['Content-Type' => 'application/pdf', 'x-amz-meta-typedoc' => 'CONV'];

		$transport = $this->createMock(SacemSynchronizer::class);
		$transport->expects($this->once())
			->method('presignDocument')
			->with($this->callback(static function (array $payload) {
				// The API contract: annee and etat numeric, dtdoc as YYYYMMDD, every field present.
				return $payload['annee'] === 2026
					&& $payload['etat'] === 1
					&& $payload['dtdoc'] === '20260304'
					&& $payload['codeProg'] === 'PROG1'
					&& $payload['refDemande'] === self::FNUM
					&& $payload['nomBenef'] === 'Camille DURAND'
					&& $payload['numPers'] === '4242'
					&& $payload['typeDoc'] === 'CONV'
					&& $payload['typePorteur'] === 'GPD'
					&& $payload['contentType'] === 'application/pdf'
					&& $payload['filename'] === 'convention-101.pdf'
					&& $payload['title'] === 'Convention signée';
			}))
			->willReturn(['url' => 'https://s3.test/upload/101', 'key' => 'fondo/emundus/uuid-101.pdf', 'headers' => $signedHeaders]);
		// The signed headers travel verbatim, or the upload is refused with a 403.
		$transport->expects($this->once())
			->method('putDocument')
			->with('https://s3.test/upload/101', $this->anything(), $signedHeaders);

		$this->externalReferenceRepository->method('get')->willReturn([]);
		$this->externalReferenceRepository->expects($this->once())
			->method('flush')
			->with($this->callback(static function (ExternalReferenceEntity $reference) {
				return $reference->getColumn() === 'jos_emundus_uploads.id'
					&& $reference->getInternId() === '101'
					&& $reference->getReference() === 'fondo/emundus/uuid-101.pdf'
					&& $reference->getSynchronizerId() === self::SYNCHRONIZER_ID;
			}))
			->willReturn(true);

		$object = $this->makeObject();

		$this->assertTrue($object->execute($this->makeMapping(), $this->makeContext(), $transport));

		$this->assertSame(
			[Text::sprintf('COM_EMUNDUS_SACEM_GED_DEPOSITED', 'convention-101.pdf', 'fondo/emundus/uuid-101.pdf')],
			$this->messageTexts($object->getExecutionMessages(), ActionMessageTypeEnum::INFO)
		);
	}

	/**
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::execute
	 * @return void
	 */
	public function testExecuteRefusesToRunWithoutAnApplicationFileInContext(): void
	{
		$this->uploadRepository->expects($this->never())->method('getBy');

		$transport = $this->createMock(SacemSynchronizer::class);
		$transport->expects($this->never())->method('presignDocument');

		$this->expectException(\DomainException::class);

		$this->makeObject()->execute($this->makeMapping(), $this->makeContext(''), $transport);
	}

	/**
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::execute
	 * @return void
	 */
	public function testExecuteReportsAFileHoldingNoDocumentOfThatType(): void
	{
		$this->uploadRepository->method('getBy')->willReturn([]);

		$transport = $this->createMock(SacemSynchronizer::class);
		$transport->expects($this->never())->method('presignDocument');

		$this->expectException(\DomainException::class);

		$this->makeObject()->execute($this->makeMapping(), $this->makeContext(), $transport);
	}

	/**
	 * A document already carrying a GED reference for this connector must not reach the API a second
	 * time — this is what makes a re-run, or a duplicated event, harmless.
	 *
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::execute
	 * @return void
	 */
	public function testExecuteSkipsADocumentAlreadyDeposited(): void
	{
		$this->uploadRepository->method('getBy')->willReturn([$this->makeUpload(102)]);

		$transport = $this->createMock(SacemSynchronizer::class);
		$transport->expects($this->never())->method('presignDocument');
		$transport->expects($this->never())->method('putDocument');

		$stored = new ExternalReferenceEntity(1, 'jos_emundus_uploads.id', '102', 'fondo/emundus/uuid-102.pdf', self::SYNCHRONIZER_ID, 'documents', 'key');
		$this->externalReferenceRepository->method('get')->willReturn([$stored]);
		$this->externalReferenceRepository->expects($this->never())->method('flush');

		$object = $this->makeObject();

		$this->assertTrue($object->execute($this->makeMapping(), $this->makeContext(), $transport));

		$this->assertSame(
			[Text::sprintf('COM_EMUNDUS_SACEM_GED_ALREADY_DEPOSITED', 'convention-102.pdf')],
			$this->messageTexts($object->getExecutionMessages(), ActionMessageTypeEnum::INFO)
		);
	}

	/**
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::execute
	 * @return void
	 */
	public function testExecuteRefusesANonPdfDocumentWithoutCallingTheApi(): void
	{
		$this->uploadRepository->method('getBy')->willReturn([$this->makeUpload(103, 'docx')]);
		$this->externalReferenceRepository->method('get')->willReturn([]);

		$transport = $this->createMock(SacemSynchronizer::class);
		$transport->expects($this->never())->method('presignDocument');

		$this->expectException(\RuntimeException::class);

		$this->makeObject()->execute($this->makeMapping(), $this->makeContext(), $transport);
	}

	/**
	 * The GED indexes on the business attributes, so an empty one is refused before any deposit
	 * rather than producing an unfindable document.
	 *
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::execute
	 * @return void
	 */
	public function testExecuteRefusesToDepositWithAnEmptyBusinessAttribute(): void
	{
		$this->uploadRepository->method('getBy')->willReturn([$this->makeUpload(104)]);

		$transport = $this->createMock(SacemSynchronizer::class);
		$transport->expects($this->never())->method('presignDocument');

		$this->expectException(\DomainException::class);

		$this->makeObject($this->makeMetadata(['numPers' => '']))
			->execute($this->makeMapping(), $this->makeContext(), $transport);
	}

	/**
	 * One unsupported document must not hold back the others: the valid one is still deposited, and
	 * the failure is reported once at the end.
	 *
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::execute
	 * @return void
	 */
	public function testExecuteDepositsTheValidDocumentsEvenWhenOneFails(): void
	{
		$this->uploadRepository->method('getBy')->willReturn([$this->makeUpload(105, 'png'), $this->makeUpload(106)]);

		$transport = $this->createMock(SacemSynchronizer::class);
		$transport->expects($this->once())
			->method('presignDocument')
			->willReturn(['url' => 'https://s3.test/upload/106', 'key' => 'fondo/emundus/uuid-106.pdf', 'headers' => []]);
		$transport->expects($this->once())->method('putDocument');

		$this->externalReferenceRepository->method('get')->willReturn([]);
		$this->externalReferenceRepository->expects($this->once())->method('flush')->willReturn(true);

		$object = $this->makeObject();
		$thrown = null;

		try
		{
			$object->execute($this->makeMapping(), $this->makeContext(), $transport);
		}
		catch (\RuntimeException $e)
		{
			$thrown = $e;
		}

		$this->assertNotNull($thrown, 'A failed document makes the run fail.');

		// One line per document, so the task history says which one was refused and why, and that the
		// other one did go through.
		$this->assertSame(
			[Text::sprintf('COM_EMUNDUS_SACEM_GED_UNSUPPORTED_FORMAT', 'convention-105.png')],
			$this->messageTexts($object->getExecutionMessages(), ActionMessageTypeEnum::ERROR)
		);
		$this->assertSame(
			[Text::sprintf('COM_EMUNDUS_SACEM_GED_DEPOSITED', 'convention-106.pdf', 'fondo/emundus/uuid-106.pdf')],
			$this->messageTexts($object->getExecutionMessages(), ActionMessageTypeEnum::INFO)
		);
	}

	/**
	 * The exception summarises; it must not repeat the per-document detail, which already travels as
	 * execution messages.
	 *
	 * @covers \Tchooz\Synchronizers\GED\Sacem\Objects\DocumentObject::execute
	 * @return void
	 */
	public function testExecuteReportsTheRefusalReasonAsAnErrorMessage(): void
	{
		$this->uploadRepository->method('getBy')->willReturn([$this->makeUpload(107, 'docx')]);
		$this->externalReferenceRepository->method('get')->willReturn([]);

		$transport = $this->createMock(SacemSynchronizer::class);
		$object    = $this->makeObject();

		try
		{
			$object->execute($this->makeMapping(), $this->makeContext(), $transport);
			$this->fail('An unsupported document must make the run fail.');
		}
		catch (\RuntimeException $e)
		{
			$this->assertSame(Text::sprintf('COM_EMUNDUS_SACEM_GED_DEPOSIT_PARTIALLY_FAILED', 1, 1), $e->getMessage());
		}

		$this->assertSame(
			[Text::sprintf('COM_EMUNDUS_SACEM_GED_UNSUPPORTED_FORMAT', 'convention-107.docx')],
			$this->messageTexts($object->getExecutionMessages(), ActionMessageTypeEnum::ERROR)
		);
	}
}
