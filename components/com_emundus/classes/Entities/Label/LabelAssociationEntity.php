<?php

namespace Tchooz\Entities\Label;

use DateTimeImmutable;
use EmundusHelperDate;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;

class LabelAssociationEntity
{
	public function __construct(
		private int $id,
		private int $labelId,
		private string $fnum,
		private DateTimeImmutable $created,
		private ?User $user = null,
		private ?LabelEntity $label = null
	) {
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

	public function getLabelId(): int
	{
		return $this->labelId;
	}

	public function setLabelId(int $labelId): self
	{
		$this->labelId = $labelId;
		return $this;
	}

	public function getLabel(): ?LabelEntity
	{
		return $this->label;
	}

	public function setLabel(?LabelEntity $label): self
	{
		$this->label = $label;
		return $this;
	}

	public function getFnum(): string
	{
		return $this->fnum;
	}

	public function setFnum(string $fnum): self
	{
		$this->fnum = $fnum;
		return $this;
	}

	public function getCreated(): DateTimeImmutable
	{
		return $this->created;
	}

	public function setCreated(DateTimeImmutable $created): self
	{
		$this->created = $created;
		return $this;
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setUser(?User $user): self
	{
		$this->user = $user;
		return $this;
	}

	public function __serialize(): array
	{
		return [
			'id' => $this->id,
			'label' => $this->label?->__serialize() ?? ['id' => $this->labelId],
			'fnum' => $this->fnum,
			'created' => EmundusHelperDate::displayDate($this->created->format('Y-m-d H:i:s'), 'DATE_FORMAT_LC2', 0),
			'user_id' => $this->user?->id ?? 0,
			'user' => $this->user?->name ?? ''
		];
	}
}