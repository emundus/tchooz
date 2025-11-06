<?php

namespace Tchooz\Entities\Fields;

class BooleanField extends Field
{

	public static function getType(): string
	{
		return 'boolean';
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