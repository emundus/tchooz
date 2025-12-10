<?php

namespace Tchooz\Services\Integrations;

class IntegrationConfigurationRegistry
{
	// register all from directory /services/integrations/configurations
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
					if ($instance instanceof EmundusIntegrationConfiguration) {
						$type = $className;
						if (str_ends_with($type, 'IntegrationConfiguration')) {
							$type = substr($type, 0, -strlen('IntegrationConfiguration'));
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
	 * @var array<string, EmundusIntegrationConfiguration>
	 */
	private array $configurations = [];

	public function registerConfiguration(string $type, EmundusIntegrationConfiguration $configuration): void
	{
		$this->configurations[$type] = $configuration;
	}

	public function getConfiguration(string $type): ?EmundusIntegrationConfiguration
	{
		return $this->configurations[$type] ?? null;
	}
}