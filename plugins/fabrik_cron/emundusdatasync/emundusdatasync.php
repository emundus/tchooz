<?php
/**
 * A cron task to email evaluators on un-evaluated files
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.email
 * @copyright   Copyright (C) 2015 emundus.fr - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
use GuzzleHttp\Exception\ClientException;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

use GuzzleHttp\Client as GuzzleClient;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-cron.php';

class PlgFabrik_Cronemundusdatasync extends PlgFabrik_Cron
{
	private string $accessToken = '';

	private string $baseUrl = '';

	private string $apiUrl = '';

	private GuzzleClient $client;

	private array $tables = [];

	public function process(&$data, &$listModel)
	{
		$rowsSynced = 0;

		$params = $this->getParams();

		$this->baseUrl = $params->get('base_url', '');
		$this->apiUrl  = $params->get('api_url', '');
		$auth_method   = $params->get('auth_method', '');
		$login_url     = $params->get('login_url', '');
		$client_id     = $params->get('client_id', '');
		$client_secret = $params->get('client_secret', '');

		if (!empty($this->baseUrl) && !empty($this->apiUrl))
		{
			$mapping = $params->get('mapping', '');

			$this->client = new GuzzleClient(
				[
					'base_uri' => $this->baseUrl,
					'verify'   => false
				]
			);

			//1. Perform login to API
			$this->accessToken = $this->loginApi($login_url, $client_id, $client_secret);

			if (!empty($this->accessToken))
			{
				$this->tables = $this->_db->getTableList();

				$mapping = (array) $mapping;

				foreach ($mapping as $map)
				{
					$rowsSynced = $this->syncData($map);
				}
			}
		}

		return $rowsSynced;
	}

	private function loginApi(string $login_url, string $client_id, string $client_secret): string
	{
		$accessToken = '';

		// Perform login to API
		$response = ['status' => 200, 'message' => '', 'data' => ''];

		try
		{
			$params = array();
			// Pass client_id and client_Secret in form data
			$params['form_params'] = [
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'grant_type'    => 'client_credentials'
			];


			$request = $this->client->post($this->baseUrl . '/' . $login_url, $params);

			$response['status'] = $request->getStatusCode();
			$response['data']   = json_decode($request->getBody());

			if ($response['status'] == 200)
			{
				$accessToken = $response['data']->access_token;
			}
		}
		catch (ClientException $e)
		{
			Log::add('[POST] ' . $e->getMessage(), Log::ERROR, 'com_emundus.api');
		}

		return $accessToken;
	}

	private function syncData(object $map): int
	{
		$rows_synced = 0;

		// 1. Check if table exist
		$query  = $this->_db->getQuery(true);
		$tables = $this->_db->getTableList();

		if (!empty($map->table))
		{
			$tableColumns = $this->checkTableState($map);
			if (!empty($tableColumns))
			{
				// 2. Get data from API
				$request = $this->client->get($this->baseUrl . '/' . $this->apiUrl . '/' . $map->api_route, [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->accessToken
					]
				]);

				$response['status'] = $request->getStatusCode();
				$response['datas']  = json_decode($request->getBody());

				if ($response['status'] == 200)
				{
					$datas = $response['datas'];

					// 3. Save data in database
					$rows_synced += $this->saveData($map, $datas);
				}
			}
		}

		return $rows_synced;
	}

	private function checkTableState(object $map): array
	{
		$columns = [];
		$query   = $this->_db->getQuery(true);

		if (!in_array($map->table, $this->tables))
		{
			// Create table
			$query->setQuery('CREATE TABLE IF NOT EXISTS ' . $map->table . ' (id INT AUTO_INCREMENT PRIMARY KEY)');
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				return $columns;
			}
		}

		$columns = $this->_db->getTableColumns($map->table);
		if (!empty($columns))
		{
			$columns = array_keys($columns);
		}

		// Create at least published, date_time, updated column
		if (!in_array('published', $columns))
		{
			$query->setQuery('ALTER TABLE ' . $map->table . ' ADD COLUMN published TINYINT DEFAULT 1');
			$this->_db->setQuery($query);

			if ($this->_db->execute())
			{
				$columns[] = 'published';
			}
		}
		if (!in_array('date_time', $columns))
		{
			$query->setQuery('ALTER TABLE ' . $map->table . ' ADD COLUMN date_time DATETIME');
			$this->_db->setQuery($query);

			if ($this->_db->execute())
			{
				$columns[] = 'date_time';
			}
		}
		if (!in_array('updated', $columns))
		{
			$query->setQuery('ALTER TABLE ' . $map->table . ' ADD COLUMN updated DATETIME');
			$this->_db->setQuery($query);

			if ($this->_db->execute())
			{
				$columns[] = 'updated';
			}
		}

		if (!empty($map->attributes))
		{
			$attributes = (array) $map->attributes;

			foreach ($attributes as $attribute)
			{
				if (!in_array($attribute->column, $columns))
				{
					$type   = $attribute->column_type;
					$length = $attribute->column_length;
					if ($attribute->column_type === 'join')
					{
						$type   = 'INT';
						$length = 11;
					}

					$query->setQuery('ALTER TABLE ' . $map->table . ' ADD COLUMN ' . $attribute->column . ' ' . $type . '(' . $length . ')');
					$this->_db->setQuery($query);

					if ($this->_db->execute())
					{
						$columns[] = $attribute->column;
					}
				}
			}
		}
		else
		{
			// Create at least a label column
			if (!in_array('label', $columns))
			{
				$query->setQuery('ALTER TABLE ' . $map->table . ' ADD COLUMN label VARCHAR(255)');
				$this->_db->setQuery($query);

				if ($this->_db->execute())
				{
					$columns[] = 'label';
				}
			}
		}

		return $columns;
	}

	private function saveData(object $map, array $datas): int
	{
		$query = $this->_db->getQuery(true);

		$ids = [];
		if (empty($map->attributes))
		{
			foreach ($datas as $data)
			{
				if (!empty($data) && is_string($data))
				{
					// Check if data already exist
					$query->clear()
						->select('id')
						->from($map->table)
						->where('label LIKE ' . $this->_db->quote($data));
					$this->_db->setQuery($query);
					$id = $this->_db->loadResult();

					if (empty($id))
					{
						$insert = [
							'date_time' => date('Y-m-d H:i:s'),
							'label'     => $data,
							'published' => 1
						];
						$insert = (object) $insert;

						if ($this->_db->insertObject($map->table, $insert))
						{
							$ids[] = $this->_db->insertid();
						}
					}
					else
					{
						// Publish data
						$update = [
							'id'        => $id,
							'published' => 1,
							'updated'   => date('Y-m-d H:i:s')
						];
						$update = (object) $update;

						if ($this->_db->updateObject($map->table, $update, 'id'))
						{
							$ids[] = $id;
						}
					}
				}
			}

			// Unpublish all other data
			$query->clear()
				->update($map->table)
				->set($this->_db->quoteName('published') . ' = 0')
				->where($this->_db->quoteName('id') . ' NOT IN (' . implode(',', $ids) . ')');
			$this->_db->setQuery($query);
			$this->_db->execute();
		}
		else
		{
			$attributes = (array) $map->attributes;

			foreach ($datas as $data)
			{
				if(!empty($map->where)) {
					$filter = explode('=',$map->where);

					$filterAttribute = explode('.', $filter[0]);
					if (count($filterAttribute) > 1)
					{
						if (!empty($data->{trim($filterAttribute[0])}->{trim($filterAttribute[1])}))
						{
							if(trim($data->{trim($filterAttribute[0])}->{trim($filterAttribute[1])}) != trim($filter[1]))
							{
								continue;
							}
						}
					}
				}

				$rowsToInsert = 1;
				if (!empty($map->group_by))
				{
					if (!empty($data->{$map->group_by} && is_array($data->{$map->group_by})))
					{
						$rowsToInsert = count($data->{$map->group_by});
					}
				}

				for ($i = 0; $i < $rowsToInsert; $i++)
				{
					$dataObject    = ['published' => 1];
					$lookupColumns = [];
					foreach ($attributes as $attribute)
					{
						// explode $attribute->api_attribute if it contains a comma
						$apiAttributes = explode(',', $attribute->api_attribute);

						$dataObject[$attribute->column] = '';
						foreach ($apiAttributes as $apiAttribute)
						{
							// explode $attribute->api_attribute if it contains a dot
							$apiAttribute = explode('.', $apiAttribute);
							if (count($apiAttribute) > 1)
							{
								if (!empty($data->{$apiAttribute[0]}->{$apiAttribute[1]}))
								{
									$dataObject[$attribute->column] = $data->{$apiAttribute[0]}->{$apiAttribute[1]};
								}
							}
							else
							{
								if (!empty($data->{$apiAttribute[0]}))
								{
									if (is_array($data->{$apiAttribute[0]}))
									{
										$dataObject[$attribute->column] .= $data->{$apiAttribute[0]}[$i];
									}
									else
									{
										$dataObject[$attribute->column] .= $data->{$apiAttribute[0]};
									}
								}
								else
								{
									$dataObject[$attribute->column] .= $apiAttribute[0];
								}
							}

							if ($attribute->column_type === 'join')
							{
								$joinTable  = $attribute->join_table;
								$joinColumn = $attribute->join_column;

								if (!empty($joinColumn) && !empty($joinTable))
								{
									$query->clear()
										->select('id')
										->from($this->_db->quoteName($joinTable))
										->where($this->_db->quoteName($joinColumn) . ' LIKE ' . $this->_db->quote($dataObject[$attribute->column]));
									$this->_db->setQuery($query);
									$dataObject[$attribute->column] = $this->_db->loadResult();
								}
							}

							if ($attribute->lookup_column)
							{
								if (!empty($data->{$apiAttribute[0]}))
								{
									if (is_array($data->{$apiAttribute[0]}))
									{
										$lookupColumns[$attribute->column] .= $data->{$apiAttribute[0]}[$i];
									}
									else
									{
										$lookupColumns[$attribute->column] .= $data->{$apiAttribute[0]};
									}
								} else {
									$lookupColumns[$attribute->column] .= $apiAttribute[0];
								}
							}
						}
					}

					if (!empty($lookupColumns))
					{
						// Check if data already exist
						$query->clear()
							->select('id')
							->from($map->table);
						foreach ($lookupColumns as $column => $value)
						{
							$query->where($this->_db->quoteName($column) . ' = ' . $this->_db->quote($value));
						}
						$this->_db->setQuery($query);
						$id = $this->_db->loadResult();
					}
					else
					{
						// Check if data already exist with label
						$query->clear()
							->select('id')
							->from($map->table)
							->where('label LIKE TRIM('.$this->_db->quote('%' . $dataObject['label'] .'%').')');
						$this->_db->setQuery($query);
						$id = $this->_db->loadResult();
					}

					if (!empty($id))
					{
						// Update data
						$dataObject['id']      = $id;
						$dataObject['updated'] = date('Y-m-d H:i:s');
						$dataObject            = (object) $dataObject;
						if ($this->_db->updateObject($map->table, $dataObject, 'id'))
						{
							$ids[] = $id;
						}
					}
					else
					{
						// Insert data
						$dataObject['date_time'] = date('Y-m-d H:i:s');
						$dataObject              = (object) $dataObject;
						if ($this->_db->insertObject($map->table, $dataObject))
						{
							$ids[] = $this->_db->insertid();
						}
					}
				}
			}

			// Unpublish all other data
			$query->clear()
				->update($map->table)
				->set($this->_db->quoteName('published') . ' = 0')
				->where($this->_db->quoteName('id') . ' NOT IN (' . implode(',', $ids) . ')');
			$this->_db->setQuery($query);
			$this->_db->execute();
		}

		return count($ids);
	}
}

