<?php

namespace Tchooz\Entities\Contacts;

use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;

class ContactFileAssociationEntity
{
	public function __construct(
		private int $id,
		private int $contact_id,
		private string $application_file_fnum,
		private ?ContactEntity $contact = null,
		private ?ApplicationFileEntity $application_file = null
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getContactId(): int
	{
		return $this->contact_id;
	}

	public function setContactId(int $contact_id): self
	{
		$this->contact_id = $contact_id;

		return $this;
	}

	public function getApplicationFileFnum(): string
	{
		return $this->application_file_fnum;
	}

	public function setApplicationFileFnum(string $application_file_fnum): self
	{
		$this->application_file_fnum = $application_file_fnum;

		return $this;
	}

	public function getContact(): ?ContactEntity
	{
		return $this->contact;
	}

	public function setContact(?ContactEntity $contact): self
	{
		$this->contact = $contact;

		return $this;
	}

	public function getApplicationFile(): ?ApplicationFileEntity
	{
		return $this->application_file;
	}

	public function setApplicationFile(?ApplicationFileEntity $application_file): self
	{
		$this->application_file = $application_file;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'id' => $this->id,
			'contact' => !empty($this->contact) ? $this->contact->__serialize() : ['id' => $this->contact_id],
			'application_file' => !empty($this->application_file) ? $this->application_file->__serialize() : ['fnum' => $this->application_file_fnum],
		];
	}
}