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

class WorldlineIntegrationHandler extends AbstractIntegrationHandler
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
		$config = $this->synchronizer->getConfig();

		if (empty($config))
		{
			$config = [
				'authentication' => [
					'mode'           => $setup->authentication->mode ?? 0,
					'merchant_id'    => $setup->authentication->merchant_id ?? '',
					'api_key_id'     => $setup->authentication->api_key_id ?? '',
					'api_secret'     => isset($setup->authentication->api_secret) ? $this->encrypt($setup->authentication->api_secret) : '',
					'webhook_key_id' => $setup->authentication->webhook_key_id ?? '',
					'webhook_secret' => isset($setup->authentication->webhook_secret) ? $this->encrypt($setup->authentication->webhook_secret) : '',
				]
			];
		}
		else
		{
			foreach (['mode', 'merchant_id', 'api_key_id', 'webhook_key_id'] as $key)
			{
				if (isset($setup->authentication->$key))
				{
					$config['authentication'][$key] = $setup->authentication->$key;
				}
			}

			foreach (['api_secret', 'webhook_secret'] as $key)
			{
				if (isset($setup->authentication->$key))
				{
					$config['authentication'][$key] = $this->encrypt($setup->authentication->$key);
				}
			}
		}

		$this->synchronizer->setConfig($config);
		$this->synchronizer->setEnabled(true);

		$repository = $repository ?? new SynchronizerRepository();

		return $repository->flush($this->synchronizer);
	}
}
