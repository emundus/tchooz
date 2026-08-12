<?php

namespace Tchooz\Entities\Contacts;

use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;

class OrganizationFileAssociationEntity
{
	public function __construct(
		private int $id,
		private int $organization_id,
		private string $application_file_fnum,
		private ?OrganizationEntity $organization = null,
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

	public function getOrganizationId(): int
	{
		return $this->organization_id;
	}

	public function setOrganizationId(int $organization_id): self
	{
		$this->organization_id = $organization_id;

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

	public function getOrganization(): ?OrganizationEntity
	{
		return $this->organization;
	}

	public function setOrganization(?OrganizationEntity $organization): self
	{
		$this->organization = $organization;

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
			'organization' => !empty($this->organization) ? $this->organization->__serialize() : ['id' => $this->organization_id],
			'application_file' => !empty($this->application_file) ? $this->application_file->__serialize() : ['fnum' => $this->application_file_fnum],
		];
	}
}
