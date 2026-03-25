<?php
/**
 * @package     Tchooz\Repositories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories;

use Tchooz\Enums\JoinTypeEnum;

class Join
{
	private JoinTypeEnum $type;

	private string $fromTable;

	private string $fromAlias;

	private string $toTable;

	private string $toAlias;

	private string $fromKey;

	private string $toKey;

	// TODO: Allow conditions

	/**
	 * @param   JoinTypeEnum  $type
	 * @param   string        $fromTable
	 * @param   string        $fromAlias
	 * @param   string        $toTable
	 * @param   string        $toAlias
	 * @param   string        $fromKey
	 * @param   string        $toKey
	 */
	public function __construct(string $fromTable, string $fromAlias, string $toTable, string $toAlias, string $fromKey, string $toKey, JoinTypeEnum $type = JoinTypeEnum::LEFT)
	{
		$this->type      = $type;
		$this->fromTable = $fromTable;
		$this->fromAlias = $fromAlias;
		$this->toTable   = $toTable;
		$this->toAlias   = $toAlias;
		$this->fromKey   = $fromKey;
		$this->toKey     = $toKey;
	}

	public function getType(): JoinTypeEnum
	{
		return $this->type;
	}

	public function setType(JoinTypeEnum $type): Join
	{
		$this->type = $type;

		return $this;
	}

	public function getFromTable(): string
	{
		return $this->fromTable;
	}

	public function setFromTable(string $fromTable): Join
	{
		$this->fromTable = $fromTable;

		return $this;
	}

	public function getFromAlias(): string
	{
		return $this->fromAlias;
	}

	public function setFromAlias(string $fromAlias): Join
	{
		$this->fromAlias = $fromAlias;

		return $this;
	}

	public function getToTable(): string
	{
		return $this->toTable;
	}

	public function setToTable(string $toTable): Join
	{
		$this->toTable = $toTable;

		return $this;
	}

	public function getToAlias(): string
	{
		return $this->toAlias;
	}

	public function setToAlias(string $toAlias): Join
	{
		$this->toAlias = $toAlias;

		return $this;
	}

	public function getFromKey(): string
	{
		return $this->fromKey;
	}

	public function setFromKey(string $fromKey): Join
	{
		$this->fromKey = $fromKey;

		return $this;
	}

	public function getToKey(): string
	{
		return $this->toKey;
	}

	public function setToKey(string $toKey): Join
	{
		$this->toKey = $toKey;

		return $this;
	}
}