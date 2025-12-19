<?php
/**
 * @package     Tchooz\Entities\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\ApplicationFile;

class StatusEntity
{
	private int $id;

	private int $step;

	private string $label;

	private int $ordering;

	private string $color;

	public function __construct(int $id, int $step, string $label, int $ordering, string $color)
	{
		$this->id       = $id;
		$this->step     = $step;
		$this->label    = $label;
		$this->ordering = $ordering;
		$this->color    = $color;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getStep(): int
	{
		return $this->step;
	}

	public function setStep(int $step): void
	{
		$this->step = $step;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getOrdering(): int
	{
		return $this->ordering;
	}

	public function setOrdering(int $ordering): void
	{
		$this->ordering = $ordering;
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
		return [
			'id'       => $this->step,
			'label'    => $this->label,
			'ordering' => $this->ordering,
			'color'    => $this->color,
		];
	}
}