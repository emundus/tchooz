<?php
/**
 * @package     Tchooz\Services\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Integrations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Handlers\HandlerInterface;

abstract class AbstractIntegrationHandler implements HandlerInterface
{
	protected SynchronizerEntity $synchronizer;

	protected ?EmundusIntegrationConfiguration $configuration;

	public function __construct(
		SynchronizerEntity $synchronizer,
		?EmundusIntegrationConfiguration $configuration
	)
	{
		$this->synchronizer = $synchronizer;
		$this->configuration = $configuration;
	}

	/**
	 * Returns the list of Addons that this integration depends on.
	 * Override in concrete handlers to declare dependencies.
	 *
	 * @return AddonEnum[]
	 */
	public function getRequiredAddons(): array
	{
		return [];
	}

	/**
	 * Check that all required addons are activated.
	 *
	 * @param AddonRepository|null $addonRepository
	 *
	 * @return array{satisfied: bool, missing: AddonEnum[]}
	 */
	public function checkAddonDependencies(?AddonRepository $addonRepository = null): array
	{
		$requiredAddons = $this->getRequiredAddons();

		if (empty($requiredAddons))
		{
			return ['satisfied' => true, 'missing' => []];
		}

		$addonRepository = $addonRepository ?? new AddonRepository();
		$missing = [];

		foreach ($requiredAddons as $addonEnum)
		{
			$addon = $addonRepository->getByName($addonEnum->value);

			if (!$addon || !$addon->isActivated())
			{
				$missing[] = $addonEnum;
			}
		}

		return [
			'satisfied' => empty($missing),
			'missing'   => $missing,
		];
	}

	/**
	 * Save the integration configuration from user-provided setup data.
	 * The default implementation iterates over declared parameters (from the Configuration class),
	 * encrypts passwords, and persists the config via the SynchronizerRepository.
	 *
	 * Override in concrete handlers for custom setup logic.
	 *
	 * @param object                       $setup       The setup data from the frontend
	 * @param SynchronizerRepository|null  $repository  Optional repository (for testing)
	 *
	 * @return bool
	 */
	public function onSetup(object $setup, ?SynchronizerRepository $repository = null): bool
	{
		if (empty($this->configuration))
		{
			return false;
		}

		$config = $this->synchronizer->getConfig();

		foreach ($setup as $groupName => $group)
		{
			foreach ($group as $fieldName => $value)
			{
				$parameter = $this->configuration->getParameter($fieldName);

				if ($parameter === null)
				{
					continue;
				}

				if ($parameter->isRequired() && empty($value))
				{
					throw new \InvalidArgumentException(
						Text::sprintf('COM_EMUNDUS_SETTINGS_APP_PARAMETER_REQUIRED', $parameter->getName())
					);
				}

				if ($parameter instanceof PasswordField)
				{
					// If value is only asterisks, the user did not change it — skip
					if (preg_match('/^\*+$/', $value))
					{
						continue;
					}

					$value = $this->encrypt($value);
				}

				$config[$groupName][$fieldName] = $value;
			}
		}

		$this->synchronizer->setConfig($config);

		$repository = $repository ?? new SynchronizerRepository();

		return $repository->flush($this->synchronizer);
	}

	protected function encrypt(string $value): string
	{
		if (!class_exists('EmundusHelperFabrik'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
		}

		return \EmundusHelperFabrik::encryptDatas($value);
	}

	/**
	 * Hook executed after a successful onSetup().
	 * Use this for post-save actions like testing authentication, creating webhooks, etc.
	 *
	 * Override in concrete handlers for custom post-setup logic.
	 *
	 * @param object $setup  The original setup data from the frontend
	 *
	 * @return bool
	 */
	public function onAfterSetup(object $setup): bool
	{
		return true;
	}

	public function getSynchronizer(): SynchronizerEntity
	{
		return $this->synchronizer;
	}

	public function getConfiguration(): ?EmundusIntegrationConfiguration
	{
		return $this->configuration;
	}
}

