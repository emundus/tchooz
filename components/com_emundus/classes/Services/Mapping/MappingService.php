<?php

namespace Tchooz\Services\Mapping;

use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Services\Automation\ConditionRegistry;

class MappingService
{
	/*
	 * Convert source data to destination data based on the provided mapping entity, and the context if any.
	 */
	public static function getJsonFromMapping(MappingEntity $mapping, ActionTargetEntity $context): array
	{
		$json = [];

		if (!empty($context->getFile()))
		{
			$conditionsRegistry      = new ConditionRegistry();
			$transformationsRegistry = new MappingTransformationsRegistry();

			foreach ($mapping->getRows() as $mappingRow)
			{
				assert($mappingRow instanceof MappingRowEntity);
				$resolver = $conditionsRegistry->getResolver($mappingRow->getSourceType()->value);
				$value    = $resolver->resolveValue($context, $mappingRow->getSourceField());

				if (!empty($mappingRow->getTransformations()))
				{
					foreach ($mappingRow->getTransformations() as $transformation)
					{
						$transformer = $transformationsRegistry->getTransformer($transformation->getType());

						if ($transformer)
						{
							$transformer->setParametersValues($transformation->getParameters());

							$value = $transformer->with($mappingRow)
								->with($context)
								->transform($value);
						}
					}
				}

				if (!class_exists('HtmlSanitizerSingleton'))
				{
					require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
				}

				if (is_string($value))
				{
					$sanitizer = HtmlSanitizerSingleton::getInstance();
					$value     = $sanitizer->sanitizeNoHtml($value);
					$value     = trim($value);
				}
				else if (is_array($value))
				{
					foreach ($value as $key => $val)
					{
						if (is_string($val))
						{
							$sanitizer = HtmlSanitizerSingleton::getInstance();
							$val       = $sanitizer->sanitizeNoHtml($val);
							$val       = trim($val);
						}

						$value[$key] = $val;
					}
				}

				$json[$mappingRow->getTargetField()] = $value;
			}
		}

		return $json;
	}
}