<?php
namespace Tchooz\Services\Security;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Exception\RateLimitExceededException;

defined('_JEXEC') or die;

/**
 * Generic, multi-window rate limiter backed by Joomla's cache.
 *
 * The cache (not the session) is used so the limiter works for guest users,
 * for whom sessions are unreliable. A limiter instance is scoped to a single
 * "bucket" (a string identifier such as an IP hash); callers record actions
 * and the limiter enforces a set of time windows against the recorded
 * timestamps.
 *
 * This class is deliberately free of any knowledge about public applications,
 * IPs or campaigns: it just enforces "at most N actions within W seconds for
 * this bucket". That makes it reusable for any sensitive endpoint.
 *
 * NOTE ON ATOMICITY: the read-modify-write around the cache is not atomic, so
 * tightly concurrent requests can in theory slip a small number past a window.
 * The short cooldown window mitigates this in practice; if strict atomicity is
 * ever required, back the counters with a DB row lock instead.
 *
 * @since 1.0.0
 */
class RateLimiter
{
	/**
	 * Cache group under which all rate-limit buckets are stored.
	 */
	private const CACHE_GROUP = 'com_emundus_ratelimit';

	/**
	 * Longest retention; individual timestamps are purged per-window.
	 */
	private const CACHE_LIFETIME = 86400;

	/**
	 * The cache controller used to persist timestamp lists.
	 *
	 * @var \Joomla\CMS\Cache\Controller\OutputController
	 */
	private $cache;

	public function __construct()
	{
		$this->cache = Factory::getContainer()
			->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', [
				'defaultgroup' => self::CACHE_GROUP,
				'lifetime'     => self::CACHE_LIFETIME,
			]);
	}

	/**
	 * Enforce a cooldown: minimum seconds between two consecutive actions.
	 *
	 * @param   string  $bucket             Bucket identifier (e.g. IP hash).
	 * @param   int     $cooldownSeconds    Minimum gap required.
	 * @param   string  $languageKey        Localised message key; receives the
	 *                                       remaining seconds via sprintf.
	 *
	 * @return  void
	 *
	 * @throws  RateLimitExceededException
	 *
	 * @since   1.0.0
	 */
	public function enforceCooldown(string $bucket, int $cooldownSeconds, string $languageKey): void
	{
		$timestamps = $this->getTimestamps($bucket);

		if (empty($timestamps))
		{
			return;
		}

		$now  = time();
		$last = end($timestamps);

		if (($now - $last) < $cooldownSeconds)
		{
			$remaining = $cooldownSeconds - ($now - $last);

			throw new RateLimitExceededException(Text::sprintf($languageKey, $remaining));
		}
	}

	/**
	 * Enforce "at most $max actions within the last $windowSeconds".
	 *
	 * @param   string  $bucket          Bucket identifier (e.g. IP hash).
	 * @param   int     $max             Maximum allowed actions in the window.
	 * @param   int     $windowSeconds   Window length in seconds.
	 * @param   string  $languageKey     Localised message key for rejection.
	 *
	 * @return  void
	 *
	 * @throws  RateLimitExceededException
	 *
	 * @since   1.0.0
	 */
	public function enforceWindow(string $bucket, int $max, int $windowSeconds, string $languageKey): void
	{
		$timestamps = $this->getTimestamps($bucket);
		$now        = time();

		$inWindow = array_filter($timestamps, static function ($ts) use ($now, $windowSeconds) {
			return $ts > ($now - $windowSeconds);
		});

		if (count($inWindow) >= $max)
		{
			throw new RateLimitExceededException(Text::_($languageKey));
		}
	}

	/**
	 * Record that an action just happened for this bucket.
	 *
	 * Appends the current timestamp and persists the (purged) list. Call this
	 * once after all windows for the bucket have passed.
	 *
	 * @param   string  $bucket  Bucket identifier.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function record(string $bucket): void
	{
		$timestamps   = $this->getTimestamps($bucket);
		$timestamps[] = time();

		$this->cache->store(array_values($timestamps), $this->key($bucket));
	}

	/**
	 * Fetch the (24h-purged) timestamp list for a bucket.
	 *
	 * @param   string  $bucket  Bucket identifier.
	 *
	 * @return  array<int>
	 *
	 * @since   1.0.0
	 */
	private function getTimestamps(string $bucket): array
	{
		$timestamps = $this->cache->get($this->key($bucket));

		if (!is_array($timestamps))
		{
			return [];
		}

		$now = time();

		return array_values(array_filter($timestamps, static function ($ts) use ($now) {
			return $ts > ($now - self::CACHE_LIFETIME);
		}));
	}

	/**
	 * Build the cache key for a bucket.
	 *
	 * @param   string  $bucket  Bucket identifier.
	 *
	 * @return  string
	 *
	 * @since   1.0.0
	 */
	private function key(string $bucket): string
	{
		return 'rl_' . $bucket;
	}
}