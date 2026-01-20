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
		return $this->defaultSchema();
	}
}