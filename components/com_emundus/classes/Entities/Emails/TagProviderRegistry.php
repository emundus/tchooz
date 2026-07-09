<?php
/**
 * @package     Tchooz\Entities\Emails
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Emails;

use Tchooz\Entities\Emails\Providers\TransactionTagProvider;
use Tchooz\Interfaces\TagProviderInterface;

/**
 * Registry of constant email tag providers.
 *
 * Mirrors {@see TagModifierRegistry}: providers register here once, and the
 * email model iterates them instead of hard-coding each tag. New tags are added
 * by registering a provider, never by editing the consumer.
 */
class TagProviderRegistry
{
	/** @var TagProviderInterface[] */
	private static array $providers = [];

	private static bool $defaultsRegistered = false;

	public static function register(TagProviderInterface $provider): void
	{
		self::$providers[strtolower($provider->getName())] = $provider;
	}

	public static function registerDefaults(): void
	{
		if (self::$defaultsRegistered)
		{
			return;
		}

		self::register(new TransactionTagProvider());

		self::$defaultsRegistered = true;
	}

	public static function get(string $name): ?TagProviderInterface
	{
		return self::$providers[strtolower($name)] ?? null;
	}

	/**
	 * @return TagProviderInterface[]
	 */
	public static function all(): array
	{
		self::registerDefaults();

		return self::$providers;
	}
}
