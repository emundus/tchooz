<?php
/**
 * @package     Tchooz\Controller
 *
 * @copyright   eMundus
 * @license     GNU/GPL
 */

declare(strict_types=1);

namespace Tchooz\Controller;

defined('_JEXEC') or die('ACCESS_DENIED');

/**
 * Resolves and instantiates the controller requested via the `controller` query parameter.
 *
 * Two locations are supported, in order of preference:
 *   1. `components/com_emundus/classes/Controller/<Name>Controller.php` (new, namespaced `Tchooz\Controller`)
 *   2. `components/com_emundus/controllers/<name>.php`                  (legacy, global `EmundusController<Name>`)
 *
 * When no controller is requested (or none is found), the legacy base `EmundusController`
 * is returned to preserve the previous behavior of `emundus.php`.
 */
final class EmundusControllerRegistry
{
	private const NAMESPACED_DIR = '/components/com_emundus/classes/Controller/';
	private const LEGACY_DIR     = '/components/com_emundus/controllers/';

	/**
	 * Resolve the controller for the given name.
	 *
	 * @param   string  $controllerName  The raw controller name (already filtered as WORD by the caller).
	 *
	 * @return  object  The instantiated controller (namespaced or legacy).
	 */
	public static function resolve(string $controllerName): object
	{
		if ($controllerName !== '')
		{
			$namespacedClass = self::resolveNamespaced($controllerName);
			if ($namespacedClass !== null)
			{
				return new $namespacedClass();
			}

			if (!self::loadLegacy($controllerName))
			{
				$controllerName = '';
			}
		}

		$legacyClass = 'EmundusController' . $controllerName;

		return new $legacyClass();
	}

	/**
	 * Try to resolve a controller from the namespaced `classes/Controller/` directory.
	 *
	 * @return  string|null  The fully qualified class name when found, null otherwise.
	 */
	private static function resolveNamespaced(string $controllerName): ?string
	{
		$className = ucfirst($controllerName) . 'Controller';
		$path      = JPATH_BASE . self::NAMESPACED_DIR . $className . '.php';

		if (!is_file($path))
		{
			return null;
		}

		$fqcn = __NAMESPACE__ . '\\' . $className;

		return class_exists($fqcn) ? $fqcn : null;
	}

	/**
	 * Try to require a legacy controller file.
	 */
	private static function loadLegacy(string $controllerName): bool
	{
		$path = JPATH_BASE . self::LEGACY_DIR . $controllerName . '.php';

		if (!is_file($path))
		{
			return false;
		}

		require_once $path;

		return true;
	}
}
