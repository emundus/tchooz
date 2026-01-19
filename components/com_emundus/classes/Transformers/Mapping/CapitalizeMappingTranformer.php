<?php

namespace Tchooz\Transformers\Mapping;

use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Enums\Mapping\MappingTransformersEnum;

class CapitalizeMappingTranformer extends MappingTranformer
{
	public function __construct()
	{
		parent::__construct(MappingTransformersEnum::CAPITALIZE);
	}

	public function transform(mixed $value): mixed
	{
		if (!is_string($value)) {
			return $value;
		}

		return ucwords(strtolower($value));
	}
}