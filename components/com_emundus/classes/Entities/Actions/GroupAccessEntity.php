<?php
/**
 * @package     Tchooz\Entities\Actions
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Actions;

use Tchooz\Entities\Groups\GroupEntity;

class GroupAccessEntity
{
	private int $id;

	private ?GroupEntity $group;

	private ?ActionEntity $action;

	private CrudEntity $crud;

	/**
	 * @param   int            $id
	 * @param   ?GroupEntity   $group
	 * @param   ?ActionEntity  $action
	 * @param   CrudEntity     $crud
	 */
	public function __construct(int $id, ?GroupEntity $group, ?ActionEntity $action, CrudEntity $crud)
	{
		$this->id     = $id;
		$this->group  = $group;
		$this->action = $action;
		$this->crud   = $crud;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): GroupAccessEntity
	{
		$this->id = $id;

		return $this;
	}

	public function getGroup(): ?GroupEntity
	{
		return $this->group;
	}

	public function setGroup(?GroupEntity $group): GroupAccessEntity
	{
		$this->group = $group;

		return $this;
	}

	public function getAction(): ?ActionEntity
	{
		return $this->action;
	}

	public function setAction(?ActionEntity $action): GroupAccessEntity
	{
		$this->action = $action;

		return $this;
	}

	public function getCrud(): CrudEntity
	{
		return $this->crud;
	}

	public function setCrud(CrudEntity $crud): GroupAccessEntity
	{
		$this->crud = $crud;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'id'     => $this->id,
			'group'  => $this->group?->__serialize(),
			'action' => $this->action?->__serialize(),
			'crud'   => $this->crud->__serialize(),
		];
	}
}