<?php
/**
 * @package     Tchooz\Services\Integrations\Handlers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Integrations\Handlers;

use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use Tchooz\Services\Sofis\SofisAuthenticator;

class SofisIntegrationHandler extends AbstractIntegrationHandler
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
	 * After saving the configuration, verify the full two-step authentication handshake: obtain the
	 * IDAMA token from the authorization server, then exchange it for an Azure AD token. Any failure
	 * throws with a specific message (surfaced to the admin); success returns true.
	 *
	 * @throws \Exception
	 */
	public function onAfterSetup(object $setup): bool
	{
		SofisAuthenticator::fromSynchronizer($this->synchronizer)->getAccessToken();

		return true;
	}
}
