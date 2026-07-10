<?php

namespace Tchooz\Attributes;

use Tchooz\Enums\Security\SensitiveDataStrategy;

/**
 * Marks an entity property as personal data that must be anonymised by the
 * tchooz:users_anonymize CLI command (post prod -> pre-prod copies).
 *
 * The property name is expected to match its SQL column name (project
 * convention across entities). If a future entity introduces a divergence, add
 * a $column argument here.
 *
 * The strategy tells SensitiveDataAnonymizer how to neutralise the stored
 * value (see SensitiveDataStrategy). Defaults to EMPTY_STRING - the safe
 * choice for any text column, nullable or not.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class SensitiveData
{
	public function __construct(
		public readonly SensitiveDataStrategy $strategy = SensitiveDataStrategy::EMPTY_STRING,
	) {}
}
