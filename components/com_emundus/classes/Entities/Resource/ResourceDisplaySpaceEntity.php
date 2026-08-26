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
use Tchooz\Enums\Resource\DisplaySpaceTypeEnum;

#[Table(name: '#__emundus_resource_display_spaces')]
class ResourceDisplaySpaceEntity implements \JsonSerializable
{
	private int $id;

	#[Column(type: Types::INTEGER)]
	private int $resourceId;

	#[Column(type: Types::STRING, length: 20)]
	private DisplaySpaceTypeEnum $type;

	#[Column(type: Types::INTEGER)]
	private ?int $targetId;

	/**
	 * Display label of the target (form/campaign/program/page), resolved by the factory — not persisted.
	 */
	private string $targetLabel = '';

	public function __construct(
		int $id = 0,
		int $resourceId = 0,
		DisplaySpaceTypeEnum $type = DisplaySpaceTypeEnum::FORM,
		?int $targetId = null
	) {
		$this->id         = $id;
		$this->resourceId = $resourceId;
		$this->type       = $type;
		$this->targetId   = $targetId;
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

	public function getType(): DisplaySpaceTypeEnum
	{
		return $this->type;
	}

	public function setType(DisplaySpaceTypeEnum $type): void
	{
		$this->type = $type;
	}

	public function getTargetId(): ?int
	{
		return $this->targetId;
	}

	public function setTargetId(?int $targetId): void
	{
		$this->targetId = $targetId;
	}

	public function getTargetLabel(): string
	{
		return $this->targetLabel;
	}

	public function setTargetLabel(string $targetLabel): void
	{
		$this->targetLabel = $targetLabel;
	}

	public function jsonSerialize(): array
	{
		return [
			'id'          => $this->id,
			'resourceId'  => $this->resourceId,
			'type'        => $this->type->value,
			'targetId'    => $this->targetId,
			'targetLabel' => $this->targetLabel,
		];
	}
}
