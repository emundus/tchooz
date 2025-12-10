<?php
/**
 * @package     Tchooz\Entities
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities;

class ExternalReferenceEntity
{
	private int $id;

	private string $column;

	private string|int $intern_id;

	private string|int $reference;

	public function __construct(string $column, string|int $intern_id, string|int $reference, int $id = 0)
	{
		$this->id        = $id;
		$this->column    = $column;
		$this->intern_id = $intern_id;
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

	public function setColumn(string $column): void
	{
		$this->column = $column;
	}

	public function getInternId(): int|string
	{
		return $this->intern_id;
	}

	public function setInternId(int|string $intern_id): void
	{
		$this->intern_id = $intern_id;
	}

	public function getReference(): int|string
	{
		return $this->reference;
	}

	public function setReference(int|string $reference): void
	{
		$this->reference = $reference;
	}
}