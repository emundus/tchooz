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
		return  $this->defaultSchema();
	}
}