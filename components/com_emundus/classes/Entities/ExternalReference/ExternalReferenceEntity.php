<?php

namespace Tchooz\Entities\ExternalReference;

class ExternalReferenceEntity
{
	private int $id;

	private string $column; // e.g., 'jos_emundus_setup_campaigns.id'

	private string $internId; // e.g., Campaign entity ID

	private string $reference; // e.g., UUID from external system

	public function __construct(int $id, string $column, string $internId, string $reference)
	{
		$this->id = $id;
		$this->column = $column;
		$this->internId = $internId;
		$this->reference = $reference;
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
}