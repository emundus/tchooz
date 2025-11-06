<?php
/**
 * @package     Tchooz\Entities\List
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\List;

class ListResult
{
	private array $items;

	private int $totalItems;

	public function __construct(array $items, int $totalItems)
	{
		$this->items = $items;
		$this->totalItems = $totalItems;
	}

	public function getItems(): array
	{
		return $this->items;
	}

	public function setItems(array $items): void
	{
		$this->items = $items;
	}

	public function getTotalItems(): int
	{
		return $this->totalItems;
	}

	public function setTotalItems(int $totalItems): void
	{
		$this->totalItems = $totalItems;
	}
}