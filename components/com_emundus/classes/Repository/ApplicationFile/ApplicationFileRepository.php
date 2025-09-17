<?php
namespace Tchooz\Repository\ApplicationFile;

use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

class ApplicationFileRepository
{
	private QueryInterface $query;

	public function __construct(
		private DatabaseInterface $db,
		private readonly int $user_id
	)
	{
		$this->query = $this->db->getQuery(true);

		Log::addLogger(['text_file' => 'com_emundus.applicationrepository.php'], Log::ALL, array('com_emundus.applicationrepository'));
	}

	public function flush(ApplicationFileEntity $applicationFileEntity): bool
	{
		$flushed = false;

		try
		{
			// Check if the user is valid
			if (!$applicationFileEntity->getUser()->id)
			{
				throw new \Exception('Invalid user');
			}

			// Check if the fnum is valid
			if (empty($applicationFileEntity->getFnum()))
			{
				throw new \Exception('Invalid fnum');
			}

			$ccid = $this->createCampaignCandidature($applicationFileEntity);
			if(empty($ccid)) {
				throw new \Exception('Failed to create campaign candidature');
			}

			if(!empty($applicationFileEntity->getData())) {
				foreach ($applicationFileEntity->getData() as $table => $data) {
					if(!$this->insertDatas($data, $table, $applicationFileEntity->getFnum(), $ccid)) {
						throw new \Exception('Failed to insert data into ' . $table);
					}
				}
			}

			$flushed = true;
		}
		catch (\Exception $e)
		{
			// Handle exception
			Log::add('Error when try to flush an application file: ' . $e->getMessage(), Log::ERROR, 'com_emundus.applicationrepository');
		}

		return $flushed;
	}

	private function createCampaignCandidature(ApplicationFileEntity $applicationFileEntity): int
	{
		$fnum = $applicationFileEntity->getFnum();

		$this->query->clear()
			->select('id')
			->from('#__emundus_campaign_candidature')
			->where('fnum = :fnum')
			->bind(':fnum', $fnum);
		$this->db->setQuery($this->query);
		$ccid = $this->db->loadResult();

		if(empty($ccid))
		{
			$campaign_candidature = [
				'date_time' => date('Y-m-d H:i:s'),
				'applicant_id' => $applicationFileEntity->getUser()->id,
				'user_id' => $this->user_id,
				'campaign_id' => $applicationFileEntity->getCampaignId(),
				'fnum' => $applicationFileEntity->getFnum(),
				'status' => $applicationFileEntity->getStatus(),
				'published' => $applicationFileEntity->getPublished(),
				'form_progress' => 0,
				'attachment_progress' => 0
			];
			$campaign_candidature = (object)$campaign_candidature;
			$this->db->insertObject('#__emundus_campaign_candidature', $campaign_candidature);

			$ccid = $this->db->insertid();
		}

		return $ccid;
	}
	
	private function insertDatas(array $datas, string $table, string $fnum, int $ccid): bool
	{
		$result = false;

		// If all datas are empty, skip the table
		$skip = empty(array_filter($datas, fn($data) => !empty($data)));
		if($skip) {
			return true;
		}

		$parent_table = $this->getRepeatJoin($table);

		if(empty($parent_table)) {
			$date_columns = $this->getDateColumns($table);
			$row_id = $this->getRowId($table, $fnum);

			$datas['time_date'] = date('Y-m-d H:i:s');
			$datas['user'] = $this->user_id;
			$datas['fnum'] = $fnum;
			if($this->haveCcidColumn($table)) {
				$datas['ccid'] = $ccid;
			}

			$multiple_inserts = [];
			foreach ($datas as $key => $value) {
				$multiple_table_join = $this->getMultipleTableJoin($table, $key);
				if(!empty($multiple_table_join))
				{
					// If the key is a join to another table, insert after the parent table is inserted
					$multiple_inserts[$multiple_table_join][$key] = $value;
					unset($datas[$key]);
				}

				if(in_array($key, $date_columns)) {
					if(empty($value)) {
						$datas[$key] = null;
						continue;
					}

					$timestamp = strtotime($value);
					if($timestamp !== false) {
						$datas[$key] = date('Y-m-d H:i:s', $timestamp);
					} else {
						$datas[$key] = null;
					}
				}
			}

			$datas = (object)$datas;

			if(empty($row_id))
			{
				if($result = $this->db->insertObject($table, $datas))
				{
					$row_id = $this->db->insertid();
				}
			}
			else {
				$datas->id = $row_id;
				$result = $this->db->updateObject($table, $datas, 'id');
			}

			if(!empty($row_id) && !empty($multiple_inserts))
			{
				foreach ($multiple_inserts as $repeat_table => $rows) {
					$existing_multiple_rows = $this->getMultipleRows($repeat_table, $row_id);
					foreach ($rows as $column => $row)
					{
						if(is_array($row)) {
							foreach ($row as $iteration => $multiple_value) {
								$insert = [
									'parent_id' => $row_id,
									$column => $multiple_value,
								];

								if(in_array($iteration, array_keys($existing_multiple_rows))) {
									$insert['id'] = $existing_multiple_rows[$iteration]->id;
									$insert = (object)$insert;
									$this->db->updateObject($repeat_table, $insert, 'id');
								}
								else {
									$insert = (object)$insert;
									$this->db->insertObject($repeat_table, $insert);
								}
							}
						}
						else if(!empty($row)) {
							$insert = [
								'parent_id' => $row_id,
								$column => $row,
							];

							if(!empty($existing_multiple_rows))
							{
								$insert['id'] = $existing_multiple_rows[0]->id;
								$insert = (object)$insert;
								$this->db->updateObject($repeat_table, $insert, 'id');
							}
							else {
								$insert = (object)$insert;
								$this->db->insertObject($repeat_table, $insert);
							}
						}
					}
				}
			}

			return $result;
		}
		else {
			$repeat_inserts = [];

			$parent_id = $this->getRowId($parent_table, $fnum);
			if(empty($parent_id)) {
				$parent_datas = [
					'time_date' => date('Y-m-d H:i:s'),
					'fnum' => $fnum,
					'user' => $this->user_id
				];
				if($this->haveCcidColumn($table)) {
					$parent_datas['ccid'] = $ccid;
				}
				$parent_datas = (object)$parent_datas;
				
				if($this->db->insertObject($parent_table, $parent_datas)) {
					$parent_id = $this->db->insertid();
				}
			}

			if(!empty($parent_id) && !empty($datas) && !empty($datas[array_key_first($datas)]) && is_array($datas[array_key_first($datas)])) {
				$repeat_iterations = count($datas[array_key_first($datas)]);

				$existing_repeat_rows = $this->getRepeatRows($table, $parent_id);

				for($i = 0; $i < $repeat_iterations; $i++) {
					$repeat_datas = [];
					foreach ($datas as $key => $value) {
						if(is_array($value) && isset($value[$i])) {
							$repeat_datas[$key] = $value[$i];
						}
					}

					$repeat_datas['parent_id'] = $parent_id;
					if(in_array($i, array_keys($existing_repeat_rows))) {
						$repeat_datas['id'] = $existing_repeat_rows[$i]->id;
					}
					$repeat_datas = (object)$repeat_datas;

					if(!empty($repeat_datas->id))
					{
						$repeat_inserts[] = $this->db->updateObject($table, $repeat_datas, 'id');
						continue;
					}

					$repeat_inserts[] = $this->db->insertObject($table, $repeat_datas);
				}
			}
			
			return !in_array(false, $repeat_inserts, true);
		}
	}

	private function getRepeatJoin($table): ?string
	{
		$this->query->clear()
			->select('join_from_table')
			->from($this->db->quoteName('#__fabrik_joins'))
			->where($this->db->quoteName('table_join') . ' = '. $this->db->quote($table))
			->where($this->db->quoteName('table_join_key') . ' = '. $this->db->quote('parent_id'));
		$this->db->setQuery($this->query);
		return $this->db->loadResult();
	}

	private function getMultipleTableJoin($table, $key): ?string
	{
		$this->query->clear()
			->select('table_join')
			->from($this->db->quoteName('#__fabrik_joins'))
			->where($this->db->quoteName('join_from_table') . ' = '. $this->db->quote($table))
			->where($this->db->quoteName('table_key') . ' = '. $this->db->quote($key))
			->where($this->db->quoteName('table_join_key') . ' = '. $this->db->quote('parent_id'));
		$this->db->setQuery($this->query);
		return $this->db->loadResult();
	}

	private function getRowId(string $table, string $fnum): int
	{
		$this->query->clear()
			->select('id')
			->from($this->db->quoteName($table))
			->where($this->db->quoteName('fnum') . ' = :fnum')
			->bind(':fnum', $fnum);
		$this->db->setQuery($this->query);
		return (int)$this->db->loadResult();
	}

	private function haveCcidColumn(string $table): bool
	{
		$this->db->setQuery('SHOW COLUMNS FROM ' . $table);
		$columns = $this->db->loadColumn();

		return in_array('ccid', $columns);
	}

	private function getDateColumns(string $table): array
	{
		$this->db->setQuery('SHOW COLUMNS FROM ' . $table);
		$columns = $this->db->loadObjectList();

		$date_columns = [];
		foreach ($columns as $column) {
			if (str_contains($column->Type, 'date') || str_contains($column->Type, 'time')) {
				$date_columns[] = $column->Field;
			}
		}

		return $date_columns;
	}

	private function getRepeatRows(string $table, int $parent_id): array
	{
		$this->query->clear()
			->select('*')
			->from($this->db->quoteName($table))
			->where($this->db->quoteName('parent_id') . ' = :parent_id')
			->bind(':parent_id', $parent_id, ParameterType::INTEGER);
		$this->db->setQuery($this->query);
		return $this->db->loadObjectList();
	}

	private function getMultipleRows(string $table, int $parent_id): array
	{
		$this->query->clear()
			->select('*')
			->from($this->db->quoteName($table))
			->where($this->db->quoteName('parent_id') . ' = :parent_id')
			->bind(':parent_id', $parent_id, ParameterType::INTEGER);
		$this->db->setQuery($this->query);
		return $this->db->loadObjectList();
	}
}