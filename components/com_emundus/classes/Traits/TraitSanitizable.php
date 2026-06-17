<?php
/**
 * @package     Tchooz\Traits
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Traits;

use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Tchooz\Attributes\ORM\Sanitize;

trait TraitSanitizable
{
	/**
	 * @var array<class-string, array<string, Sanitize>>
	 */
	private static array $__sanitizeAttributeCache = [];

	/**
	 * Apply HtmlSanitizerSingleton to every property annotated with #[Sanitize].
	 *
	 * Properties that are uninitialised, null, or non-string are skipped.
	 *
	 * @return static
	 */
	public function sanitize(): static
	{
		$properties = self::loadSanitizeAttributes(static::class);
		if (empty($properties)) {
			return $this;
		}

		if (!class_exists('Component\\Emundus\\Helpers\\HtmlSanitizerSingleton')) {
			require_once JPATH_ROOT . '/components/com_emundus/helpers/html.php';
		}

		$sanitizer = HtmlSanitizerSingleton::getInstance();

		foreach ($properties as $name => $attribute) {
			$reflectionProperty = new \ReflectionProperty($this, $name);
			$reflectionProperty->setAccessible(true);

			if (!$reflectionProperty->isInitialized($this)) {
				continue;
			}

			$value = $reflectionProperty->getValue($this);
			if (!is_string($value) || $value === '') {
				continue;
			}

			$reflectionProperty->setValue($this, self::applySanitize($sanitizer, $attribute, $value));
		}

		return $this;
	}

	private static function applySanitize(HtmlSanitizerSingleton $sanitizer, Sanitize $attribute, string $value): string
	{
		return match ($attribute->mode) {
			Sanitize::MODE_NO_HTML => $sanitizer->sanitizeNoHtml($value),
			Sanitize::MODE_FOR     => !empty($attribute->section)
				? $sanitizer->sanitizeFor($attribute->section, $value)
				: $sanitizer->sanitize($value),
			default                => $sanitizer->sanitize($value),
		};
	}

	/**
	 * @return array<string, Sanitize>
	 */
	private static function loadSanitizeAttributes(string $class): array
	{
		if (isset(self::$__sanitizeAttributeCache[$class])) {
			return self::$__sanitizeAttributeCache[$class];
		}

		$properties = [];

		try {
			$reflection = new \ReflectionClass($class);
			foreach ($reflection->getProperties() as $property) {
				$attributes = $property->getAttributes(Sanitize::class);
				if (empty($attributes)) {
					continue;
				}

				$properties[$property->getName()] = $attributes[0]->newInstance();
			}
		} catch (\ReflectionException $e) {
			// Cache the empty result below so we do not retry on every call.
		}

		self::$__sanitizeAttributeCache[$class] = $properties;

		return $properties;
	}
}
