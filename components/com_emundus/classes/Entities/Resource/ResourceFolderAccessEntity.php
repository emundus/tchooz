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

/**
 * Access grant on a whole folder: every file directly inside the folder (present or added
 * later) is shared with the target, mirroring the per-file ResourceAccessEntity.
 */
#[Table(name: '#__emundus_resource_folder_access')]
class ResourceFolderAccessEntity implements \JsonSerializable
{
	private int $id;

	#[Column(type: Types::INTEGER)]
	private int $folderId;

	#[Column(type: Types::STRING, length: 20)]
	private ResourceAccessTypeEnum $type;

	#[Column(type: Types::INTEGER)]
	private int $targetId;

	#[Column(type: Types::STRING, length: 20)]
	private ResourcePermissionEnum $permission;

	/**
	 * Display label of the target (role/group/user), resolved by the service — not persisted.
	 */
	private string $targetLabel = '';

	/**
	 * Email of the target when it is a user, resolved by the service — not persisted.
	 */
	private string $targetEmail = '';

	public function __construct(
		int $id = 0,
		int $folderId = 0,
		ResourceAccessTypeEnum $type = ResourceAccessTypeEnum::USER,
		int $targetId = 0,
		ResourcePermissionEnum $permission = ResourcePermissionEnum::VIEW
	) {
		$this->id         = $id;
		$this->folderId   = $folderId;
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

	public function getFolderId(): int
	{
		return $this->folderId;
	}

	public function setFolderId(int $folderId): void
	{
		$this->folderId = $folderId;
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
			'folderId'    => $this->folderId,
			'type'        => $this->type->value,
			'targetId'    => $this->targetId,
			'permission'  => $this->permission->value,
			'targetLabel' => $this->targetLabel,
			'targetEmail' => $this->targetEmail,
		];
	}
}
