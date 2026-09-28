<?php

namespace Tchooz\Transformers\Mapping;

use Tchooz\Enums\Mapping\MappingTransformersEnum;

class FullnameFormatTransformer extends MappingTranformer
{
	public function __construct()
	{
		parent::__construct(MappingTransformersEnum::FULLNAME_FORMAT);
	}

	public function transform(mixed $value): mixed
	{
		if (!is_string($value) || trim($value) === '')
		{
			return $value;
		}

		$parts     = preg_split('/\s+/u', trim($value), 2);
		$firstname = implode('-', array_map(
			fn(string $part) => mb_strtoupper(mb_substr($part, 0, 1)) . mb_substr($part, 1),
			explode('-', $parts[0])
		));
		$lastname  = isset($parts[1]) ? mb_strtoupper($parts[1]) : '';

		return trim($firstname . ' ' . $lastname);
	}
}
