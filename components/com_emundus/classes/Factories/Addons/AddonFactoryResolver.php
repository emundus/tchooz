<?php
/**
 * @package     Tchooz\Factories\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Addons;

use RuntimeException;

class AddonFactoryResolver
{
	protected string $basePath;

	protected ?string $namespacePrefix;

	public function __construct(string $basePath = null, string $namespacePrefix = null)
	{
		$this->basePath = $basePath ?? JPATH_SITE . '/components/com_emundus/classes/Factories/Addons/';

		$this->namespacePrefix = $namespacePrefix;
	}

	public function resolve(string $addonType, $addon): AddonFactoryInterface
	{
		$classBase = ucfirst($addonType) . 'AddonFactory';
		$fileName  = $this->basePath . $classBase . '.php';

		if (!file_exists($fileName)) {
			throw new RuntimeException("Factory file not found: {$fileName}");
		}

		require_once $fileName;

		$candidates = [$classBase];
		if ($this->namespacePrefix) {
			$candidates[] = '\\' . trim($this->namespacePrefix, '\\') . '\\' . $classBase;
		} else {
			$candidates[] = '\\Tchooz\\Factories\\Addons\\' . $classBase;
		}

		$classFound = null;
		foreach ($candidates as $fqcn) {
			if (class_exists($fqcn, false)) {
				$classFound = $fqcn;
				break;
			}
		}

		if (!$classFound) {
			throw new RuntimeException("Factory class {$classBase} not found in file {$fileName}. Tried: " . implode(', ', $candidates));
		}

		$instance = new $classFound($addon);
		if (!($instance instanceof AddonFactoryInterface)) {
			throw new RuntimeException("Factory {$classFound} must implement AddonFactoryInterface");
		}

		return $instance;
	}
}