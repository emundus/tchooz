<?php

namespace scripts;

use EmundusHelperUpdate;

class Release2_5_1Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query  = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		$tasks = [];

		try
		{
			// Check if index already exists
			$index_query = 'SHOW INDEX FROM jos_messages WHERE Key_name = "notifications";';
			$this->db->setQuery($index_query);
			$index = $this->db->loadResult();

			if(empty($index))
			{
				$index_query = 'CREATE INDEX notifications on jos_messages (page, user_id_from, date_time);';
				$this->db->setQuery($index_query);
				$tasks[] = $this->db->execute();
			}

			$result['status'] = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();

			return $result;
		}

		return $result;
	}
}