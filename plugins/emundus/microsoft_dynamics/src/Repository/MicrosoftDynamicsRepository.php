<?php
/**
 * @package     Joomla\Plugin\Emundus\MicrosoftDynamics\Repository
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Emundus\MicrosoftDynamics\Repository;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\Plugin\Emundus\MicrosoftDynamics\Entity\MicrosoftDynamicsEntity;

class MicrosoftDynamicsRepository
{
	private QueryInterface $query;

	public function __construct(
		private DatabaseInterface $db
	)
	{
		$this->query = $this->db->getQuery(true);

		$tables = $this->db->getTableList();

		if (!in_array('jos_emundus_microsoft_dynamics_queue', $tables))
		{
			if (!$this->createTable())
			{
				throw new \Exception('The table could not be created');
			}
		}
	}

	public function flush(MicrosoftDynamicsEntity $dynamicsEntity, string $collectionname, string $name, string $action): bool
	{
		// Store datas in database
		$importDatas = [
			'collectionname' => $collectionname,
			'name'           => $name,
			'action'         => $action,
			'fnum'           => $dynamicsEntity->getFnum(),
			'campaign_id'    => $dynamicsEntity->getCampaignId(),
			'json'           => json_encode($dynamicsEntity->getApplicationFile()),
			'created_at'     => date('Y-m-d H:i:s'),
			'lookup_filters' => json_encode($dynamicsEntity->getLookupKeys()),
			'config'         => json_encode($dynamicsEntity->getConfig()),
			'status'         => 'pending'
		];

		// Check if the data already exists
		$this->query->clear()
			->select('id,config')
			->from($this->db->quoteName('#__emundus_microsoft_dynamics_queue'))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($dynamicsEntity->getFnum()))
			->where($this->db->quoteName('collectionname') . ' = ' . $this->db->quote($collectionname))
			->where($this->db->quoteName('name') . ' = ' . $this->db->quote($name))
			->where($this->db->quoteName('action') . ' = ' . $this->db->quote($action));
		$this->db->setQuery($this->query);
		$imports = $this->db->loadAssocList();

		foreach ($imports as $import)
		{
			if (!empty($import['id']))
			{
				$importConfig = json_decode($import['config'], true);
				if (!empty($importConfig['data']))
				{
					$importConfig['data'] = json_decode($importConfig['data'], true);
				}
				$currentConfigData = [];
				if (!empty($dynamicsEntity->getConfig()['data']))
				{
					$currentConfigData = json_decode($dynamicsEntity->getConfig()['data'], true);
				}

				if (!empty($importConfig) && is_array($importConfig['data']) && !empty($currentConfigData) && $importConfig['data']['state'] == $currentConfigData['state'])
				{
					$importDatas['id'] = $import['id'];
					$importDatas       = (object) $importDatas;

					return $this->db->updateObject('jos_emundus_microsoft_dynamics_queue', $importDatas, 'id');
				}
			}
		}

		$importDatas = (object) $importDatas;

		return $this->db->insertObject('jos_emundus_microsoft_dynamics_queue', $importDatas);
	}

	public function flushApi(string $collectionname, string $datas, object $api, string $rowId): array
	{
		$mSync = new \EmundusModelSync();

		if (!empty($rowId))
		{
			return $mSync->callApi($api, $collectionname . '(' . $rowId . ')', 'patch', $datas);
		}
		else
		{
			$params = json_decode($datas, true);

			return $mSync->callApi($api, $collectionname, 'post', $params);
		}
	}

	public function deleteData(int $dataId): bool
	{
		$this->query->clear()
			->delete($this->db->quoteName('#__emundus_microsoft_dynamics_queue'))
			->where('id = ' . $this->db->quote($dataId));
		$this->db->setQuery($this->query);

		return $this->db->execute();
	}

	public function updateRetry(int $dataId, int $retry): bool
	{
		$this->query->clear()
			->update($this->db->quoteName('#__emundus_microsoft_dynamics_queue'))
			->set('retry = ' . $this->db->quote($retry))
			->where('id = ' . $this->db->quote($dataId));
		$this->db->setQuery($this->query);

		return $this->db->execute();
	}

	public function updateStatus(int $dataId, string $status): bool
	{
		$this->query->clear()
			->update($this->db->quoteName('#__emundus_microsoft_dynamics_queue'))
			->set('status = ' . $this->db->quote($status))
			->where('id = ' . $this->db->quote($dataId));
		$this->db->setQuery($this->query);

		return $this->db->execute();
	}

	public function getDatas(?string $order = null): array
	{
		$this->query->clear()
			->select('*')
			->from($this->db->quoteName('#__emundus_microsoft_dynamics_queue'))
			->where('json IS NOT NULL')
			->where('status = ' . $this->db->quote('pending'));
		if(!empty($order)) {
			$this->query->order($order);
		}
		else {
			$this->query->order('created_at');
		}
		$this->db->setQuery($this->query);

		return $this->db->loadAssocList();
	}

	public function getJsonData($data): array
	{
		$this->query->clear()
			->select('json')
			->from($this->db->quoteName('#__emundus_microsoft_dynamics_queue'))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($data['fnum']))
			->where($this->db->quoteName('collectionname') . ' = ' . $this->db->quote($data['collectionname']))
			->where($this->db->quoteName('name') . ' = ' . $this->db->quote($data['name']))
			->where($this->db->quoteName('action') . ' = ' . $this->db->quote($data['action']));
		$this->db->setQuery($this->query);
		$json = $this->db->loadResult();

		if(!empty($json)) {
			return json_decode($json, true);
		}
		else {
			return [];
		}
	}

	public function getLookupFilters($data): array
	{
		$this->query->clear()
			->select('lookup_filters')
			->from($this->db->quoteName('#__emundus_microsoft_dynamics_queue'))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($data['fnum']))
			->where($this->db->quoteName('collectionname') . ' = ' . $this->db->quote($data['collectionname']))
			->where($this->db->quoteName('name') . ' = ' . $this->db->quote($data['name']))
			->where($this->db->quoteName('action') . ' = ' . $this->db->quote($data['action']));
		$this->db->setQuery($this->query);
		$json = $this->db->loadResult();

		if(!empty($json)) {
			return json_decode($json, true);
		}
		else {
			return [];
		}
	}

	public function getRowId(string $name, string $collectionname, object $api, array $filters): string
	{
		$mSync = new \EmundusModelSync();

		$rowid = '';

		$attribute_id = $name . 'id';
		if (!empty($filters))
		{
			$params = [
				'$top'    => 1,
				'$select' => $attribute_id,
				'$filter' => implode(' and ', array_map(function ($filter) {
					return $filter['attribute'] . ' eq \'' . $filter['value'] . '\'';
				}, $filters))
			];
			$result = $mSync->callApi($api, $collectionname, 'get', $params);

			if ($result['status'] == 200)
			{
				if (!empty($result['data']->value))
				{
					$rowid = $result['data']->value[0]->{$attribute_id};
				}
			}
		}

		return $rowid;
	}

	private function createTable(): bool
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
		$columns = [
			[
				'name'   => 'id_crm',
				'type'   => 'VARCHAR',
				'null'   => 1,
				'length' => 100
			],
			[
				'name'   => 'action',
				'type'   => 'VARCHAR',
				'null'   => 1,
				'length' => 150
			],
			[
				'name'   => 'name',
				'type'   => 'VARCHAR',
				'null'   => 1,
				'length' => 150
			],
			[
				'name'   => 'collectionname',
				'type'   => 'VARCHAR',
				'null'   => 1,
				'length' => 150
			],
			[
				'name'   => 'fnum',
				'type'   => 'VARCHAR',
				'null'   => 1,
				'length' => 28
			],
			[
				'name'   => 'campaign_id',
				'type'   => 'INT',
				'null'   => 1,
				'length' => 11
			],
			[
				'name' => 'created_at',
				'type' => 'DATETIME',
				'null' => 0
			],
			[
				'name' => 'updated_at',
				'type' => 'DATETIME',
				'null' => 1
			],
			[
				'name' => 'deleted_at',
				'type' => 'DATETIME',
				'null' => 1
			],
			[
				'name' => 'json',
				'type' => 'TEXT',
				'null' => 1
			],
			[
				'name' => 'lookup_filters',
				'type' => 'TEXT',
				'null' => 1
			],
			[
				'name' => 'config',
				'type' => 'TEXT',
				'null' => 1
			],
			[
				'name'   => 'status',
				'type'   => 'VARCHAR',
				'length' => 100,
				'null'   => 1
			],
			[
				'name'   => 'retry',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 0,
				'default' => 0
			]
		];

		return \EmundusHelperUpdate::createTable('jos_emundus_microsoft_dynamics_queue', $columns)['status'];
	}
}