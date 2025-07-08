<?php
/**
 * @package     Tchooz\Entities\Emails
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Emails;

use Tchooz\Interfaces\TagModifierInterface;

class TagModifierRegistry
{
	/** @var TagModifierInterface[] */
	private static array $modifiers = [];

	public static function register(TagModifierInterface $modifier): void
	{
		self::$modifiers[strtoupper($modifier->getName())] = $modifier;
	}

	public static function get(string $name): ?TagModifierInterface
	{
		return self::$modifiers[$name] ?? null;
	}

	public static function all(): array
	{
		return self::$modifiers;
	}
}