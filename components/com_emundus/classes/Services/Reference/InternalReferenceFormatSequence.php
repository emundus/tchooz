<?php
/**
 * @package     Tchooz\Services\Reference
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Reference;

use Tchooz\Enums\Reference\PositionEnum;
use Tchooz\Enums\Reference\ResetTypeEnum;

class InternalReferenceFormatSequence
{
	private PositionEnum $position;

	private ResetTypeEnum $resetType;

	private int $length;

	/**
	 * @param   PositionEnum   $position
	 * @param   ResetTypeEnum  $resetType
	 * @param   int            $length
	 */
	public function __construct(PositionEnum $position, ResetTypeEnum $resetType, int $length)
	{
		$this->position  = $position;
		$this->resetType = $resetType;
		$this->length    = $length;
	}

	public function getPosition(): PositionEnum
	{
		return $this->position;
	}

	public function setPosition(PositionEnum $position): InternalReferenceFormatSequence
	{
		$this->position = $position;

		return $this;
	}

	public function getResetType(): ResetTypeEnum
	{
		return $this->resetType;
	}

	public function setResetType(ResetTypeEnum $resetType): InternalReferenceFormatSequence
	{
		$this->resetType = $resetType;

		return $this;
	}

	public function getLength(): int
	{
		return $this->length;
	}

	public function setLength(int $length): InternalReferenceFormatSequence
	{
		$this->length = $length;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'position'  => $this->position->value,
			'reset_type' => $this->resetType->value,
			'length'    => $this->length,
		];
	}
}