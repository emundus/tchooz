<?php

namespace Tchooz\Transformers\Mapping;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Enums\Mapping\MappingTransformersEnum;
use Tchooz\Enums\Reference\ResetTypeEnum;
use Tchooz\Providers\DateProvider;
use Tchooz\Repositories\Reference\InternalReferenceRepository;

class SequentialMappingTranformer extends MappingTranformer
{
	public function __construct()
	{
		$parameters = [
			new ChoiceField('reset_type', Text::_('COM_TCHOOZ_MAPPING_TRANSFORMER_SEQUENTIAL_RESET_TYPE_LABEL'), $this->getAvailableResetTypes(), true),
			new NumericField('length', Text::_('COM_TCHOOZ_MAPPING_TRANSFORMER_SEQUENTIAL_LENGTH_LABEL'), true)
		];
		parent::__construct(MappingTransformersEnum::SEQUENTIAL, $parameters);
	}

	private function getAvailableResetTypes(): array
	{
		return [
			new ChoiceFieldValue(ResetTypeEnum::NEVER->value, ResetTypeEnum::NEVER->getLabel()),
			new ChoiceFieldValue(ResetTypeEnum::YEARLY->value, ResetTypeEnum::YEARLY->getLabel()),
			new ChoiceFieldValue(ResetTypeEnum::CAMPAIGN->value, ResetTypeEnum::CAMPAIGN->getLabel()),
			new ChoiceFieldValue(ResetTypeEnum::PROGRAM->value, ResetTypeEnum::PROGRAM->getLabel()),
		];
	}

	public function transform(mixed $value): mixed
	{
		$target = $this->getWithOfType(ApplicationFileEntity::class)[0];
		assert($target instanceof ApplicationFileEntity);

		$dateProvider = $this->getWithOfType(DateProvider::class)[0];
		assert($dateProvider instanceof DateProvider);

		$resetType = $this->getParameterValue('reset_type');
		$length    = $this->getParameterValue('length');

		$internReferenceRepository = new InternalReferenceRepository();

		$sequence = '';
		if (!empty($resetType) && !empty($length))
		{
			$sequence = str_pad('1', $length, '0', STR_PAD_LEFT);
			switch ($resetType)
			{
				case 'yearly':
					$lastSequence = $internReferenceRepository->getLastSequenceByYear($dateProvider->getCurrentYear());
					$sequence     = str_pad($lastSequence + 1, $length, '0', STR_PAD_LEFT);
					break;
				case 'campaign':
					$campaign = $target->getCampaign();
					if ($campaign && method_exists($campaign, 'getId'))
					{
						$lastSequence = $internReferenceRepository->getLastSequenceByCampaign($campaign->getId());
						$sequence     = str_pad($lastSequence + 1, $length, '0', STR_PAD_LEFT);
					}
					break;
				case 'program':
					$program = $target->getCampaign()->getProgram();
					if ($program && method_exists($program, 'getId'))
					{
						$lastSequence = $internReferenceRepository->getLastSequenceByProgram($program->getId());
						$sequence     = str_pad($lastSequence + 1, $length, '0', STR_PAD_LEFT);
					}
					break;
				case 'never':
					$lastSequence = $internReferenceRepository->getLastSequence();
					$sequence     = str_pad($lastSequence + 1, $length, '0', STR_PAD_LEFT);
					break;
				default:
					return $value;
			}
		}

		$value .= $sequence;

		return $value;
	}
}