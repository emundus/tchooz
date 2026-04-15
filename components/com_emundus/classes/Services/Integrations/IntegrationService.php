<?php
/**
 * @package     Tchooz\Services\Integrations
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Integrations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

class IntegrationService
{
	private SynchronizerRepository $repository;

	private IntegrationHandlerResolver $resolver;

	public function __construct(?SynchronizerRepository $repository = null, ?IntegrationHandlerResolver $resolver = null)
	{
		$this->repository = $repository ?? new SynchronizerRepository();
		$this->resolver   = $resolver ?? new IntegrationHandlerResolver();
	}

	public function activate(int $id): bool
	{
		$synchronizer = $this->repository->getById($id);
		if(!$synchronizer)
		{
			return false;
		}

		if($synchronizer->isEnabled())
		{
			return true;
		}

		if(!$this->executeHandler($synchronizer, true))
		{
			return false;
		}

		$synchronizer->setEnabled(true);
		return $this->repository->flush($synchronizer);
	}

	public function deactivate(int $id): bool
	{
		$synchronizer = $this->repository->getById($id);
		if(!$synchronizer)
		{
			return false;
		}

		if(!$synchronizer->isEnabled())
		{
			return true;
		}

		if(!$this->executeHandler($synchronizer, false))
		{
			return false;
		}

		$synchronizer->setEnabled(false);
		return $this->repository->flush($synchronizer);
	}

	public function getParameters(SynchronizerEntity $synchronizer): array
	{
		$handler = $this->resolver->resolve($synchronizer);

		return $handler->getConfiguration()?->getParameters() ?? [];
	}

	/**
	 * Save the integration configuration, then run post-setup actions.
	 *
	 * @param int    $id     The synchronizer ID
	 * @param object $setup  The setup data from the frontend
	 *
	 * @return bool
	 */
	public function setup(int $id, object $setup): bool
	{
		$synchronizer = $this->repository->getById($id);
		if (!$synchronizer)
		{
			throw new \RuntimeException('Synchronizer not found');
		}

		$handler = $this->resolver->resolve($synchronizer);

		// 1. Save the configuration
		if (!$handler->onSetup($setup, $this->repository))
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_APP_SETUP_FAILED'));
		}

		// 2. Post-setup hook (e.g. test authentication, create webhooks, etc.)
		if (!$handler->onAfterSetup($setup))
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_APP_SETUP_FAILED'));
		}

		return true;
	}

	/**
	 * Returns the list of missing (not activated) addon dependencies for a given integration.
	 *
	 * @param int $id  The synchronizer ID
	 *
	 * @return AddonEnum[]  List of missing addons, empty if all dependencies are satisfied
	 */
	public function getMissingAddons(int $id): array
	{
		$synchronizer = $this->repository->getById($id);
		if (!$synchronizer)
		{
			return [];
		}

		try
		{
			$handler = $this->resolver->resolve($synchronizer);
			$result = $handler->checkAddonDependencies();

			return $result['missing'];
		}
		catch (\Exception $e)
		{
			return [];
		}
	}

	private function executeHandler(SynchronizerEntity $synchronizer, bool $enabled): bool
	{
		try
		{
			$handler = $this->resolver->resolve($synchronizer);

			if($enabled)
			{
				// Check addon dependencies before activation
				$dependencyCheck = $handler->checkAddonDependencies();
				if (!$dependencyCheck['satisfied'])
				{
					$missingNames = array_map(fn(AddonEnum $addon) => $addon->getLabel(), $dependencyCheck['missing']);
					$message = 'COM_EMUNDUS_SETTINGS_APP_MISSING_ADDON';
					if(sizeof($missingNames) > 1)
					{
						$message = 'COM_EMUNDUS_SETTINGS_APP_MISSING_ADDONS';
					}
					throw new \RuntimeException(
						Text::sprintf($message, implode(', ', $missingNames))
					);
				}

				$result = $handler->onActivate();

				// Check if we have to init a configuration
				$parameters = $handler->getConfiguration()?->getParameters();
				$defaultParameters = $handler->getConfiguration()?->getDefaultParameters() ?? [];
				if(!empty($parameters))
				{
					$result = $this->initConfiguration($synchronizer, $parameters, $defaultParameters);
				}
			}
			else
			{
				$result = $handler->onDeactivate();
			}

			// Clear some cache
			if(!class_exists('EmundusHelperCache'))
			{
				require_once JPATH_SITE.'/components/com_emundus/helpers/cache.php';
			}
			$hCache = new \EmundusHelperCache();
			$hCache->clean(false, ['com_emundus', 'com_emundus.addon', 'com_emundus.action', 'com_emundus.menus', 'com_menus', 'mod_menu']);
		}
		catch (\RuntimeException $e)
		{
			throw $e;
		}
		catch (\Exception $e)
		{
			return false;
		}

		return $result;
	}

	private function initConfiguration(SynchronizerEntity $synchronizer, array $parameters, array $defaultParameters = []): bool
	{
		$configuration = empty($synchronizer->getConfig()) ? $defaultParameters : $synchronizer->getConfig();

		foreach ($parameters as $parameter)
		{
			assert($parameter instanceof Field);

			$key = $parameter->getGroup()?->getName() ?? null;
			if(!empty($key))
			{
				if (!isset($configuration[$parameter->getGroup()->getName()]))
				{
					$configuration[$parameter->getGroup()->getName()] = [];
				}

				if(!isset($configuration[$parameter->getGroup()->getName()][$parameter->getName()]))
				{
					$configuration[$parameter->getGroup()->getName()][$parameter->getName()] = '';
				}
			}
			else {
				if(!isset($configuration[$parameter->getName()]))
				{
					$configuration[$parameter->getName()] = '';
				}
			}
		}

		$synchronizer->setConfig($configuration);
		return $this->repository->flush($synchronizer);
	}
}