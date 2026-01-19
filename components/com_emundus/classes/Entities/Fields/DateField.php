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
		return $this->defaultSchema();
	}
}