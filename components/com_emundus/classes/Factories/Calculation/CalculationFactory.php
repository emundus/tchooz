<?php

namespace Tchooz\Factories\Calculation;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Calculation\CalculationEntity;
use Tchooz\Entities\Calculation\Templates\CalculateAverage;
use Tchooz\Entities\Calculation\Templates\CalculateDatesDiff;
use Tchooz\Entities\Calculation\Templates\CalculatePercentile;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Calculation\CalculationTypeEnum;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Enums\Time\TimeUnitEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Factories\TransformerFactory;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Services\Automation\ConditionRegistry;
use Tchooz\Services\Calculation\CalculationContext;

class CalculationFactory
{

	/**
	 * @param   FabrikElementEntity  $element
	 *
	 * @return ?CalculationEntity
	 * @throws \Exception
	 */
	public static function calculationEntityFromFabrikElement(FabrikElementEntity $element): ?CalculationEntity
	{
		$calculation = null;

		if ($element->getPlugin() === ElementPluginEnum::EMUNDUS_CALCULATION)
		{
			$calculation = new CalculationEntity(0);

			switch ($element->getParams()->type)
			{
				case CalculateAverage::getCode():
				case CalculatePercentile::getCode():
				case CalculateDatesDiff::getCode():
					$calculation->setType(CalculationTypeEnum::TEMPLATE)
						->setTemplateCode($element->getParams()->type);
					break;
				case 'custom':
					$calculation->setType(CalculationTypeEnum::CUSTOM);
					$config = self::validateCalculationConfigurationFromJson($element->getParams()->operation);

					foreach ($config['fields'] as $key => $field)
					{
						$config['fields'][$key] = $key;
					}

					$calculation->setConfiguration($config);
					break;
				default:
					throw new \Exception('Unknown calculation type: ' . $element->getParams()->type);
			}
		}

		return $calculation;
	}

	/**
	 * @param   FabrikElementEntity      $element
	 * @param   array                    $values
	 * @param   ActionTargetEntity|null  $targetEntity
	 *
	 * @return ?CalculationContext
	 * @throws \Exception
	 */
	public static function calculationContextFromFabrikElement(FabrikElementEntity $element, array $values, ?ActionTargetEntity $targetEntity = null): ?CalculationContext
	{
		$context = null;

		if ($element->getPlugin() === ElementPluginEnum::EMUNDUS_CALCULATION)
		{
			switch ($element->getParams()->type)
			{
				case CalculateAverage::getCode():
					$data = [
						'result_out_of' => (float) $element->getParams()->average_form->result_out_of,
						'elements'      => []
					];

					foreach ($element->getParams()->average_form->elements as $elementData)
					{
						$data['elements'][] = [
							'element_id'          => $elementData->element_id,
							'element_ponderation' => (float) $elementData->element_ponderation,
							'element_value'       => (float) $values[$elementData->element_id] ?? 0,
							'element_out_of'      => (float) $elementData->element_out_of
						];
					}

					$context = new CalculationContext($data);

					break;
				case CalculatePercentile::getCode():
					$data = [
						'percentile' => (int) $element->getParams()->percentile_form->percentile,
						'elements'   => []
					];

					foreach ($element->getParams()->percentile_form->elements as $elementData)
					{
						$data['elements'][] = [
							'element_id'    => $elementData->element_id,
							'element_value' => (float) $values[$elementData->element_id] ?? 0,
						];
					}

					$context = new CalculationContext($data);
					break;
				case CalculateDatesDiff::getCode():
					$fabrikRepository = new FabrikRepository();
					$fabrikFactory    = new FabrikFactory($fabrikRepository);
					$fabrikRepository->setFactory($fabrikFactory);

					list($formId, $elementId) = explode('.', $element->getParams()->dates_diff_form->start_date_element);
					$startDateElt   = $fabrikRepository->getElementById($elementId);
					$transformer    = TransformerFactory::make($startDateElt->getPlugin()->value, ['details_date_format' => 'Y-m-d']);
					$startDateValue = $transformer->transform($values[$element->getParams()->dates_diff_form->start_date_element] ?? null);

					if (!empty($element->getParams()->dates_diff_form->end_date_element))
					{
						list($formId, $elementId) = explode('.', $element->getParams()->dates_diff_form->end_date_element);
						$endDateElt   = $fabrikRepository->getElementById($elementId);
						$transformer  = TransformerFactory::make($endDateElt->getPlugin()->value, ['details_date_format' => 'Y-m-d']);
						$endDatevalue = $transformer->transform($values[$element->getParams()->dates_diff_form->end_date_element] ?? null);
					}

					$context = new CalculationContext([
						'start_date_element' => $startDateValue,
						'end_date_element'   => !empty($endDatevalue) ? $endDatevalue : (new \DateTime())->format('Y-m-d'),
						'unit'               => $element->getParams()->dates_diff_form->unit ?? TimeUnitEnum::YEARS,
					]);
					break;
				case 'custom':
					$configuration = self::validateCalculationConfigurationFromJson($element->getParams()->operation);
					$data = [];

					$registry = new ConditionRegistry();
					foreach ($configuration['fields'] as $fieldKey =>  $field) {

						switch ($field['type'])
						{
							case ConditionTargetTypeEnum::FORMDATA->value:
								list($formId, $elementId) = explode('.', $field['field']);

								$fieldValueEntry = $elementId;
								break;
							default:
								$fieldValueEntry = $field['field'];
						}

						if (!isset($values[$fieldValueEntry]))
						{
							if (!empty($targetEntity))
							{
								$resolver = $registry->getResolver($field['type']);

								if ($resolver) {
									$data[$fieldKey] = $resolver->resolveValue($targetEntity, $field['field']);
								}
								else
								{
									throw new \Exception('No resolver found for field type: ' . $field['type']);
								}
							}
							else
							{
								$data[$fieldKey] = 0;
							}
						} else
						{
							$data[$fieldKey] = !empty($values[$fieldValueEntry]) ? $values[$fieldValueEntry] : 0;
						}
					}

					$data = array_map('floatval', $data);

					$context = new CalculationContext($data);
					break;
				default:
					throw new \Exception('Unknown calculation type: ' . $element->getParams()->type);
			}

		}

		return $context;
	}

	/**
	 * @param   ?string  $json
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function validateCalculationConfigurationFromJson(?string $json): array
	{
		if (empty($json))
		{
			throw new \Exception('Invalid custom calculation operation format');
		}

		$data = json_decode($json, true);

		if (!is_array($data) || !isset($data['operation']) || !isset($data['fields']))
		{
			throw new \Exception('Invalid custom calculation operation format');
		}

		foreach($data['fields'] as $field)
		{
			if (!isset($field['type']) || !isset($field['field']))
			{
				throw new \Exception('Invalid field format in custom calculation operation');
			}
		}

		$data['operation'] = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{200C}\x{200D}]/u', ' ', $data['operation']);
		$data['operation'] = preg_replace('/\s+/', ' ', trim($data['operation']));

		return $data;
	}
}