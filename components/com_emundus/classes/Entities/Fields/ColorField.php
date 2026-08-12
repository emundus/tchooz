<?php

namespace Tchooz\Entities\Fields;

class ColorField extends Field
{
	public static function getType(): string
	{
		return 'color';
	}

	/**
	 * @inheritDoc
	 */
	public function toSchema(): array
	{
		return $this->defaultSchema();
	}
}
