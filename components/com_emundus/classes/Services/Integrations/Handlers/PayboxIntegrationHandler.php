<?php

namespace Tchooz\Services\Integrations\Handlers;

use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;

class PayboxIntegrationHandler extends AbstractIntegrationHandler
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
		$this->synchronizer->setEnabled(true);

		return parent::onSetup($setup, $repository);
	}
}