<?php

namespace Tchooz\Services\Addons;

use RuntimeException;
use Tchooz\Services\Handlers\AbstractHandlerResolver;

class AddonHandlerResolver extends AbstractHandlerResolver
{
	public function __construct()
	{
		parent::__construct(JPATH_SITE . '/components/com_emundus/classes/Services/Addons/Handlers/', '\\Tchooz\\Services\\Addons\\Handlers\\');
	}

	public function resolve(string $addonType, $addon): AbstractAddonHandler
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
			$candidates[] = '\\Tchooz\\Services\\Addons\\Handlers\\' . $classBase;
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
		if (!($instance instanceof AbstractAddonHandler)) {
			throw new RuntimeException("Handler {$classFound} must extend AbstractAddonHandler");
		}

		return $instance;
	}

}