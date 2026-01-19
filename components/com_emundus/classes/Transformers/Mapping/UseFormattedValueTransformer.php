<?php

namespace Tchooz\Transformers\Mapping;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Enums\Mapping\MappingTransformersEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Services\Automation\ConditionRegistry;

class UseFormattedValueTransformer extends MappingTranformer
{

	public function __construct()
	{
		parent::__construct(MappingTransformersEnum::USE_FORMATTED_VALUE);
	}

	public function transform(mixed $value): mixed
	{
		if ($this->isExecutedWith(MappingRowEntity::class) && $this->isExecutedWith(ActionTargetEntity::class))
		{
			$mappingRows = $this->getWithOfType(MappingRowEntity::class);
			$mappingRow = $mappingRows[0];
			assert($mappingRow instanceof MappingRowEntity);
			$contexts    = $this->getWithOfType(ActionTargetEntity::class);
			$context = $contexts[0];
			assert($context instanceof ActionTargetEntity);

			$conditionsRegistry = new ConditionRegistry();
			$resolver = $conditionsRegistry->getResolver($mappingRow->getSourceType()->value);

			if ($resolver)
			{
				$foundFormattedValue = false;
				$availableFields = $resolver->getAvailableFields([]);

				foreach ($availableFields as $availableField)
				{
					if ($availableField->getName() === $mappingRow->getSourceField())
					{
						if ($availableField instanceof ChoiceField)
						{
							foreach ($availableField->getChoices() as $choice)
							{
								if ($choice->getValue() == $value)
								{
									$value = $choice->getLabel();
									$foundFormattedValue = true;
									break 2;
								}
							}
						}
						break;
					}
				}

				if (!$foundFormattedValue)
				{
					$value = $resolver->resolveValue($context, $mappingRow->getSourceField(), ValueFormatEnum::FORMATTED);
				}
			}
		}

		return $value;
	}
}