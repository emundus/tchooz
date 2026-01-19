<?php
/**
 * @package     Tchooz\Entities\Label
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Label;

class LabelEntity
{
	private int $id;

	private string $label;

	private string $class;

	private int $ordering;

	public function __construct(string $label, string $class, int $ordering, int $id = 0)
	{
		$this->id       = $id;
		$this->label    = $label;
		$this->class    = $class;
		$this->ordering = $ordering;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getClass(): string
	{
		return $this->class;
	}

	public function setClass(string $class): void
	{
		$this->class = $class;
	}

	public function getOrdering(): int
	{
		return $this->ordering;
	}

	public function setOrdering(int $ordering): void
	{
		$this->ordering = $ordering;
	}

	public function __serialize(): array
	{
		return [
			'id'       => $this->id,
			'label'    => $this->label,
			'class'    => $this->class,
			'ordering' => $this->ordering,
		];
	}
}