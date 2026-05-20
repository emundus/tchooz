<?php

namespace Tchooz\Services\Addons;

class AddonConfigurationRegistry
{
	// register all from directory /services/addons/configurations
	public function __construct()
	{
		$configurationFiles = glob(__DIR__ . '/Configurations/*.php');

		if ($configurationFiles) {
			foreach ($configurationFiles as $file) {
				require_once $file;

				$className = pathinfo($file, PATHINFO_FILENAME);
				$fullClassName = __NAMESPACE__ . '\\Configurations\\' . $className;
				if (class_exists($fullClassName)) {
					$instance = new $fullClassName();
					if ($instance instanceof EmundusAddonConfiguration) {
						$type = $className;
						if (str_ends_with($type, 'AddonConfiguration')) {
							$type = substr($type, 0, -strlen('AddonConfiguration'));
						}
						$type = strtolower($type);
						// remove IntegrationConfiguration suffix if exists

						$this->registerConfiguration($type, $instance);
					}
				}
			}
		}
	}

	/**
	 * @var array<string, EmundusAddonConfiguration>
	 */
	private array $configurations = [];

	public function registerConfiguration(string $type, EmundusAddonConfiguration $configuration): void
	{
		$this->configurations[$type] = $configuration;
	}

	public function getConfiguration(string $type): ?EmundusAddonConfiguration
	{
		$type = strtolower($type);
		$type = str_replace('_', '', $type);
		return $this->configurations[$type] ?? null;
	}

	public function getConfigurations(): array
	{
		return $this->configurations;
	}
}