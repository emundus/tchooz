<?php

namespace Tchooz\Transformers\Mapping;

use Tchooz\Enums\Mapping\MappingTransformersEnum;

class ToBooleanTransformer extends MappingTranformer
{

	public function __construct()
	{
		// todo: allow to define conditions to check resulting on a true/false
		parent::__construct(MappingTransformersEnum::BOOLEAN);
	}

	public function transform(mixed $value): mixed
	{
		if (is_bool($value)) {
			return $value;
		}

		if (is_numeric($value)) {
			return $value > 0;
		}

		if (is_string($value)) {
			$value = strtolower($value);
			return in_array($value, ['true', '1', 'yes', 'on']);
		}

		if (!empty($value)) {
			return true;
		}

		return false;
	}
}