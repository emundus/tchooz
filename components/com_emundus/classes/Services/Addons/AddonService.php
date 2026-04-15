<?php
/**
 * @package     Tchooz\Services\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Addons;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Addons\AddonStatus;
use Tchooz\Repositories\Addons\AddonRepository;

class AddonService
{
	private AddonRepository $repository;

	private AddonHandlerResolver $resolver;

	public function __construct(?AddonRepository $repository = null, ?AddonHandlerResolver $resolver = null)
	{
		$this->repository = $repository ?? new AddonRepository();
		$this->resolver   = $resolver ?? new AddonHandlerResolver();
	}

	public function activate(string $namekey): bool
	{
		$addon = $this->repository->getByName($namekey);

		if (!$addon)
		{
			Log::add("AddonService::activate — addon '{$namekey}' not found", Log::WARNING, 'com_emundus.addon');

			return false;
		}

		$alreadyActivated = $addon->isActivated();

		// Already activated
		if ($alreadyActivated)
		{
			return true;
		}

		// Mettre à jour le statut
		$addon->setActivated(true);
		$addon->setActivatedAt(new \DateTimeImmutable());

		if (!$this->repository->flush($addon))
		{
			return false;
		}

		// Execute the handler if exists, only if it was not already activated (to avoid executing onActivate multiple times)
		return $this->executeHandler($namekey, $addon, true);
	}

	public function deactivate(string $namekey): bool
	{
		$addon = $this->repository->getByName($namekey);

		if (!$addon)
		{
			Log::add("AddonService::deactivate — addon '{$namekey}' not found", Log::WARNING, 'com_emundus.addon');

			return false;
		}

		// Already deactivated
		if (!$addon->isActivated())
		{
			return true;
		}

		$addon->setActivated(false);
		if (!$this->repository->flush($addon))
		{
			return false;
		}

		// Execute handler if exists
		return $this->executeHandler($namekey, $addon, false);
	}

	public function hide(string $namekey): bool
	{
		$addon = $this->repository->getByName($namekey);

		if (!$addon)
		{
			return false;
		}

		// Already hidden
		if (!$addon->isDisplayed())
		{
			return true;
		}

		$addon->setDisplayed(false);
		if (!$this->repository->flush($addon))
		{
			return false;
		}

		return true;
	}

	public function show(string $namekey): bool
	{
		$addon = $this->repository->getByName($namekey);

		if (!$addon)
		{
			return false;
		}

		// Already visible
		if ($addon->isDisplayed() && !$addon->isSuggested())
		{
			return true;
		}

		$addon->setDisplayed(true);
		$addon->setSuggested(false);
		if (!$this->repository->flush($addon))
		{
			return false;
		}

		return true;
	}

	public function removeSuggest(string $namekey): bool
	{
		$addon = $this->repository->getByName($namekey);

		if (!$addon)
		{
			return false;
		}

		// Already hidden
		if (!$addon->isSuggested())
		{
			return true;
		}

		$addon->setSuggested(false);
		if (!$this->repository->flush($addon))
		{
			return false;
		}

		return true;
	}

	public function suggest(string $namekey): bool
	{
		$addon = $this->repository->getByName($namekey);

		if (!$addon)
		{
			return false;
		}

		// Already visible
		if ($addon->isSuggested())
		{
			return true;
		}

		$addon->setSuggested(true);
		if (!$this->repository->flush($addon))
		{
			return false;
		}

		return true;
	}

	/**
	 * @deprecated Utiliser activate()/deactivate()
	 * Rétro-compatibilité avec l'ancien toggle(type, enabled).
	 */
	public function toggle(string $namekey, bool $enabled): bool
	{
		if ($enabled)
		{
			return $this->activate($namekey);
		}

		return $this->deactivate($namekey);
	}

	/**
	 * Retourne tous les addons visibles
	 *
	 * @return AddonEntity[]
	 */
	public function getAddons(): array
	{
		return $this->repository->get();
	}

	/**
	 * Retourne tous les addons visibles
	 *
	 * @return AddonEntity[]
	 */
	public function getVisibleAddons(): array
	{
		return $this->repository->getItemsByFields(['displayed' => 1, 'suggested' => 1], true, 'OR');
	}

	/**
	 * Retourne un addon par sa clé.
	 */
	public function getAddon(string $namekey): ?AddonEntity
	{
		return $this->repository->getByName($namekey);
	}

	/**
	 * Execute the handler for the given addon if exists.
	 *
	 * @param string      $namekey
	 * @param AddonEntity $addon
	 * @param bool        $activating  true = onActivate, false = onDeactivate
	 *
	 * @return bool
	 */
	private function executeHandler(string $namekey, AddonEntity $addon, bool $activating): bool
	{
		try
		{
			$handler = $this->resolver->resolve($namekey, $addon);

			if ($activating)
			{
				$result = $handler->onActivate($addon);
			}
			else
			{
				$result = $handler->onDeactivate($addon);
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
			// Pas de handler pour cet addon — ce n'est pas une erreur,
			// certains addons n'ont pas besoin de handler.
			Log::add("AddonService — no handler for '{$namekey}': " . $e->getMessage(), Log::DEBUG, 'com_emundus.addon');

			return true;
		}

		return $result;
	}
}

