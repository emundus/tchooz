<?php
/**
 * @package     Tchooz\Services\Integrations\Handlers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Integrations\Handlers;

use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use function Sabre\Uri\parse;

class SogecommerceIntegrationHandler extends AbstractIntegrationHandler
{
	public function getRequiredAddons(): array
	{
		return [AddonEnum::PAYMENT];
	}

	public function onActivate(): bool
	{
		return true;
	}

	public function onDeactivate(): bool
	{
		return true;
	}

	public function onSetup(object $setup, ?SynchronizerRepository $repository = null): bool
	{
		parent::onSetup($setup, $repository);

		$config = $this->synchronizer->getConfig();

		$config_keys = ['authentication', 'configuration'];
		foreach ($config_keys as $key)
		{
			if (isset($setup->$key))
			{
				if ($key === 'authentication')
				{
					foreach ($setup->$key as $sub_key => $sub_value)
					{
						if (isset($config[$key][$sub_key]))
						{
							if ($sub_key === 'client_secret')
							{
								$config[$key][$sub_key] = $this->encrypt($sub_value);
							}
							else
							{
								$config[$key][$sub_key] = $sub_value;
							}
						}
					}
				}
				elseif($key === 'configuration')
				{
					foreach ($setup->$key as $sub_key => $sub_value)
					{
						$config[$sub_key] = $sub_value;
					}
				}
			}
		}

		$this->synchronizer->setConfig($config);
		$this->synchronizer->setEnabled(true);

		$repository = $repository ?? new SynchronizerRepository();

		return $repository->flush($this->synchronizer);
	}
}