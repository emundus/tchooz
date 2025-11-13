<?php
/**
 * @package     Tchooz\Entities\Actions
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Actions;

class CrudEntity
{
	private int $multi;

	private int $create;

	private int $read;

	private int $update;

	private int $delete;

	public function __construct(int $multi, int $create, int $read, int $update, int $delete) {
		$this->multi  = $multi;
		$this->create = $create;
		$this->read   = $read;
		$this->update = $update;
		$this->delete = $delete;
	}

	public function getMulti(): int
	{
		return $this->multi;
	}

	public function setMulti(int $multi): void
	{
		$this->multi = $multi;
	}

	public function getCreate(): int
	{
		return $this->create;
	}

	public function setCreate(int $create): void
	{
		$this->create = $create;
	}

	public function getRead(): int
	{
		return $this->read;
	}

	public function setRead(int $read): void
	{
		$this->read = $read;
	}

	public function getUpdate(): int
	{
		return $this->update;
	}

	public function setUpdate(int $update): void
	{
		$this->update = $update;
	}

	public function getDelete(): int
	{
		return $this->delete;
	}

	public function setDelete(int $delete): void
	{
		$this->delete = $delete;
	}
}