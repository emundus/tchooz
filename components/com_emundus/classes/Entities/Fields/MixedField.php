<?php

namespace Tchooz\Entities\Fields;

class MixedField extends Field
{
	public static function getType(): string
	{
		return 'mixed';
	}

	public function toSchema(): array
	{
		return [
			'type' => $this->getType(),
			'name' => $this->getName(),
			'label' => $this->getLabel(),
			'required' => $this->isRequired(),
			'group' => $this->getGroup()?->toSchema(),
		];
	}
}