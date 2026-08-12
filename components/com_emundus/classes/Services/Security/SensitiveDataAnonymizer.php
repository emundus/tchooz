<?php

namespace Tchooz\Services\Security;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Tchooz\Attributes\SensitiveData;
use Tchooz\Enums\Security\SensitiveDataStrategy;

/**
 * Runs an anonymising UPDATE against a table, reading which columns to
 * neutralise and how from the #[SensitiveData] attributes declared on the
 * entity class' properties. The property name is the SQL column name (project
 * convention).
 *
 * Owns the fake-value pools (first names, last names, email domain) so every
 * caller (this service, the tchooz:users_anonymize command for #__users /
 * #__emundus_users) points at the same source of truth.
 */
class SensitiveDataAnonymizer
{
	/**
	 * Non-routable domain (RFC 6761 ".invalid") used for fake emails: no mail
	 * can ever be delivered from a pre-prod platform to an anonymised address.
	 */
	public const FAKE_EMAIL_DOMAIN = 'anonymized.invalid';

	/**
	 * Fake first names pool. Picked deterministically from the row id so the
	 * same row always gets the same fake identity (re-runnable command).
	 *
	 * @var string[]
	 */
	public const FIRST_NAMES = [
		'Jean', 'Marie', 'Pierre', 'Sophie', 'Luc', 'Camille', 'Paul', 'Julie',
		'Thomas', 'Emma', 'Nicolas', 'Chloe', 'Antoine', 'Lea', 'Hugo', 'Manon',
	];

	/**
	 * Fake last names pool.
	 *
	 * @var string[]
	 */
	public const LAST_NAMES = [
		'Martin', 'Bernard', 'Dubois', 'Durand', 'Robert', 'Richard', 'Petit', 'Moreau',
		'Leroy', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Fontaine',
	];

	private DatabaseInterface $db;

	public function __construct(?DatabaseInterface $db = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
	}

	/**
	 * Scans $entityClass for #[SensitiveData] properties and runs a single
	 * UPDATE $tableName SET col1=<expr>, col2=<expr>, ... on every row.
	 *
	 * @param   class-string  $entityClass  Entity to introspect for #[SensitiveData] props.
	 * @param   string        $tableName    Target SQL table (Joomla-style '#__foo').
	 * @param   string        $idColumn     Primary key column name, used to seed the FAKE_* strategies.
	 *
	 * @return int  Number of rows updated. Zero if the entity declares no sensitive property.
	 */
	public function anonymise(string $entityClass, string $tableName, string $idColumn = 'id'): int
	{
		$setExpressions = $this->buildSetExpressions($entityClass, $idColumn);

		if (empty($setExpressions))
		{
			return 0;
		}

		$query = $this->db->createQuery()->update($this->db->quoteName($tableName));
		foreach ($setExpressions as $expression)
		{
			$query->set($expression);
		}

		$this->db->setQuery($query);
		$this->db->execute();

		return $this->db->getAffectedRows();
	}

	/**
	 * Reads every #[SensitiveData] property of $entityClass and returns the SQL
	 * SET fragments the anonymising UPDATE needs. Exposed (not private) so a
	 * caller doing a compound UPDATE with an extra WHERE (like the user
	 * candidates-only clause in TchoozAnonymizeUsersCommand) can splice the
	 * fragments into its own query builder.
	 *
	 * @param   class-string  $entityClass
	 * @param   string        $idColumn     Primary key column name (unquoted).
	 *
	 * @return string[]  Ready-to-use SET fragments, e.g. "`email` = CONCAT(...)".
	 */
	public function buildSetExpressions(string $entityClass, string $idColumn = 'id'): array
	{
		$fragments      = [];
		$idColumnQuoted = $this->db->quoteName($idColumn);

		foreach ((new \ReflectionClass($entityClass))->getProperties() as $property)
		{
			$attributes = $property->getAttributes(SensitiveData::class);
			if (empty($attributes))
			{
				continue;
			}

			$strategy = $attributes[0]->newInstance()->strategy;
			$column   = $property->getName();

			$fragments[] = $this->db->quoteName($column) . ' = ' . $this->expressionFor($strategy, $idColumnQuoted, $column);
		}

		return $fragments;
	}

	/**
	 * Renders the SQL right-hand side (the value expression) for a single
	 * SensitiveDataStrategy. Public so callers doing their own UPDATE with a
	 * custom WHERE (users / emundus_users candidates-only filter in the CLI
	 * command) can reuse the exact same generators without duplicating the
	 * fake-name pools.
	 *
	 * @param   SensitiveDataStrategy  $strategy
	 * @param   string                 $idColumnQuoted  Already quoted with quoteName().
	 * @param   string                 $columnName      Unquoted column name (used only in prefixed placeholders like UNIQUE_PLACEHOLDER).
	 *
	 * @return string
	 */
	public function expressionFor(SensitiveDataStrategy $strategy, string $idColumnQuoted, string $columnName = ''): string
	{
		return match ($strategy) {
			SensitiveDataStrategy::EMPTY_STRING           => "''",
			SensitiveDataStrategy::NULL_VALUE             => 'NULL',
			SensitiveDataStrategy::FAKE_FIRSTNAME         => $this->buildEltExpression(self::FIRST_NAMES, $idColumnQuoted),
			SensitiveDataStrategy::FAKE_LASTNAME          => $this->buildEltExpression(self::LAST_NAMES, 'FLOOR(' . $idColumnQuoted . ' / ' . count(self::FIRST_NAMES) . ')'),
			SensitiveDataStrategy::FAKE_EMAIL             => "CONCAT('anon_', " . $idColumnQuoted . ", '@" . self::FAKE_EMAIL_DOMAIN . "')",
			SensitiveDataStrategy::FAKE_ORGANIZATION_NAME => "CONCAT('Organisation ', " . $idColumnQuoted . ')',
			SensitiveDataStrategy::UNIQUE_PLACEHOLDER     => "CONCAT('anon_" . $columnName . "_', " . $idColumnQuoted . ')',
		};
	}

	/**
	 * Deterministic MySQL ELT() expression picking a value from $pool based on
	 * the given numeric seed (1-indexed, wrapped with MOD).
	 *
	 * @param   string[]  $pool
	 * @param   string    $seedExpression
	 *
	 * @return string
	 */
	private function buildEltExpression(array $pool, string $seedExpression): string
	{
		$quoted = array_map(fn(string $value): string => $this->db->quote($value), $pool);

		return 'ELT(1 + MOD(' . $seedExpression . ', ' . count($pool) . '), ' . implode(', ', $quoted) . ')';
	}
}
