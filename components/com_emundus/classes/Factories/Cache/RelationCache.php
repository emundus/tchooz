<?php
/**
 * @package     Tchooz\Factories\Cache
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Cache;

/**
 * In-memory static cache (Identity Map) to avoid reloading
 * the same relation objects when processing collections.
 *
 * Example: 100 files share the same campaign → just 1 DB call.
 *
 * The cache is scoped by "namespace" (e.g. "campaign", "status", "program")
 * and indexed by key (e.g. the ID or a composite identifier).
 *
 * The lifecycle is that of the PHP request (static).
 */
final class RelationCache
{
	/** @var array<string, array<string, mixed>> */
	private static array $cache = [];

	/**
	 * Checks whether an entry exists in the cache.
	 */
	public static function has(string $namespace, string|int $key): bool
	{
		return array_key_exists($namespace, self::$cache)
			&& array_key_exists($key, self::$cache[$namespace]);
	}

	/**
	 * Retrieves an entry from the cache.
	 */
	public static function get(string $namespace, string|int $key): mixed
	{
		return self::$cache[$namespace][$key] ?? null;
	}

	/**
	 * Stores an entry in the cache.
	 */
	public static function set(string $namespace, string|int $key, mixed $value): void
	{
		self::$cache[$namespace][$key] = $value;
	}

	/**
	 * Retrieves an entry from the cache or executes the callback to create it.
	 * "remember" pattern: avoids duplication of has/get/set logic.
	 */
	public static function remember(string $namespace, string|int $key, callable $callback): mixed
	{
		if (self::has($namespace, $key)) {
			return self::get($namespace, $key);
		}

		$value = $callback();
		self::set($namespace, $key, $value);

		return $value;
	}

	/**
	 * Clears the cache for a given namespace.
	 */
	public static function forget(string $namespace): void
	{
		unset(self::$cache[$namespace]);
	}

	/**
	 * Clears the entire static cache (useful for testing).
	 */
	public static function flush(): void
	{
		self::$cache = [];
	}

	/**
	 * Preloads a set of entries into the cache.
	 * Useful for performing a batch load before processing a collection.
	 *
	 * @param string $namespace
	 * @param array<string|int, mixed> $entries [key => value, ...]
	 */
	public static function preload(string $namespace, array $entries): void
	{
		foreach ($entries as $key => $value) {
			self::$cache[$namespace][$key] = $value;
		}
	}

	/**
	 * Returns the cache statistics (useful for debugging).
	 *
	 * @return array<string, int> [namespace => count]
	 */
	public static function stats(): array
	{
		$stats = [];
		foreach (self::$cache as $namespace => $entries) {
			$stats[$namespace] = count($entries);
		}

		return $stats;
	}
}

