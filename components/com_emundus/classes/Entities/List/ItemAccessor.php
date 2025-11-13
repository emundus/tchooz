<?php
/**
 * @package     Tchooz\Entities\List
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\List;

class ItemAccessor
{

	public static function getAccessorValue(mixed $item, string $key, mixed $default = null): mixed
	{
		if ($key === '') {
			return $default;
		}

		// arrays
		if (is_array($item)) {
			if (array_key_exists($key, $item)) {
				return $item[$key];
			}
			$camel = self::toCamel($key);
			if (array_key_exists($camel, $item)) {
				return $item[$camel];
			}
			return $default;
		}

		// objects (class instances)
		if (is_object($item)) {
			foreach (self::methodCandidates($key) as $m) {
				if (method_exists($item, $m) && is_callable([$item, $m])) {
					$rm = new \ReflectionMethod($item, $m);
					if ($rm->isPublic()) {
						return $item->$m();
					}
				}
			}

			// try public property (direct)
			if (property_exists($item, $key)) {
				$rp = new \ReflectionProperty($item, $key);
				if ($rp->isPublic()) {
					return $item->$key;
				}
			}

			// try camel-case property
			$camel = self::toCamel($key);
			if ($camel !== $key && property_exists($item, $camel)) {
				$rp = new \ReflectionProperty($item, $camel);
				if ($rp->isPublic()) {
					return $item->$camel;
				}
			}

			// as last attempt, try magic getter __get if defined and public
			if (method_exists($item, '__get')) {
				try {
					return $item->$key;
				} catch (\Throwable $e) {
					// ignore, fallthrough to default
				}
			}
		}

		return $default;
	}

	private static function methodCandidates(string $key): array
	{
		$camel = self::toCamel($key);
		$uc = ucfirst($camel);

		return [
			'get' . $uc,
			'is' . $uc,
			'has' . $uc,
			$camel,
		];
	}

	private static function toCamel(string $key): string
	{
		$key = str_replace(['-', '.'], '_', $key);
		$parts = explode('_', $key);
		if (count($parts) === 1) {
			return $key;
		}
		$first = array_shift($parts);
		$rest = array_map(fn($p) => ucfirst($p), $parts);
		return $first . implode('', $rest);
	}
}