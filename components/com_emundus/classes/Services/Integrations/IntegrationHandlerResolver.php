<?php

namespace Tchooz\Services\Integrations;

use RuntimeException;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Services\Handlers\AbstractHandlerResolver;

class IntegrationHandlerResolver extends AbstractHandlerResolver
{
	public function __construct()
	{
		parent::__construct(JPATH_SITE . '/components/com_emundus/classes/Services/Integrations/Handlers/', '\\Tchooz\\Services\\Integrations\\Handlers\\');
	}

	public function resolve(SynchronizerEntity $synchronizer): AbstractIntegrationHandler
	{
		$classBase = str_replace(' ', '', ucwords(str_replace('_', ' ', $synchronizer->getType()))) . 'IntegrationHandler';
		$fileName  = $this->basePath . $classBase . '.php';

		if (!file_exists($fileName))
		{
			throw new RuntimeException("Handler class {$classBase} not found");
		}

		require_once $fileName;

		$candidates   = [$classBase];
		$candidates[] = '\\' . trim($this->namespacePrefix, '\\') . '\\' . $classBase;

		$classFound = null;
		foreach ($candidates as $fqcn)
		{
			if (class_exists($fqcn, false))
			{
				$classFound = $fqcn;
				break;
			}
		}

		if (!$classFound)
		{
			throw new RuntimeException("Handler class {$classBase} not found in file {$fileName}. Tried: " . implode(', ', $candidates));
		}

		$integrationConfigurationRegistry = new IntegrationConfigurationRegistry();
		$configuration                    = $integrationConfigurationRegistry->getConfiguration($synchronizer->getType());

		$instance = new $classFound($synchronizer, $configuration);
		if (!($instance instanceof AbstractIntegrationHandler))
		{
			throw new RuntimeException("Handler {$classFound} must extend AbstractIntegrationHandler");
		}

		return $instance;
	}
}