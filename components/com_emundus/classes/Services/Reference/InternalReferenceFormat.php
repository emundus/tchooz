<?php
/**
 * @package     Tchooz\Services\Reference
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Reference;

use Tchooz\Entities\ApplicationFile\StatusEntity;
use Tchooz\Entities\Mapping\MappingTransformEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Reference\PositionEnum;
use Tchooz\Enums\Reference\ResetTypeEnum;
use Tchooz\Enums\Reference\SeparatorEnum;
use Tchooz\Factories\Mapping\MappingRowTransformationFactory;
use Tchooz\Repositories\ApplicationFile\StatusRepository;

class InternalReferenceFormat
{
	/**
	 * @var array<InternalReferenceFormatBlock>
	 */
	private array $blocks;

	private SeparatorEnum $separator;

	private ?StatusEntity $triggeringStatus;

	private ?InternalReferenceFormatSequence $sequence;

	private bool $showToApplicant;

	private bool $showInFiles;

	public function __construct(
		array                            $blocks,
		SeparatorEnum                    $separator = SeparatorEnum::DASH,
		?StatusEntity                    $triggeringStatus = null,
		bool                             $showToApplicant = false,
		bool                             $showInFiles = false,
		?InternalReferenceFormatSequence $sequence = null
	)
	{
		$this->blocks           = $blocks;
		$this->separator        = $separator;
		$this->triggeringStatus = $triggeringStatus;
		$this->showToApplicant  = $showToApplicant;
		$this->showInFiles      = $showInFiles;
		$this->sequence         = $sequence;
	}

	public function getBlocks(): array
	{
		return $this->blocks;
	}

	public function setBlocks(array $blocks): void
	{
		$this->blocks = $blocks;
	}

	public function addBlock(InternalReferenceFormatBlock $block): void
	{
		$this->blocks[] = $block;
	}

	public function getSeparator(): SeparatorEnum
	{
		return $this->separator;
	}

	public function setSeparator(SeparatorEnum $separator): void
	{
		$this->separator = $separator;
	}

	public function getTriggeringStatus(): ?StatusEntity
	{
		return $this->triggeringStatus;
	}

	public function setTriggeringStatus(?StatusEntity $triggeringStatus): InternalReferenceFormat
	{
		$this->triggeringStatus = $triggeringStatus;

		return $this;
	}

	public function isShowToApplicant(): bool
	{
		return $this->showToApplicant;
	}

	public function setShowToApplicant(bool $showToApplicant): InternalReferenceFormat
	{
		$this->showToApplicant = $showToApplicant;

		return $this;
	}

	public function isShowInFiles(): bool
	{
		return $this->showInFiles;
	}

	public function setShowInFiles(bool $showInFiles): InternalReferenceFormat
	{
		$this->showInFiles = $showInFiles;

		return $this;
	}

	public function getSequence(): ?InternalReferenceFormatSequence
	{
		return $this->sequence;
	}

	public function setSequence(?InternalReferenceFormatSequence $sequence): InternalReferenceFormat
	{
		$this->sequence = $sequence;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'blocks'           => array_map(function (InternalReferenceFormatBlock $block) {
				return $block->__serialize();
			}, $this->blocks),
			'separator'        => $this->separator->value,
			'triggering_status' => $this->triggeringStatus?->getStep(),
			'show_to_applicant'  => $this->showToApplicant,
			'show_in_files'      => $this->showInFiles,
			'sequence'         => $this->sequence?->__serialize(),
		];
	}

	public function __toMappingFormat(): array
	{
		return [
			'id'              => 'custom_reference_format',
			'label'           => 'Custom Reference Format',
			'synchronizer_id' => null,
			'target_object'   => '',
			'params'          => [
				'separator'           => $this->separator->value,
				'triggering_status'   => $this->triggeringStatus?->getStep(),
				'show_to_applicant'   => $this->showToApplicant ? 1 : 0,
				'show_in_files'       => $this->showInFiles ? 1 : 0,
				'sequence'            => !empty($this->sequence),
				'sequence_position'   => $this->sequence?->getPosition()->value,
				'sequence_reset_type' => $this->sequence?->getResetType()->value,
				'sequence_length'     => $this->sequence?->getLength(),
			],
			'blocks'          => array_map(function (InternalReferenceFormatBlock $block) {
				return [
					'id'              => 'block_' . uniqid(),
					'mapping_id'      => 'custom_reference_format',
					'order'           => 0,
					'source_type'     => $block->getType()->value,
					'source_field'    => $block->getValue(),
					'target_field'    => '',
					'transformations' => array_map(function (MappingTransformEntity $transformation) {
						return $transformation->serialize();
					}, $block->getTransformations()),
				];
			}, $this->blocks),
		];
	}
}