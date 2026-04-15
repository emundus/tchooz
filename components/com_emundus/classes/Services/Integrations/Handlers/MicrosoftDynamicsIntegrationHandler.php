<?php
/**
 * @package     Tchooz\Services\Integrations\Handlers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Integrations\Handlers;

use Joomla\CMS\Factory;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;

class MicrosoftDynamicsIntegrationHandler extends AbstractIntegrationHandler
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

		$config['base_url']                    = $setup->authentication->domain;
		$config['authentication']['client_id'] = $setup->authentication->client_id;

		// If the client secret is not changed, keep the old one
		if (!empty($setup->authentication->client_secret) && empty(preg_match('/^\*+$/', $setup->authentication->client_secret)))
		{
			$config['authentication']['client_secret'] = $this->encrypt($setup->authentication->client_secret);
		}

		$config['authentication']['tenant_id']    = $setup->authentication->tenant_id;
		$config['authentication']['grant_type']   = 'client_credentials';
		$config['authentication']['content_type'] = 'form_params';
		$config['authentication']['scope']        = $setup->authentication->domain . '/.default';
		$config['authentication']['domain']       = $setup->authentication->domain;
		$config['authentication']['route']        = 'https://login.microsoftonline.com/' . $setup->authentication->tenant_id . '/oauth2/v2.0/token';

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

		$db = Factory::getContainer()->get('DatabaseDriver');

		// Ensure entities table exists
		$query = 'SHOW TABLES LIKE ' . $db->quote('data_microsoft_dynamics_entities');
		$db->setQuery($query);
		$table_exists = $db->loadResult();

		if (empty($table_exists))
		{
			$columns = [
				['name' => 'collectionname', 'type' => 'VARCHAR', 'length' => 255, 'null' => 0],
				['name' => 'name', 'type' => 'VARCHAR', 'length' => 255, 'null' => 0],
				['name' => 'entityid', 'type' => 'VARCHAR', 'length' => 255, 'null' => 0]
			];
			$comment = 'This table is used to store all entities for Microsoft Dynamics integration';
			$created = \EmundusHelperUpdate::createTable('data_microsoft_dynamics_entities', $columns, [], $comment);

			if (!$created['status'])
			{
				return false;
			}
		}

		// Fetch and insert entities
		if (!class_exists('EmundusModelSync'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
		}

		$m_sync = new \EmundusModelSync();

		// Test authentication
		if (!$m_sync->testAuthentication($this->synchronizer->getId()))
		{
			return false;
		}
		
		// Install event subscriber plugin
		\EmundusHelperUpdate::installExtension('plg_emundus_microsoft_dynamics', 'microsoft_dynamics', null, 'plugin', 1, 'emundus', '{}', false, false);

		return true;
	}
}