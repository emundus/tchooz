<?php
/**
 * @package     Tchooz\Entities\NumericSign
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\NumericSign;

use Tchooz\Enums\ApiStatus;

class YousignRequests
{
	private int $id = 0;

	private string $name = '';

	private Request $request;

	private string $procedureId = '';

	private string $documentId = '';

	private ?string $signature_field = null;

	private ApiStatus $apiStatus;

	private string $requestPayload = '';

	private string $responsePayload = '';

	private string $createdAt;

	private int $createdBy;

	private string $expirationDate = '';

	private int $retryCount = 0;

	private array $signers = [];

	public function __construct($createdBy)
	{
		$this->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
		$this->createdBy = $createdBy;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getRequest(): Request
	{
		return $this->request;
	}

	public function setRequest(Request $request): void
	{
		$this->request = $request;
	}

	public function getProcedureId(): string
	{
		return $this->procedureId;
	}

	public function setProcedureId(string $procedureId): void
	{
		$this->procedureId = $procedureId;
	}

	public function getDocumentId(): string
	{
		return $this->documentId;
	}

	public function setDocumentId(string $documentId): void
	{
		$this->documentId = $documentId;
	}

	public function getSignatureField(): ?string
	{
		return $this->signature_field;
	}

	public function setSignatureField(?string $signature_field): void
	{
		$this->signature_field = $signature_field;
	}

	public function getApiStatus(): ApiStatus
	{
		return $this->apiStatus;
	}

	public function setApiStatus(ApiStatus|string $apiStatus): self
	{
		if (is_string($apiStatus))
		{
			$apiStatus = ApiStatus::from($apiStatus);
		}
		elseif (!($apiStatus instanceof ApiStatus))
		{
			throw new \InvalidArgumentException('Invalid api status type');
		}

		$this->apiStatus = $apiStatus;

		return $this;
	}

	public function getRequestPayload(): string
	{
		return $this->requestPayload;
	}

	public function setRequestPayload(string $requestPayload): void
	{
		$this->requestPayload = $requestPayload;
	}

	public function getResponsePayload(): string
	{
		return $this->responsePayload;
	}

	public function setResponsePayload(string $responsePayload): void
	{
		$this->responsePayload = $responsePayload;
	}

	public function getCreatedAt(): string
	{
		return $this->createdAt;
	}

	public function setCreatedAt(string $createdAt): void
	{
		$this->createdAt = $createdAt;
	}

	public function getCreatedBy(): int
	{
		return $this->createdBy;
	}

	public function setCreatedBy(int $createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getExpirationDate(): string
	{
		return $this->expirationDate;
	}

	public function setExpirationDate(string $expirationDate): void
	{
		$this->expirationDate = $expirationDate;
	}

	public function getRetryCount(): int
	{
		return $this->retryCount;
	}

	public function setRetryCount(int $retryCount): void
	{
		$this->retryCount = $retryCount;
	}

	public function getSigners(): array
	{
		return $this->signers;
	}

	public function setSigners(array $signers): void
	{
		$this->signers = $signers;
	}

	public function addSigner(string $signerId, int $request_signer_id, ?string $signature_url = '', ?string $signature_field = ''): void
	{
		$this->signers[] = [
			'signer_id'         => $signerId,
			'request_signer_id' => $request_signer_id,
			'signature_url'     => $signature_url,
			'signature_field'   => $signature_field ?: $this->signature_field,
		];
	}

	public function __serialize(): array
	{
		return [
			'id'               => $this->id,
			'name'             => $this->name,
			'request_id'       => $this->getRequest()->getId(),
			'procedure_id'     => $this->procedureId,
			'document_id'      => $this->documentId,
			'signature_field'  => $this->signature_field,
			'status'           => $this->apiStatus->value,
			'request_payload'  => $this->requestPayload,
			'response_payload' => $this->responsePayload,
			'created_at'       => $this->createdAt,
			'created_by'       => $this->createdBy,
			'expiration_date'  => $this->expirationDate,
			'retry_count'      => $this->retryCount,
		];
	}
}