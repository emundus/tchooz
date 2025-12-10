<?php

namespace Tchooz\Entities\Fields;

use Joomla\CMS\Language\Text;

class ChoiceField extends Field
{
	private array $choices = [];

	private bool $multiple = false;

	private bool $choicesGrouped = false;

	public function __construct(
		string $name,
		string $label,
		array $choices,
		bool $required = false,
		bool $multiple = false,
		?FieldGroup $group = null,
		bool $choicesGrouped = false,
		bool $addSelectOption = true
	) {
		parent::__construct($name, $label, $required, $group);

		if (!empty($choices)) {
			if (!$multiple && $addSelectOption) {
				$this->addChoice(new ChoiceFieldValue(null, Text::_('TCHOOZ_AUTOMATION_FIELD_CHOICE_SELECT_OPTION')));
			}

			foreach ($choices as $choice) {
				assert($choice instanceof ChoiceFieldValue);
				$this->addChoice($choice);
			}
		}
		$this->multiple = $multiple;
		$this->choicesGrouped = $choicesGrouped;
	}


	public static function getType(): string
	{
		return 'choice';
	}

	public function addChoice(ChoiceFieldValue $choice): void
	{
		$this->choices[] = $choice;
	}

	public function getChoices(): array
	{
		return $this->choices;
	}

	public function setChoices(array $choices): void
	{
		$this->choices = $choices;
	}

	public function getMultiple(): bool
	{
		return $this->multiple;
	}

	public function setMultiple(bool $multiple): void
	{
		$this->multiple = $multiple;
	}

	public function areChoicesGrouped(): bool
	{
		return $this->choicesGrouped;
	}

	public function toSchema(): array
	{
		return [
			'name' => $this->name,
			'label' => $this->label,
			'type' => $this->getType(),
			'choices' => array_map(fn($choice) => $choice->toSchema(), $this->choices),
			'required' => $this->required,
			'multiple' => $this->getMultiple(),
			'group' => $this->getGroup()?->toSchema(),
			'research' => $this->getResearch()?->toSchema(),
			'displayRules' => array_map(fn($rule) => $rule->toSchema(), $this->getDisplayRules())
		];
	}
}