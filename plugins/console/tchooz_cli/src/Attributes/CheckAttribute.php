<?php

namespace Emundus\Plugin\Console\Tchooz\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class CheckAttribute
{
	public function __construct(
		public ?string $description = null,
	) {
	}
}
