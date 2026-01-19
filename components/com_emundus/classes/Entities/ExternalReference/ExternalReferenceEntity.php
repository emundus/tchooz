<?php

namespace Tchooz\Entities\ExternalReference;

class ExternalReferenceEntity
{
	private int $id;

	private string $column; // e.g., 'jos_emundus_setup_campaigns.id'

	private string $internId; // e.g., Campaign entity ID

	private string $reference; // e.g., UUID from external system

	private ?int $synchronizerId = null; // e.g., HubSpot Synchronizer ID

	private ?string $referenceObject = null; // e.g., 'contacts', 'deals', etc.

	private ?string $referenceAttribute = null; // e.g., 'hs_object_id', 'dealId', etc.

	public function __construct(int $id, string $column, string $internId, string $reference, ?int $synchronizerId = null, ?string $referenceObject = null, ?string $referenceAttribute = null)
	{
		$this->id = $id;
		$this->column = $column;
		$this->internId = $internId;
		$this->reference = $reference;
		$this->synchronizerId = $synchronizerId;
		$this->referenceObject = $referenceObject;
		$this->referenceAttribute = $referenceAttribute;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getColumn(): string
	{
		return $this->column;
	}

	public function getInternId(): string
	{
		return $this->internId;
	}

	public function getReference(): string
	{
		return $this->reference;
	}

	public function getSynchronizerId(): ?int
	{
		return $this->synchronizerId;
	}

	public function getReferenceObject(): ?string
	{
		return $this->referenceObject;
	}

	public function getReferenceAttribute(): ?string
	{
		return $this->referenceAttribute;
	}
}