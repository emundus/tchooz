<?php
/**
 * @package     Tchooz\Entities\Emails
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Emails;

use Tchooz\Entities\Emails\Modifiers\CapitalizeModifier;
use Tchooz\Entities\Emails\Modifiers\ChoiceStatusModifier;
use Tchooz\Entities\Emails\Modifiers\IndexModifier;
use Tchooz\Entities\Emails\Modifiers\LettersModifier;
use Tchooz\Entities\Emails\Modifiers\LowercaseModifier;
use Tchooz\Entities\Emails\Modifiers\NumberModifier;
use Tchooz\Entities\Emails\Modifiers\TrimModifier;
use Tchooz\Entities\Emails\Modifiers\UppercaseModifier;
use Tchooz\Interfaces\TagModifierInterface;

class TagModifierRegistry
{
	/** @var TagModifierInterface[] */
	private static array $modifiers = [];

	private static bool $defaultsRegistered = false;

	public static function register(TagModifierInterface $modifier): void
	{
		self::$modifiers[strtoupper($modifier->getName())] = $modifier;
	}

	public static function registerDefaults(): void
	{
		if (self::$defaultsRegistered)
		{
			return;
		}

		self::register(new UppercaseModifier());
		self::register(new LowercaseModifier());
		self::register(new CapitalizeModifier());
		self::register(new TrimModifier());
		self::register(new LettersModifier());
		self::register(new NumberModifier());
		self::register(new ChoiceStatusModifier());
		self::register(new IndexModifier());

		self::$defaultsRegistered = true;
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