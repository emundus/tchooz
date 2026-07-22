<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Tchooz\Entities\Automation\Actions\ActionRedirect;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

class Release2_23_0Installer extends ReleaseInstaller
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
			$repository = new SynchronizerRepository();
			$payzen     = $repository->getByType('payzen');

			if (empty($payzen))
			{
				$config = [
					'authentication' => [
						'client_id'     => '',
						'client_secret' => '',
					],
					'endpoint'       => 'https://secure.payzen.eu/vads-payment/',
					'mode'           => 'TEST',
					'return_url'     => '',
					'configuration'  => [
						'endpoint' => 'https://secure.payzen.eu/vads-payment/',
						'mode'     => 'TEST',
					],
				];

				$payzen = new SynchronizerEntity(
					0,
					'payzen',
					'PayZen',
					'Paiement via le service PayZen',
					[],
					$config,
					false,
					false,
					'payzen.svg'
				);

				$this->tasks[] = $repository->flush($payzen);
			}

			if (!empty($payzen) && !empty($payzen->getId()))
			{
				$this->tasks[] = $this->associatePaymentMethod('CB', $payzen->getId());
				$this->tasks[] = $this->associatePaymentMethod('sepa', $payzen->getId());
			}

			$this->rebuildRedirectAutomationsActions();

			$result['status'] = !in_array(false, $this->tasks, true);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function associatePaymentMethod(string $methodName, int $serviceId): bool
	{
		$query = $this->db->getQuery(true);
		$query->select('id')
			->from($this->db->quoteName('#__emundus_setup_payment_method'))
			->where($this->db->quoteName('name') . ' = ' . $this->db->quote($methodName));
		$this->db->setQuery($query);
		$methodId = $this->db->loadResult();

		if (empty($methodId))
		{
			return true;
		}

		$query->clear()
			->select('payment_method_id')
			->from($this->db->quoteName('#__emundus_setup_payment_method_sync'))
			->where($this->db->quoteName('payment_method_id') . ' = ' . $this->db->quote($methodId))
			->andWhere($this->db->quoteName('service_id') . ' = ' . $this->db->quote($serviceId));
		$this->db->setQuery($query);

		if (!empty($this->db->loadResult()))
		{
			return true;
		}

		$association                    = new \stdClass();
		$association->payment_method_id = $methodId;
		$association->service_id        = $serviceId;

		return $this->db->insertObject('#__emundus_setup_payment_method_sync', $association);
	}

	private function rebuildRedirectAutomationsActions(): void
	{
		$query = $this->db->createQuery();

		$query->select('id, params')
			->from($this->db->quoteName('#__emundus_action'))
			->where($this->db->quoteName('name') . ' = ' . $this->db->quote('redirect'));
		$this->db->setQuery($query);
		$redirectActions = $this->db->loadObjectList();

		foreach ($redirectActions as $redirectAction)
		{
			$params = json_decode($redirectAction->params);

			// Same priority that execute method in ActionRedirect class
			if (!empty($params->known_url))
			{
				$params->url_type = ActionRedirect::KNOWN_URL;
			}
			elseif (!empty($params->custom_url))
			{
				$params->url_type = ActionRedirect::CUSTOM_URL;
			}
			else
			{
				$params->url_type = ActionRedirect::INTERN_URL;
			}

			$redirectAction->params = json_encode($params);
			$this->tasks[]          = $this->db->updateObject('#__emundus_action', $redirectAction, 'id');
		}
	}
}