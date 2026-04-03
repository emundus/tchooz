<?php

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Calculation\Templates\CalculateAverage;
use Tchooz\Entities\Calculation\Templates\CalculateDatesDiff;
use Tchooz\Entities\Calculation\Templates\CalculatePercentile;
use Tchooz\Entities\Fabrik\ObservableElement;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Time\TimeUnitEnum;
use Tchooz\Factories\Calculation\CalculationFactory;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Factories\JoomlaForm\JoomlaFormFactory;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Services\Calculation\CalculationEngine;
use Tchooz\Services\Calculation\CalculationTemplateRegistry;
use Tchooz\Transformers\CurrencyTransformer;
use Tchooz\Transformers\XMLTransformer;

class PlgFabrik_ElementEmundus_calculation extends PlgFabrik_Element
{
	private FabrikRepository $repository;

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->repository = new FabrikRepository();
		$factory          = new FabrikFactory($this->repository);
		$this->repository->setFactory($factory);
		Log::addLogger(['text_file' => 'plugins.fabrik_element.emundus_calculation.php'], Log::ALL, ['plugins.fabrik_element.emundus_calculation']);
	}

	public function getPluginForm($repeatCounter = null)
	{
		$form = parent::getPluginForm();

		$calculationTemplateRegistry = new CalculationTemplateRegistry(false);

		$formFactory    = new JoomlaFormFactory();
		$xmlTransformer = new XMLTransformer();
		foreach ($form->getXml()->fields->fieldset->field as $field)
		{
			if (str_ends_with($field->attributes()->name, 'form'))
			{
				// remove _form suffix to get element name
				$name = substr($field->attributes()->name, 0, -5);

				$template = $calculationTemplateRegistry->getTemplate($name);
				if (!empty($template))
				{
					$xmlString  = $formFactory->build($template->getParameters());
					$xmlElement = simplexml_load_string($xmlString);
					$xmlTransformer->appendXml($field, $xmlElement, 'form');
				}
			}
		}

		return $form;
	}

	/**
	 * Get database field description
	 *
	 * @return  string  Db field type
	 */
	public function getFieldDescription(): string
	{
		return 'DECIMAL(10,3)';
	}

	public function render($data, $repeatCounter = 0): string
	{
		$layout = $this->getLayout('form');

		$displayData                = new stdClass;
		$displayData->id            = $this->getHTMLId($repeatCounter);
		$displayData->name          = $this->getHTMLName($repeatCounter);
		$displayData->value         = $this->getValue($data, $repeatCounter);
		$displayData->repeatCounter = $repeatCounter;
		$displayData->type          = $this->getParams()->get('type');
		$displayData->suffix = '';

		if (!$this->assertNoCalculationLoop())
		{
			$layout = $this->getLayout('misconfiguration');
			$displayData->errorMessage = Text::_('COM_EMUNDUS_CALCULATION_LOOP_ERROR');
		}

		if ($displayData->type === CalculateDatesDiff::getCode())
		{
			$entry = CalculateDatesDiff::getCode() . '_form';

			$unit = $this->getParams()->get($entry)->unit ?? TimeUnitEnum::YEARS;
			if (is_string($unit))
			{
				$unit = TimeUnitEnum::tryFrom($unit);
			}
			$displayData->suffix = $unit->getLabel();
		}

		return $layout->render($displayData);
	}

	public function elementJavascript($repeatCounter): array
	{
		$id   = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);

		if ($this->assertNoCalculationLoop())
		{
			$opts->elementsToObserve = $this->getElementsToObserve();
		}
		else
		{
			$opts->elementsToObserve = [];
		}

		$opts->id                = $this->getId();

		return array('FbEmundus_Calculation', $id, $opts);
	}

	/**
	 * @return array<ObservableElement>
	 */
	private function getElementsToObserve(): array
	{
		$elementsToObserve = array();
		$params            = $this->getParams();
		$type              = $params->get('type');

		$currentFormId = $this->getFormModel()->getId();

		switch ($type)
		{
			case 'custom':
				try {
					$configuration = CalculationFactory::validateCalculationConfigurationFromJson($params->get('operation'));
				} catch (\Exception $e) {
					Log::add('Error parsing calculation configuration: ' . $e->getMessage(), Log::ERROR, 'plugins.fabrik_element.emundus_calculation');
					return [];
				}

				foreach ($configuration['fields'] as $field)
				{
					switch ($field['type'])
					{
						case ConditionTargetTypeEnum::FORMDATA->value:
							list($elementFormId, $elementId) = explode('.', $field['field']);
							if ($elementFormId == $currentFormId) {
								$element = $this->getFormModel()->getElement($elementId, true);
								if (!empty($element))
								{
									$elementsToObserve[] = new ObservableElement(
										targetType: ConditionTargetTypeEnum::FORMDATA,
										id: $element->id,
										name: $element->getFullName(true, false),
										value: null,
										groupId: $element->getGroup()->getId(),
										inRepeatGroup: (bool) $element->getGroup()->canRepeat(),
									);
								}
							}

							break;
						case ConditionTargetTypeEnum::ALIASDATA->value:
							break;
					}
				}

				break;
			case CalculateAverage::getCode():
				$form = $params->get(CalculateAverage::getCode() . '_form');

				if (!empty($form))
				{
					foreach ($form->elements as $element)
					{
						switch ($element->element_type)
						{
							case ConditionTargetTypeEnum::FORMDATA->value:
								list($elementFormId, $elementId) = explode('.', $element->element_id);
								if ($elementFormId == $currentFormId)
								{
									$fabrikElement = $this->getFormModel()->getElement($elementId, true);
									$elementsToObserve[] = new ObservableElement(
										targetType: ConditionTargetTypeEnum::FORMDATA,
										id: $element->element_id,
										name: $fabrikElement->getFullName(true, false),
										value: null,
										groupId: $fabrikElement->getGroup()->getId(),
										inRepeatGroup: (bool) $fabrikElement->getGroup()->canRepeat(),
									);
								}
								break;
							case ConditionTargetTypeEnum::ALIASDATA->value:
								// todo:
								break;
						}
					}
				}

				break;
			case CalculateDatesDiff::getCode():
				$form = $params->get(CalculateDatesDiff::getCode() . '_form');
				$startDateElementId = $form->start_date_element;
				$endDateElementId   = $form->end_date_element;

				if (!empty($startDateElementId))
				{
					list($elementFormId, $elementId) = explode('.', $startDateElementId);
					if ($elementFormId == $currentFormId)
					{
						$startDateElt = $this->getFormModel()->getElement($elementId, true);

						if (!empty($startDateElt))
						{
							$elementsToObserve[] = new ObservableElement(
								targetType: ConditionTargetTypeEnum::FORMDATA,
								id: $startDateElementId,
								name: $startDateElt->getFullName(true, false),
								value: null,
								groupId: $startDateElt->getGroup()->getId(),
								inRepeatGroup: (bool) $startDateElt->getGroup()->canRepeat(),
							);
						}
					}
				}

				if (!empty($endDateElementId))
				{
					list($elementFormId, $elementId) = explode('.', $endDateElementId);
					if ($elementFormId == $currentFormId)
					{
						$endDateElt = $this->getFormModel()->getElement($elementId, true);

						if (!empty($endDateElt))
						{
							$elementsToObserve[] = new ObservableElement(
								targetType: ConditionTargetTypeEnum::FORMDATA,
								id: $endDateElementId,
								name: $endDateElt->getFullName(true, false),
								value: null,
								groupId: $endDateElt->getGroup()->getId(),
								inRepeatGroup: (bool) $endDateElt->getGroup()->canRepeat(),
							);
						}
					}
				}

				break;
			case CalculatePercentile::getCode():
				$form = $params->get(CalculatePercentile::getCode() . '_form');

				if (!empty($form) && !empty($form->elements))
				{
					foreach ($form->elements as $element)
					{
						switch ($element->element_type)
						{
							case ConditionTargetTypeEnum::FORMDATA->value:
								list($elementFormId, $elementId) = explode('.', $element->element_id);
								if ($elementFormId == $currentFormId)
								{
									$fabrikElement = $this->getFormModel()->getElement($elementId, true);
									$elementsToObserve[] = new ObservableElement(
										targetType: ConditionTargetTypeEnum::FORMDATA,
										id: $element->element_id,
										name: $fabrikElement->getFullName(true, false),
										value: null,
										groupId: $fabrikElement->getGroup()->getId(),
										inRepeatGroup: (bool) $fabrikElement->getGroup()->canRepeat(),
									);
								}
								break;
							case ConditionTargetTypeEnum::ALIASDATA->value:
								// todo:
								break;
						}
					}
				}

				break;
		}

		return $elementsToObserve;
	}

	/**
	 * @return void
	 * @throws Throwable
	 */
	public function onAjax_emundus_calculation(): void
	{
		$result = 0;
		$input  = $this->app->input;
		$this->setId($input->getInt('element_id'));
		$this->loadMeForAjax();
		$params        = $this->getParams();
		$w             = new FabrikWorker;
		$filter        = InputFilter::getInstance();
		$d             = $filter->clean($_REQUEST, 'array');
		$formModel     = $this->getFormModel();
		$repeatCounter = $this->app->input->get('repeatCounter', '0');
		$formModel->addEncrytedVarsToArray($d);
		$this->getFormModel()->data = $d;
		$this->swapValuesForLabels($d);
		$targetEntity = null;

		try
		{
			if (!$this->assertNoCalculationLoop())
			{
				throw new \Exception(Text::_('COM_EMUNDUS_CALCULATION_ERROR'));
			}

			$element = $this->repository->getElementById($this->getId());
			$calculationEntity  = CalculationFactory::calculationEntityFromFabrikElement($element);

			$elementValues = $this->getElementValuesFromObservables($d, $repeatCounter, $calculationEntity->getTemplateCode());
			$calculationContext = CalculationFactory::calculationContextFromFabrikElement($element, $elementValues, $targetEntity);

			if (!empty($calculationEntity) && !empty($calculationContext))
			{
				$calculationEngine = new CalculationEngine();
				$result            = $calculationEngine->execute($calculationEntity, $calculationContext);
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error executing calculation: ' . $e->getMessage(), Log::ERROR, 'plugins.fabrik_element.emundus_calculation');
			$result = 0;

			$this->addErrorHTML($repeatCounter, Text::_('COM_EMUNDUS_CALCULATION_ERROR') . ': ' . $e->getMessage());
		}

		echo $result;
	}

	private function getElementValuesFromObservables($d, $repeatCounter, ?string $templateCode): array
	{
		$values = [];

		$transformer = new CurrencyTransformer();
		foreach ($this->getElementsToObserve() as $observable)
		{
			if ($observable->isInRepeatGroup())
			{
				$groupedValues = [];
				if ($this->getGroup()->getId() === $observable->getGroupId())
				{
					if (is_array($d[$observable->getName() . '_' . $repeatCounter . '_raw']))
					{
						$groupedValues = $d[$observable->getName() . '_' . $repeatCounter . '_raw'];
					}
					else
					{
						$groupedValues[] = $d[$observable->getName() . '_' . $repeatCounter . '_raw'];
					}
				}
				else
				{
					$groupedValues = $d[$observable->getName()  . '_raw'];
				}

				// Decode URL-encoded characters (%20, %2C, etc.) from values
				$groupedValues = array_map('urldecode', $groupedValues);

				if (empty($templateCode))
				{
					$groupedValues = array_map(function ($value) use ($transformer) {
						return (float) $transformer->transform($value);
					}, $groupedValues);

					$values[$observable->getId()] = array_sum($groupedValues);
				}
				else
				{
					$values[$observable->getId()] = $groupedValues;
				}
			}
			else if (isset($d[$observable->getName()]))
			{
				if (empty($templateCode))
				{
					$values[$observable->getId()] = $transformer->transform($d[$observable->getName() . '_raw']);
				}
				else
				{
					$values[$observable->getId()] = $d[$observable->getName() . '_raw'];
				}
			}
		}

		return $values;
	}

	/**
	 * @return bool
	 */
	private function assertNoCalculationLoop(): bool
	{
		$noLoop = true;

		$observables = $this->getElementsToObserve();

		foreach ($observables as $observable)
		{
			if ($observable->getId() == $this->getId())
			{
				$noLoop = false;
				break;
			}
		}

		return $noLoop;
	}
}