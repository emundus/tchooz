<?php
/**
 * @package     Tchooz\Entities\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Resource;

use Tchooz\Attributes\ORM\Column;
use Tchooz\Attributes\ORM\Table;
use Tchooz\Attributes\ORM\Types;
use Tchooz\Enums\Resource\ResourceAccessTypeEnum;
use Tchooz\Enums\Resource\ResourcePermissionEnum;

#[Table(name: '#__emundus_resource_access')]
class ResourceAccessEntity implements \JsonSerializable
{
	private int $id;

	#[Column(type: Types::INTEGER)]
	private int $resourceId;

	#[Column(type: Types::STRING, length: 20)]
	private ResourceAccessTypeEnum $type;

	#[Column(type: Types::INTEGER)]
	private int $targetId;

	#[Column(type: Types::STRING, length: 20)]
	private ResourcePermissionEnum $permission;

	/**
	 * Display label of the target (role/group/user), resolved by the factory — not persisted.
	 */
	private string $targetLabel = '';

	/**
	 * Email of the target when it is a user, resolved by the factory — not persisted.
	 */
	private string $targetEmail = '';

	public function __construct(
		int $id = 0,
		int $resourceId = 0,
		ResourceAccessTypeEnum $type = ResourceAccessTypeEnum::USER,
		int $targetId = 0,
		ResourcePermissionEnum $permission = ResourcePermissionEnum::VIEW
	) {
		$this->id         = $id;
		$this->resourceId = $resourceId;
		$this->type       = $type;
		$this->targetId   = $targetId;
		$this->permission = $permission;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getResourceId(): int
	{
		return $this->resourceId;
	}

	public function setResourceId(int $resourceId): void
	{
		$this->resourceId = $resourceId;
	}

	public function getType(): ResourceAccessTypeEnum
	{
		return $this->type;
	}

	public function setType(ResourceAccessTypeEnum $type): void
	{
		$this->type = $type;
	}

	public function getTargetId(): int
	{
		return $this->targetId;
	}

	public function setTargetId(int $targetId): void
	{
		$this->targetId = $targetId;
	}

	public function getPermission(): ResourcePermissionEnum
	{
		return $this->permission;
	}

	public function setPermission(ResourcePermissionEnum $permission): void
	{
		$this->permission = $permission;
	}

	public function getTargetLabel(): string
	{
		return $this->targetLabel;
	}

	public function setTargetLabel(string $targetLabel): void
	{
		$this->targetLabel = $targetLabel;
	}

	public function getTargetEmail(): string
	{
		return $this->targetEmail;
	}

	public function setTargetEmail(string $targetEmail): void
	{
		$this->targetEmail = $targetEmail;
	}

	public function jsonSerialize(): array
	{
		return [
			'id'          => $this->id,
			'resourceId'  => $this->resourceId,
			'type'        => $this->type->value,
			'targetId'    => $this->targetId,
			'permission'  => $this->permission->value,
			'targetLabel' => $this->targetLabel,
			'targetEmail' => $this->targetEmail,
		];
	}
}
