<?php

namespace Tchooz\Transformers\Mapping;

use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Enums\Mapping\MappingTransformersEnum;

class UppercaseMappingTransformer extends MappingTranformer
{
	public function __construct()
	{
		parent::__construct(MappingTransformersEnum::UPPERCASE);
	}
	public function transform(mixed $value): mixed
	{
		if (is_string($value))
		{
			return mb_strtoupper($value);
		}

		return $value;
	}
}