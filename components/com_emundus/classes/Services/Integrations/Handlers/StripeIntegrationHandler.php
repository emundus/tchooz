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

class StripeIntegrationHandler extends AbstractIntegrationHandler
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
					'client_secret'  => isset($setup->authentication->client_secret) ? $this->encrypt($setup->authentication->client_secret) : '',
					'webhook_secret' => isset($setup->authentication->webhook_secret) ? $this->encrypt($setup->authentication->webhook_secret) : '',
				]
			];
		}
		else
		{
			if (isset($setup->authentication->client_secret))
			{
				$config['authentication']['client_secret'] = $this->encrypt($setup->authentication->client_secret);
			}

			if (isset($setup->authentication->webhook_secret))
			{
				$config['authentication']['webhook_secret'] = $this->encrypt($setup->authentication->webhook_secret);
			}
		}

		$this->synchronizer->setConfig($config);
		$this->synchronizer->setEnabled(true);

		$repository = $repository ?? new SynchronizerRepository();

		return $repository->flush($this->synchronizer);
	}
}