<?php
/**
 * @package     Tchooz\Entities\Programs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Programs;

class ProgramEntity
{
	private int $id;

	private string $code;

	private string $label;

	private ?string $notes;

	private bool $published;

	/**
	 * Deprecated: Need to use the category entity relation
	 */
	private ?string $programmes;

	private ?string $synthesis;

	private bool $applyOnline;

	private ?int $ordering;

	private ?string $logo;

	private ?string $color;

	public function __construct(string $code, string $label, int $id = 0, bool $published = true, ?string $notes = '', ?string $programmes = '', ?string $synthesis = '', bool $applyOnline = false, ?int $ordering = 0, ?string $logo = '', ?string $color = '')
	{
		$this->id = $id;
		$this->code = $code;
		$this->label = $label;
		$this->published = $published;
		$this->notes = $notes;
		$this->programmes = $programmes;
		$this->synthesis = $synthesis;
		$this->applyOnline = $applyOnline;
		$this->ordering = $ordering;
		$this->logo = $logo;
		$this->color = $color;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function setCode(string $code): void
	{
		$this->code = $code;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getNotes(): string
	{
		return $this->notes;
	}

	public function setNotes(string $notes): void
	{
		$this->notes = $notes;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): void
	{
		$this->published = $published;
	}

	public function getProgrammes(): string
	{
		return $this->programmes;
	}

	public function setProgrammes(string $programmes): void
	{
		$this->programmes = $programmes;
	}

	public function getSynthesis(): string
	{
		return $this->synthesis;
	}

	public function setSynthesis(string $synthesis): void
	{
		$this->synthesis = $synthesis;
	}

	public function isApplyOnline(): bool
	{
		return $this->applyOnline;
	}

	public function setApplyOnline(bool $applyOnline): void
	{
		$this->applyOnline = $applyOnline;
	}

	public function getOrdering(): int
	{
		return $this->ordering;
	}

	public function setOrdering(int $ordering): void
	{
		$this->ordering = $ordering;
	}

	public function getLogo(): string
	{
		return $this->logo;
	}

	public function setLogo(string $logo): void
	{
		$this->logo = $logo;
	}

	public function getColor(): string
	{
		return $this->color;
	}

	public function setColor(string $color): void
	{
		$this->color = $color;
	}

	public function __serialize(): array
	{
		return get_object_vars($this);
	}
}