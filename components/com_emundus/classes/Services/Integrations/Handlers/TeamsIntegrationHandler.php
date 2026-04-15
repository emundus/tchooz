<?php
/**
 * @package     Tchooz\Services\Integrations\Handlers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Integrations\Handlers;

use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;

class TeamsIntegrationHandler extends AbstractIntegrationHandler
{

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

		$config['authentication']['route']         = 'https://login.microsoftonline.com/' . $setup->authentication->tenant_id . '/oauth2/v2.0/token';
		$config['authentication']['client_id']     = $setup->authentication->client_id;

		// If the client secret is not changed, keep the old one
		if (!empty($setup->authentication->client_secret) && strspn($setup->authentication->client_secret, '*') !== strlen($setup->authentication->client_secret))
		{
			$config['authentication']['client_secret'] = $this->encrypt($setup->authentication->client_secret);
		}

		$config['authentication']['tenant_id']     = $setup->authentication->tenant_id;
		$config['authentication']['email']         = $setup->authentication->email;

		$this->synchronizer->setConfig($config);
		$this->synchronizer->setEnabled(true);

		$repository = $repository ?? new SynchronizerRepository();

		return $repository->flush($this->synchronizer);
	}

	public function onAfterSetup(object $setup): bool
	{
		if (!class_exists('EmundusHelperUpdate'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
		}

		// Install event subscriber plugin
		\EmundusHelperUpdate::installExtension('plg_emundus_teams', 'teams', null, 'plugin', 1, 'emundus', '{}', false, false);

		// Test authentication
		if (!class_exists('EmundusModelSync'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
		}

		$m_sync = new \EmundusModelSync();

		return $m_sync->testAuthentication($this->synchronizer->getId());
	}
}