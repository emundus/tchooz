<?php

namespace Joomla\Component\Emundus\Administrator\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class PostflightAttribute
{
	public function __construct(
		public string $name,
	) {
	}
}
