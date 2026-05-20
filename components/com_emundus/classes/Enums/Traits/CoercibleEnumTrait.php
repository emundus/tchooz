<?php
/**
 * @package     Tchooz\Enums\Traits
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Traits;

/**
 * Permet à un enum string-backed d'accepter indifféremment :
 *   - une instance du case (ex: ButtonVariantEnum::PRIMARY)
 *   - une string correspondant à une valeur du case (ex: 'primary')
 *   - une valeur invalide ou null → retourne le défaut fourni
 */
trait CoercibleEnumTrait
{
	public static function coerce(mixed $value, self $default): self
	{
		if ($value instanceof self)
		{
			return $value;
		}

		if (is_string($value))
		{
			return static::tryFrom($value) ?? $default;
		}

		return $default;
	}
}
