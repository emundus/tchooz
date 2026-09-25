<?php

namespace Tchooz\Synchronizers\GED\Sacem\Objects;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionExecutionMessage;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\MappingResolution;
use Tchooz\Entities\Mapping\SynchronizerMappingObjectDefinition;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Enums\Automation\ActionMessageTypeEnum;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Repositories\Upload\UploadRepository;
use Tchooz\Services\Field\FieldOptionProvider;
use Tchooz\Services\Mapping\MappingService;
use Tchooz\Synchronizers\GED\Sacem\SacemSynchronizer;
use Tchooz\Synchronizers\Mapping\AbstractMappingObject;
use Tchooz\Synchronizers\Mapping\ReportsExecutionMessages;
use Tchooz\Synchronizers\Mapping\SelfExecutingMappingObject;
use Tchooz\Synchronizers\MappingTransportInterface;

/**
 * Sacem GED document: deposits an application file's document into the GED with the business
 * metadata carried by the mapping.
 *
 * The deposit is a two-call choreography (presign, then a byte upload to the returned target), so
 * the object drives its own execution instead of the generic create/update flow.
 *
 * The connector says which document type it deposits (one connector per type, e.g. the signed
 * agreements); the documents themselves are then read from the application file carried by the
 * execution context.
 *
 * Every attribute the GED receives is declared as an available field, which makes the mapping the
 * single description of the payload: the admin sources the six business attributes, and the two
 * Sacem codes (typeDoc, typePorteur) come pre-filled with their fixed value and stay overridable.
 * Only what the API cannot know from the mapping — the media type and the identity of the file
 * itself — is added here from the upload.
 */
class DocumentObject extends AbstractMappingObject implements SelfExecutingMappingObject, ReportsExecutionMessages
{
	private const CHANNEL = 'com_emundus.ged_sacem';

	/**
	 * Connector parameter naming the document type to deposit.
	 */
	private const ATTACHMENT_PARAM = 'attachment_id';

	// The API accepts nothing else, so the media type is a constant rather than a guess on the file.
	private const CONTENT_TYPE = 'application/pdf';

	private const EXTENSION = 'pdf';

	private ExternalReferenceRepository $externalReferenceRepository;

	private UploadRepository $uploadRepository;

	/**
	 * @var array<ActionExecutionMessage>
	 */
	private array $executionMessages = [];

	public function __construct(?ExternalReferenceRepository $externalReferenceRepository = null, ?UploadRepository $uploadRepository = null)
	{
		$this->externalReferenceRepository = $externalReferenceRepository ?? new ExternalReferenceRepository();
		$this->uploadRepository            = $uploadRepository ?? new UploadRepository();

		Log::addLogger(['text_file' => 'com_emundus.ged_sacem.php'], Log::ALL, [self::CHANNEL]);
	}

	public function getName(): string
	{
		return 'document';
	}

	protected function buildDefinition(): SynchronizerMappingObjectDefinition
	{
		$attachmentType = (new ChoiceField(self::ATTACHMENT_PARAM, Text::_('COM_EMUNDUS_SACEM_GED_PARAM_ATTACHMENT'), [], true, false))
			->setOptionsProvider(new FieldOptionProvider('form', 'getAttachments', [], new AttachmentTypeRepository(), 'get', [['published' => 1], 0], 'getName'))
			->provideOptions()
			->setOptionsProvider(null); // Cleared once used, so the definition carries no live repository.

		return new SynchronizerMappingObjectDefinition(
			$this->getName(),
			'COM_EMUNDUS_SACEM_GED_DOCUMENT_OBJECT_LABEL',
			SacemSynchronizer::DOCUMENTS_API_PATH,
			new ExternalReferenceEntity(0, 'jos_emundus_uploads.id', '', '', null, 'documents', 'key'),
			[ApiMethodEnum::POST],
			[$attachmentType],
			[], // metadata
			[], // associations
			[
				// The six attributes the GED requires of every deposited document. annee and etat are
				// numeric in the API contract; the field type is what casts them (see buildMetadata).
				new NumericField('annee', Text::_('COM_EMUNDUS_SACEM_GED_FIELD_ANNEE'), true),
				new StringField('codeProg', Text::_('COM_EMUNDUS_SACEM_GED_FIELD_CODE_PROG'), true),
				new NumericField('etat', Text::_('COM_EMUNDUS_SACEM_GED_FIELD_ETAT'), true),
				new StringField('nomBenef', Text::_('COM_EMUNDUS_SACEM_GED_FIELD_NOM_BENEF'), true),
				new StringField('numPers', Text::_('COM_EMUNDUS_SACEM_GED_FIELD_NUM_PERS'), true),
				new StringField('refDemande', Text::_('COM_EMUNDUS_SACEM_GED_FIELD_REF_DEMANDE'), true),

				// Sacem classification codes: fixed values, configured here rather than hardcoded.
				(new StringField('typeDoc', Text::_('COM_EMUNDUS_SACEM_GED_FIELD_TYPE_DOC'), true))->setDefaultValue('CONV'),
				(new StringField('typePorteur', Text::_('COM_EMUNDUS_SACEM_GED_FIELD_TYPE_PORTEUR'), true))->setDefaultValue('GPD'),
			]
		);
	}

	/**
	 * The connector must name the document type it deposits, and every required attribute must be
	 * mapped. The parent only covers the first half, on a message that does not say which object
	 * refuses — hence the override.
	 *
	 * @throws \DomainException
	 */
	public function validate(MappingEntity $mapping): void
	{
		$params = $mapping->getParams() ?? [];

		if (empty($params[self::ATTACHMENT_PARAM]))
		{
			throw new \DomainException(Text::_('COM_EMUNDUS_SACEM_GED_PARAM_ATTACHMENT_MISSING'));
		}

		$mappedTargets = [];

		foreach ($mapping->getRows() as $row)
		{
			if ($row->getSourceField() !== '')
			{
				$mappedTargets[] = $row->getTargetField();
			}
		}

		foreach ($this->getDefinition()->getAvailableFields() as $field)
		{
			if ($field->isRequired() && !in_array($field->getName(), $mappedTargets, true))
			{
				throw new \DomainException(Text::sprintf('COM_EMUNDUS_SACEM_GED_FIELD_REQUIRED', $field->getLabel()));
			}
		}
	}

	/**
	 * Deposit every document of the configured type held by the application file in context. A
	 * document that cannot be deposited never stops the others: the failures are collected and
	 * reported once at the end, so a single unsupported file does not hold back the rest.
	 *
	 * @throws \Exception
	 */
	public function execute(MappingEntity $mapping, ActionTargetEntity $context, MappingTransportInterface $transport): bool
	{
		if (!($transport instanceof SacemSynchronizer))
		{
			throw new \DomainException(Text::_('COM_EMUNDUS_SACEM_GED_TRANSPORT_UNSUPPORTED'));
		}

		$this->validate($mapping);

		$this->executionMessages = [];

		$uploads  = $this->findDocuments($mapping, $context);
		$metadata = $this->buildMetadata($this->resolveMappedData($mapping, $context));

		$failures = 0;

		foreach ($uploads as $upload)
		{
			try
			{
				$this->deposit($upload, $metadata, $mapping, $transport);
			}
			catch (\Exception $e)
			{
				Log::add('Sacem GED deposit failed for upload ' . $upload->getId() . ' : ' . $e->getMessage(), Log::ERROR, self::CHANNEL);

				$this->report($e->getMessage(), ActionMessageTypeEnum::ERROR);
				$failures++;
			}
		}

		// The per-document detail travels as execution messages; the exception only has to say that
		// the run failed and how widely, so the task history never shows the same text twice.
		if ($failures > 0)
		{
			throw new \RuntimeException(Text::sprintf('COM_EMUNDUS_SACEM_GED_DEPOSIT_PARTIALLY_FAILED', $failures, count($uploads)));
		}

		return true;
	}

	/**
	 * @return array<ActionExecutionMessage>
	 */
	public function getExecutionMessages(): array
	{
		return $this->executionMessages;
	}

	private function report(string $message, ActionMessageTypeEnum $type = ActionMessageTypeEnum::INFO): void
	{
		$this->executionMessages[] = new ActionExecutionMessage($message, $type);
	}

	/**
	 * Convert the mapping rows into values. Isolated so a test can drive the choreography without a
	 * database behind the source resolvers.
	 */
	protected function resolveMappedData(MappingEntity $mapping, ActionTargetEntity $context): array
	{
		return MappingService::getJsonFromMapping($mapping, $context);
	}

	/**
	 * Deposit one document, unless it already reached the GED.
	 *
	 * @throws \Exception
	 */
	private function deposit(UploadEntity $upload, array $metadata, MappingEntity $mapping, SacemSynchronizer $transport): void
	{
		if (strtolower($upload->getExtension()) !== self::EXTENSION)
		{
			throw new \DomainException(Text::sprintf('COM_EMUNDUS_SACEM_GED_UNSUPPORTED_FORMAT', $upload->getFilename()));
		}

		$filePath = $upload->getFileInternalPath();

		if (!file_exists($filePath))
		{
			throw new \RuntimeException(Text::sprintf('COM_EMUNDUS_SACEM_GED_FILE_NOT_FOUND', $upload->getFilename()));
		}

		if ($this->isAlreadyDeposited($upload, $mapping->getSynchronizerId()))
		{
			Log::add('Upload ' . $upload->getId() . ' already deposited in the Sacem GED, skipping.', Log::INFO, self::CHANNEL);

			$this->report(Text::sprintf('COM_EMUNDUS_SACEM_GED_ALREADY_DEPOSITED', $upload->getFilename()));

			return;
		}

		$presign = $transport->presignDocument($this->describeDocument($upload, $metadata));

		if (empty($presign['url']))
		{
			throw new \RuntimeException(Text::sprintf('COM_EMUNDUS_SACEM_GED_PRESIGN_FAILED', $upload->getFilename()));
		}

		// The presign response dictates the upload headers: they are signed, so they travel untouched.
		// A refusal throws with the target's own reason.
		$transport->putDocument($presign['url'], $filePath, $presign['headers']);

		if (!empty($presign['key']))
		{
			$this->trackDocumentReference($upload, (string) $presign['key'], $mapping->getSynchronizerId());
		}

		$this->report(Text::sprintf('COM_EMUNDUS_SACEM_GED_DEPOSITED', $upload->getFilename(), (string) ($presign['key'] ?? '')));
	}

	/**
	 * A document already carrying a GED reference for this connector has been deposited: the stored
	 * reference is what makes a re-run — or a duplicated event — idempotent.
	 */
	private function isAlreadyDeposited(UploadEntity $upload, int $synchronizerId): bool
	{
		$reference = $this->getDefinition()->getExternalReference();

		$found = $this->externalReferenceRepository->get([
			'column'    => $reference->getColumn(),
			'intern_id' => (string) $upload->getId(),
			'sync_id'   => $synchronizerId,
		], 1);

		return !empty($found);
	}

	/**
	 * The documents to deposit are those of the configured type on the application file being
	 * processed — the connector says which type, the context says which file.
	 *
	 * @return array<UploadEntity>
	 *
	 * @throws \DomainException when there is no file in context, or no document of that type on it.
	 */
	private function findDocuments(MappingEntity $mapping, ActionTargetEntity $context): array
	{
		$fnum = (string) $context->getFile();

		if ($fnum === '')
		{
			throw new \DomainException(Text::_('COM_EMUNDUS_SACEM_GED_NO_FILE'));
		}

		$attachmentId = (int) ($mapping->getParams()[self::ATTACHMENT_PARAM] ?? 0);

		$uploads = $this->uploadRepository->getBy([
			'fnum'          => $fnum,
			'attachment_id' => $attachmentId,
		]);

		if (empty($uploads))
		{
			throw new \DomainException(Text::_('COM_EMUNDUS_SACEM_GED_NO_DOCUMENT'));
		}

		return $uploads;
	}

	/**
	 * Collect the mapped business attributes, refusing to deposit with an incomplete identity: the
	 * GED indexes on them, so an empty one silently produces an unfindable document.
	 *
	 * @throws \DomainException
	 */
	private function buildMetadata(array $data): array
	{
		$metadata = [];

		foreach ($this->getDefinition()->getAvailableFields() as $field)
		{
			$name  = $field->getName();
			$value = trim((string) ($data[$name] ?? ''));

			if ($value === '')
			{
				throw new \DomainException(Text::sprintf('COM_EMUNDUS_SACEM_GED_MISSING_REQUIRED_VALUE', $field->getLabel()));
			}

			$metadata[$name] = $field instanceof NumericField ? (int) $value : $value;
		}

		return $metadata;
	}

	/**
	 * Complete the mapped attributes with what only the file itself carries. dtdoc is the YYYYMMDD
	 * the API expects, not an ISO date.
	 */
	private function describeDocument(UploadEntity $upload, array $metadata): array
	{
		return array_merge($metadata, [
			'contentType' => self::CONTENT_TYPE,
			'dtdoc'       => $upload->getTimedate()->format('Ymd'),
			'filename'    => $upload->getFilename(),
			'title'       => !empty($upload->getDescription()) ? $upload->getDescription() : $upload->getFilename(),
		]);
	}

	private function trackDocumentReference(UploadEntity $upload, string $documentId, int $synchronizerId): void
	{
		$definition = $this->getDefinition()->getExternalReference();

		$reference = new ExternalReferenceEntity(
			0,
			$definition->getColumn(),
			(string) $upload->getId(),
			$documentId,
			$synchronizerId,
			$definition->getReferenceObject(),
			$definition->getReferenceAttribute()
		);

		if (!$this->externalReferenceRepository->flush($reference))
		{
			Log::add('Error saving external reference for uploaded file with ID : ' . $upload->getId() . ' Sacem GED Id : ' . $documentId, Log::ERROR, self::CHANNEL);
		}
	}

	// -------------------------------------------------------------------------
	// Not applicable — the object is self-executing (see SelfExecutingMappingObject).
	// -------------------------------------------------------------------------

	public function resolveExistence(MappingEntity $mapping, ActionTargetEntity $context, MappingTransportInterface $transport): MappingResolution
	{
		throw new \LogicException('The Sacem GED document is self-executing; resolveExistence() is not used.');
	}

	public function buildPayload(array $mappedData, MappingEntity $mapping, ActionTargetEntity $context): array
	{
		throw new \LogicException('The Sacem GED document is self-executing; buildPayload() is not used.');
	}
}
