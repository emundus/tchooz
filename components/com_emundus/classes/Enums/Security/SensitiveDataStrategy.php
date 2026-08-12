<?php

namespace Tchooz\Enums\Security;

/**
 * Anonymisation strategy for a property flagged with #[SensitiveData]. Each
 * case names a way to neutralise the stored value; the concrete SQL expression
 * is produced by SensitiveDataAnonymizer.
 */
enum SensitiveDataStrategy: string
{
	/** Empty string (''): default, safe for any nullable/NOT-NULL text column. */
	case EMPTY_STRING = 'empty';

	/** SQL NULL: use only on columns that are actually nullable in the schema. */
	case NULL_VALUE = 'null';

	/** Deterministic fake first name picked from a shared pool, seeded by the row id. */
	case FAKE_FIRSTNAME = 'fake_firstname';

	/** Deterministic fake last name picked from a shared pool, seeded by a shifted row id. */
	case FAKE_LASTNAME = 'fake_lastname';

	/** Fake email at a non-routable ".invalid" domain, seeded by the row id. */
	case FAKE_EMAIL = 'fake_email';

	/** Generic organisation label ("Organisation <id>"), unique per row. */
	case FAKE_ORGANIZATION_NAME = 'fake_org_name';

	/** Unique placeholder built from the row id ("anon_<id>"). Use when the schema requires a UNIQUE non-empty value. */
	case UNIQUE_PLACEHOLDER = 'unique_placeholder';
}
