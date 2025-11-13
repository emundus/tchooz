<?php

namespace Tchooz\Services\Addons;

use RuntimeException;

class AddonHandlerResolver
{
	protected string $basePath;

	protected ?string $namespacePrefix;

	public function __construct(string $basePath = null, string $namespacePrefix = null)
	{
		$this->basePath = $basePath ?? JPATH_SITE . '/components/com_emundus/classes/Services/Addons/';

		$this->namespacePrefix = $namespacePrefix;
	}

	public function resolve(string $addonType, $addon): AddonHandlerInterface
	{
		$classBase = str_replace(' ', '', ucwords(str_replace('_', ' ', $addonType))) . 'AddonHandler';
		$fileName  = $this->basePath . $classBase . '.php';

		if (!file_exists($fileName)) {
			throw new RuntimeException("Factory file not found: {$fileName}");
		}

		require_once $fileName;

		$candidates = [$classBase];
		if ($this->namespacePrefix) {
			$candidates[] = '\\' . trim($this->namespacePrefix, '\\') . '\\' . $classBase;
		} else {
			$candidates[] = '\\Tchooz\\Services\\Addons\\' . $classBase;
		}

		$classFound = null;
		foreach ($candidates as $fqcn) {
			if (class_exists($fqcn, false)) {
				$classFound = $fqcn;
				break;
			}
		}

		if (!$classFound) {
			throw new RuntimeException("Handler class {$classBase} not found in file {$fileName}. Tried: " . implode(', ', $candidates));
		}

		$instance = new $classFound($addon);
		if (!($instance instanceof AddonHandlerInterface)) {
			throw new RuntimeException("Handler {$classFound} must implement AddonHandlerInterface");
		}

		return $instance;
	}

}