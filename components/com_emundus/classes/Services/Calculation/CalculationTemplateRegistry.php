<?php

namespace Tchooz\Services\Calculation;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Calculation\Templates\CalculationTemplate;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Services\Field\DisplayRule;

class CalculationTemplateRegistry
{
	private const TEMPLATES_DIRECTORY = JPATH_ROOT . '/components/com_emundus/classes/Entities/Calculation/Templates';

	/** @var CalculationTemplate[] */
	private array $templates = [];

	/**
	 * @param $checkAvailability bool only necessary for the front-end context, as some templates may not be available in the front-end (e.g. due to missing dependencies)
	 */
	public function __construct($checkAvailability = false)
	{
		$this->autoRegisterTemplates($checkAvailability);
	}

	private function autoRegisterTemplates($checkAvailability = false): void
	{
		$files = glob(self::TEMPLATES_DIRECTORY . '/*.php');
		if ($files) {
			foreach ($files as $file) {
				$className = 'Tchooz\\Entities\\Calculation\\Templates\\' . pathinfo($file, PATHINFO_FILENAME);
				if (class_exists($className)) {
					$reflection = new \ReflectionClass($className);
					if (!$reflection->isAbstract() && $reflection->isSubclassOf(CalculationTemplate::class)) {
						$instance = $reflection->newInstance();
						if ($instance instanceof CalculationTemplate) {
							if ($instance->isAvailable() || !$checkAvailability)
							{
								$this->register($instance);
							}
						}
					}
				}
			}
		}
	}

	public function register(CalculationTemplate $template): void
	{
		$this->templates[$template::getCode()] = $template;
	}

	public function getTemplate(string $code): ?CalculationTemplate
	{
		return $this->templates[$code] ?? null;
	}

	/**
	 * @return CalculationTemplate[]
	 */
	public function getTemplates(): array
	{
		return $this->templates;
	}

	/**
	 * @return array<Field>
	 */
	public function getParameters(): array
	{
		$parameters = [];
		$typeField = new ChoiceField('type', Text::_('COM_EMUNDUS_CALCULATION_TYPE'), [], true, false, null, false, false);
		$typeField->addChoice(new ChoiceFieldValue('custom', Text::_('COM_EMUNDUS_CALCULATION_TPL_CUSTOM')));
		foreach ($this->getTemplates() as $template) {
			$typeField->addChoice(new ChoiceFieldValue($template::getCode(), Text::_($template->getLabel())));
		}
		$parameters[] = $typeField;

		$parameters[] = (new StringField('operation', Text::_('COM_EMUNDUS_CALCULATION_OPERATION')))->setDisplayRules([new DisplayRule($typeField, conditionOperator: \Tchooz\Enums\Automation\ConditionOperatorEnum::EQUALS, value: 'custom')]);

		return $parameters;
	}

	public function getParametersByTemplates(array $templateCodes = []): array
	{
		$parametersByTemplates = [];

		foreach ($this->getTemplates() as $template) {
			if (empty($templateCodes) || in_array($template::getCode(), $templateCodes)) {
				$parametersByTemplates[$template::getCode()] = $template->getParameters();
			}
		}

		return $parametersByTemplates;
	}
}