<?php

namespace scripts;

class Release2_4_3Installer extends ReleaseInstaller
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
			$event_result = \EmundusHelperUpdate::addCustomEvents([['label' => 'onAfterSubmitFile', 'category' => 'File', 'published' => 1]]);
			$tasks[] = $event_result['status'];

			// Be sure that jos_emundus_setup_campaigns_more table exist
			$columns = [
				[
					'name' => 'date_time',
					'type' => 'DATE',
					'null' => 0,
				],
				[
					'name' => 'campaign_id',
					'type' => 'INT',
					'null' => 0
				]
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_setup_campaigns_more_campaign_id_fk',
					'from_column'    => 'campaign_id',
					'ref_table'      => 'jos_emundus_setup_campaigns',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true,
				],
			];
			$tasks[] = \EmundusHelperUpdate::createTable('jos_emundus_setup_campaigns_more', $columns, $foreign_keys)['status'];

			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('import'));
			$this->db->setQuery($query);
			$config = $this->db->loadObject();

			if (empty($config->namekey))
			{
				$params = '{"enabled":0,"displayed":0,"params":{}}';
				$query->clear()
					->insert($this->db->quoteName('#__emundus_setup_config'))
					->columns($this->db->quoteName('namekey') . ', ' . $this->db->quoteName('value'))
					->values($this->db->quote('import') . ', ' . $this->db->quote($params));

				$this->db->setQuery($query);
				$this->db->execute();
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