<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;

class Release2_24_2Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		try
		{
			$this->tasks[] = EmundusHelperUpdate::addSelectableProviderTag(
				'LAST_CONFIRMED_TRANSACTION_AMOUNT',
				'Amount of the last confirmed transaction of the file, followed by its currency symbol.',
				'Montant de la dernière transaction confirmée du dossier, suivi du symbole de sa devise.'
			);

			$this->tasks[] = EmundusHelperUpdate::addSelectableProviderTag(
				'LAST_CONFIRMED_TRANSACTION_REFERENCE',
				'Reference of the last confirmed transaction of the file.',
				'Référence de la dernière transaction confirmée du dossier.'
			);

			$this->tasks[] = EmundusHelperUpdate::addSelectableProviderTag(
				'LAST_TRANSACTION_AMOUNT',
				'Amount of the last transaction of the file whatever its status, followed by its currency symbol.',
				'Montant de la dernière transaction du dossier quel que soit son statut, suivi du symbole de sa devise.'
			);

			$this->tasks[] = EmundusHelperUpdate::addSelectableProviderTag(
				'LAST_TRANSACTION_REFERENCE',
				'Reference of the last transaction of the file whatever its status.',
				'Référence de la dernière transaction du dossier quel que soit son statut.'
			);

			$this->tasks[] = $this->renameWorldlineReferenceLabels();
			$this->tasks[] = $this->resetWorldlineConfiguration();

			$result['status'] = !in_array(false, $this->tasks);

			if (!$result['status'])
			{
				$result['message'] = 'Failed to register the transaction email tags.';
			}
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}


	private function renameWorldlineReferenceLabels(): bool
	{
		$query = $this->db->createQuery();

		$query->update($this->db->quoteName('#__emundus_external_reference'))
			->set($this->db->quoteName('reference_object') . ' = ' . $this->db->quote('payment'))
			->set($this->db->quoteName('reference_attribute') . ' = ' . $this->db->quote('id'))
			->where($this->db->quoteName('reference_object') . ' = ' . $this->db->quote('payments'))
			->where($this->db->quoteName('reference_attribute') . ' = ' . $this->db->quote('paymentId'));

		try
		{
			$this->db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * Worldline now holds one credential set per environment, under the preprod_ and production_
	 * prefixes. A configuration still in the previous shape is rewritten to that of the new one,
	 * which drops what it held: the old keys carried no environment, so there is no way to tell
	 * which set they belonged to. Credentials have to be pasted again from the Configuration Center.
	 *
	 * Only an empty configuration is written: an already configured Worldline is left alone. This
	 * script re-runs on every update at an equal version, and rewriting each time would wipe
	 * credentials entered in between.
	 */
	private function resetWorldlineConfiguration(): bool
	{
		$query = $this->db->createQuery();

		$query->select($this->db->quoteName('config'))
			->from($this->db->quoteName('#__emundus_setup_sync'))
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('worldline'));

		$this->db->setQuery($query);
		$stored = json_decode((string) $this->db->loadResult(), true);

		$legacy_keys = ['mode', 'merchant_id', 'api_key_id', 'api_secret', 'webhook_key_id', 'webhook_secret', 'checkout_subdomain', 'webhook_url'];
		$stored_keys = array_keys($stored['authentication'] ?? []);

		sort($legacy_keys);
		sort($stored_keys);

		if ($stored_keys === $legacy_keys)
		{
			$config = json_encode([
				'authentication' => [
					'mode'                          => 0,
					'preprod_merchant_id'           => '',
					'preprod_api_key_id'            => '',
					'preprod_api_secret'            => '',
					'preprod_webhook_key_id'        => '',
					'preprod_webhook_secret'        => '',
					'preprod_checkout_subdomain'    => '',
					'production_merchant_id'        => '',
					'production_api_key_id'         => '',
					'production_api_secret'         => '',
					'production_webhook_key_id'     => '',
					'production_webhook_secret'     => '',
					'production_checkout_subdomain' => '',
					'webhook_url'                   => '',
				],
			]);

			$query->clear()
				->update($this->db->quoteName('#__emundus_setup_sync'))
				->set($this->db->quoteName('config') . ' = ' . $this->db->quote($config))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('worldline'));

			try
			{
				$this->db->setQuery($query)->execute();
			}
			catch (\Exception $e)
			{
				return false;
			}
		}

		return true;
	}

}
