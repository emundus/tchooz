<?php
/**
 * @package     Tchooz\Services\Reference
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Reference;

use Tchooz\Entities\Mapping\MappingTransformEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;

class InternalReferenceFormatBlock
{
	private ConditionTargetTypeEnum $type;

	private mixed $value;

	/**
	 * @var array<MappingTransformEntity>
	 */
	private array $transformations;

	public function __construct(ConditionTargetTypeEnum $type, mixed $value, array $transformations = [])
	{
		$this->type  = $type;
		$this->value = $value;
		$this->transformations = $transformations;
	}

	public function getType(): ConditionTargetTypeEnum
	{
		return $this->type;
	}

	public function setType(ConditionTargetTypeEnum $type): void
	{
		$this->type = $type;
	}

	public function getValue(): mixed
	{
		return $this->value;
	}

	public function setValue(mixed $value): void
	{
		$this->value = $value;
	}

	public function getTransformations(): array
	{
		return $this->transformations;
	}

	public function setTransformations(array $transformations): void
	{
		$this->transformations = $transformations;
	}

	public function addTransformation(MappingTransformEntity $transformation): void
	{
		$this->transformations[] = $transformation;
	}

	public function __serialize(): array
	{
		return [
			'type' => $this->type,
			'value' => $this->value,
			'transformations' => array_map(function (MappingTransformEntity $transformation) {
				return $transformation->serialize();
			}, $this->transformations)
		];
	}
}