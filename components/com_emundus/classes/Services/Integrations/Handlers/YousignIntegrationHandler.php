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
use Joomla\CMS\Uri\Uri;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use Tchooz\Synchronizers\NumericSign\YousignSynchronizer;

class YousignIntegrationHandler extends AbstractIntegrationHandler
{
	public function getRequiredAddons(): array
	{
		return [AddonEnum::NUMERIC_SIGN];
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

		$token = $setup->authentication->token ?? '';
		if (preg_match('/^\*+$/', $token))
		{
			$token = $config['authentication']['token'] ?? '';
		}
		else
		{
			$token = $this->encrypt($token);
		}

		// Backward compatility
		if(isset($setup->authentication->mode))
		{
			$base_url           = ($setup->authentication->mode ?? 0) == 0
				? 'https://api-sandbox.yousign.app/v3'
				: 'https://api.yousign.app/v3';
			$config['base_url'] = $base_url;
		}
		$config['authentication']['token']        = $token;
		$config['authentication']['create_webhook']                = $setup->authentication->create_webhook ?? $config['create_webhook'] ?? 0;
		$config['create_webhook']                = $setup->authentication->create_webhook ?? $config['create_webhook'] ?? 0;
		$config['authentication']['mode']                          = $setup->authentication->mode ?? $config['mode'] ?? 0;
		$config['mode']                          = $setup->authentication->mode ?? $config['mode'] ?? 0;
		$config['configuration']['signature_level'] = $setup->configuration->signature_level ?? $config['signature_level'] ?? '';
		$config['signature_level'] = $setup->configuration->signature_level ?? $config['signature_level'] ?? '';
		$config['configuration']['signature_authentication_mode'] = $setup->configuration->signature_authentication_mode ?? $config['signature_authentication_mode'] ?? '';
		$config['signature_authentication_mode'] = $setup->configuration->signature_authentication_mode ?? $config['signature_authentication_mode'] ?? '';
		$config['configuration']['signature_display_mode'] = $setup->configuration->signature_display_mode ?? $config['signature_display_mode'] ?? 'minimal';
		$config['signature_display_mode'] = $setup->configuration->signature_display_mode ?? $config['signature_display_mode'] ?? 'minimal';
		$config['configuration']['request_name'] = $setup->configuration->request_name ?? $config['request_name'] ?? '';
		$config['request_name'] = $setup->configuration->request_name ?? $config['request_name'] ?? '';

		if(isset($setup->configuration->expiration_date))
		{
			if (!empty($setup->configuration->expiration_date) && $setup->configuration->expiration_date !== 'Invalid Date')
			{
				$config['configuration']['expiration_date'] = $setup->configuration->expiration_date;
				$config['expiration_date'] = $setup->configuration->expiration_date;
			}
			else
			{
				$config['configuration']['expiration_date'] = null;
				$config['expiration_date'] = null;
			}
		}

		$this->synchronizer->setConfig($config);
		$this->synchronizer->setEnabled(true);

		$repository = $repository ?? new SynchronizerRepository();

		return $repository->flush($this->synchronizer);
	}

	public function onAfterSetup(object $setup): bool
	{
		$this->ensureSchedulerTask();

		$synchronizer = new YousignSynchronizer();

		// If we update only configuration do not test again
		if(!isset($setup->authentication))
		{
			return true;
		}

		if (($setup->authentication->mode ?? 0) == 1)
		{
			// Production mode — test consumptions
			$api_consumptions = $synchronizer->getConsumptionsData();
			$authenticated = !empty($api_consumptions['data']);

			if ($authenticated)
			{
				$this->updateConsumptions((array) $api_consumptions['data']);
			}

			// Webhook management
			$webhooks = $synchronizer->getWebhookSubscriptions();
			if (($setup->authentication->create_webhook ?? 0) == 1)
			{
				$this->ensureWebhook($synchronizer, $webhooks);
			}
			else
			{
				$this->disableExistingWebhooks($synchronizer, $webhooks);

				// Fallback: test workspaces
				$workspaces = $synchronizer->getWorkspaces();
				$authenticated = !empty($workspaces['data']->data);
			}

			return $authenticated;
		}

		// Sandbox mode — test workspaces
		$workspaces = $synchronizer->getWorkspaces();

		return !empty($workspaces['data']->data);
	}

	private function ensureSchedulerTask(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('id')
			->from($db->quoteName('#__scheduler_tasks'))
			->where($db->quoteName('type') . ' = ' . $db->quote('yousign.api'));
		$db->setQuery($query);
		$task_id = $db->loadResult();

		if (empty($task_id))
		{
			if (!class_exists('EmundusHelperUpdate'))
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
			}

			$execution_rules = [
				'rule-type'        => 'interval-minutes',
				'interval-minutes' => 30,
				'exec-day'         => 1,
				'exec-time'        => '12:00'
			];
			$cron_rules = [
				'type' => 'interval',
				'exp'  => 'PT30M'
			];

			\EmundusHelperUpdate::createSchedulerTask('Yousign', 'yousign.api', $execution_rules, $cron_rules);
		}
		else
		{
			$query->clear()
				->update($db->quoteName('#__scheduler_tasks'))
				->set($db->quoteName('state') . ' = 1')
				->where($db->quoteName('id') . ' = ' . $db->quote($task_id));
			$db->setQuery($query);
			$db->execute();
		}
	}

	private function updateConsumptions(array $consumptions): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->update($db->quoteName('#__emundus_setup_sync'))
			->set($db->quoteName('consumptions') . ' = ' . $db->quote(json_encode($consumptions)))
			->where($db->quoteName('type') . ' = ' . $db->quote('yousign'));
		$db->setQuery($query);
		$db->execute();
	}

	private function ensureWebhook(YousignSynchronizer $synchronizer, array $webhooks): void
	{
		$webhook_created = false;

		if (!empty($webhooks['data']))
		{
			foreach ($webhooks['data'] as $webhook)
			{
				if (strpos($webhook->endpoint, Uri::base()) !== false)
				{
					$webhook_created = true;

					if (!$webhook->enabled)
					{
						$synchronizer->toggleWebhookSubscription($webhook->id);
					}
				}
			}
		}

		if (!$webhook_created)
		{
			$response = $synchronizer->createWebhookSubscription();
			if (($response['status'] ?? 0) === 201 && !empty($response['data']))
			{
				$this->updateWebhookSecret($response['data']->secret_key);
			}
		}
	}

	private function disableExistingWebhooks(YousignSynchronizer $synchronizer, array $webhooks): void
	{
		if (!empty($webhooks['data']))
		{
			foreach ($webhooks['data'] as $webhook)
			{
				if (strpos($webhook->endpoint, Uri::base()) !== false)
				{
					$synchronizer->toggleWebhookSubscription($webhook->id, false);
				}
			}
		}
	}

	private function updateWebhookSecret(string $secret_key): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('id, config')
			->from($db->quoteName('#__emundus_setup_sync'))
			->where($db->quoteName('type') . ' = ' . $db->quote('yousign'));
		$db->setQuery($query);
		$row = $db->loadObject();

		if (!empty($row))
		{
			$config = json_decode($row->config, true);
			$config['webhook_secret'] = $secret_key;

			$query->clear()
				->update($db->quoteName('#__emundus_setup_sync'))
				->set($db->quoteName('config') . ' = ' . $db->quote(json_encode($config)))
				->where($db->quoteName('id') . ' = ' . $db->quote($row->id));
			$db->setQuery($query);
			$db->execute();
		}
	}
}