<?php

namespace Tchooz\Entities\Fields;

class StringField extends Field
{
	private ?int $minLength = null;
	private ?int $maxLength = null;

	public function __construct(string $name, string $label, bool $required = false, ?FieldGroup $group = null, ?int $minLength = null, ?int $maxLength = null)
	{
		parent::__construct($name, $label, $required, $group);
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
			'maxLength' => $this->maxLength
		];
	}
}