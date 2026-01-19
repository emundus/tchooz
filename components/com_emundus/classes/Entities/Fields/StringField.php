<?php

namespace Tchooz\Entities\Fields;

use Tchooz\Services\Field\FieldResearch;

class StringField extends Field
{
	private ?int $minLength = null;
	private ?int $maxLength = null;

	public function __construct(string $name, string $label, bool $required = false, ?FieldGroup $group = null, ?int $minLength = null, ?int $maxLength = null, ?FieldResearch $research = null, array $displayRules = [])
	{
		parent::__construct($name, $label, $required, $group, $research, $displayRules);
		$this->minLength = $minLength;
		$this->maxLength = $maxLength;
	}

	public static function getType(): string
	{
		return 'string';
	}

	/**
	 * @inheritDoc
	 */
	public function toSchema(): array
	{
		return [
			'type' => StringField::getType(),
			'name' => $this->getName(),
			'label' => $this->getLabel(),
			'required' => $this->isRequired(),
			'group' => $this->getGroup()?->toSchema(),
			'minLength' => $this->minLength,
			'maxLength' => $this->maxLength,
			'displayRules' => array_map(fn($rule) => $rule->toSchema(), $this->getDisplayRules())
		];
	}
}