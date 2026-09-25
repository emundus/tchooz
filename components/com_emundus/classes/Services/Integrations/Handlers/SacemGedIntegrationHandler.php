<?php

namespace Tchooz\Services\Integrations\Handlers;

use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use Tchooz\Services\SacemGed\SacemGedAuthenticator;

class SacemGedIntegrationHandler extends AbstractIntegrationHandler
{
	public function onActivate(): bool
	{
		return true;
	}

	public function onDeactivate(): bool
	{
		return true;
	}

	/**
	 * After saving the configuration, verify the credentials actually obtain a token. Any failure
	 * throws with a specific message (surfaced to the admin); success returns true.
	 *
	 * @throws \Exception
	 */
	public function onAfterSetup(object $setup): bool
	{
		SacemGedAuthenticator::fromSynchronizer($this->synchronizer)->getAccessToken();

		return true;
	}
}
