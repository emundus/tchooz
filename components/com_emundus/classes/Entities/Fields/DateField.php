<?php

namespace Tchooz\Entities\Fields;

class DateField extends Field
{

	public static function getType(): string
	{
		return 'date';
	}

	/**
	 * @inheritDoc
	 */
	public function toSchema(): array
	{
		return [
			'name' => $this->name,
			'label' => $this->label,
			'type' => $this->getType(),
			'required' => $this->required,
			'group' => $this->getGroup()?->toSchema()
		];
	}
}