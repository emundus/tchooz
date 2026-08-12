<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

class Release2_22_0Installer extends ReleaseInstaller
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
			$paybox     = $repository->getByType('paybox');

			if (empty($paybox))
			{
				$config = [
					'authentication' => [
						'site'        => '',
						'rang'        => '',
						'identifiant' => '',
						'hmac_key'    => '',
						'public_key'  => '',
					],
					'configuration'  => [
						'mode' => 'TEST',
					],
				];

				$paybox = new SynchronizerEntity(
					0,
					'paybox',
					'Paybox',
					'Paiement via le service Paybox',
					[],
					$config,
					false,
					false,
					'paybox.png'
				);

				$this->tasks[] = $repository->flush($paybox);
			}

			if (!empty($paybox) && !empty($paybox->getId()))
			{
				$this->tasks[] = $this->associatePaymentMethod('CB', $paybox->getId());
			}

			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_comments', 'pinned', 'TINYINT', 1, 0, 0)['status'];
			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_comments', 'is_public', 'TINYINT', 1, 0, 1)['status'];
			$this->tasks[] = \EmundusHelperUpdate::alterColumn('jos_emundus_comments', 'applicant_id', 'INT', 1)['status'];
			$this->tasks[] = \EmundusHelperUpdate::alterColumn('jos_emundus_comments', 'fnum', 'VARCHAR', 28)['status'];

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
}