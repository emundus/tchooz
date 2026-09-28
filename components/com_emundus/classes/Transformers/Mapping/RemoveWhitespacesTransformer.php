<?php

namespace Tchooz\Transformers\Mapping;

use Tchooz\Enums\Mapping\MappingTransformersEnum;

class RemoveWhitespacesTransformer extends MappingTranformer
{
	// Covers ASCII whitespace plus non-breaking space, narrow no-break space and BOM,
	// which are the ones found in values pasted from spreadsheets or French formatted numbers.
	private const WHITESPACES_PATTERN = '/[\s\x{00A0}\x{202F}\x{FEFF}]+/u';

	public function __construct()
	{
		parent::__construct(MappingTransformersEnum::REMOVE_WHITESPACES);
	}

	public function transform(mixed $value): mixed
	{
		if (is_array($value))
		{
			return array_map([$this, 'transform'], $value);
		}

		if (!is_string($value))
		{
			return $value;
		}

		return preg_replace(self::WHITESPACES_PATTERN, '', $value) ?? $value;
	}
}
