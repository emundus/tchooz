<?php

namespace scripts;

use EmundusHelperUpdate;

class Release2_5_0Installer extends ReleaseInstaller
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
			// Create table jos_emundus_contacts
			$columns      = [
				[
					'name'   => 'lastname',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null' => 0
				],
				[
					'name'   => 'firstname',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null' => 0
				],
				[
					'name'   => 'email',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null' => 0
				],
				[
					'name'   => 'phone_1',
					'type'   => 'VARCHAR',
					'length' => 100,
					'null' => 1
				],
				[
					'name'   => 'user_id',
					'type'   => 'INT',
					'length' => 11,
					'null' => 1
				],
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_contacts_user_id_fk',
					'from_column'    => 'user_id',
					'ref_table'      => 'jos_emundus_users',
					'ref_column'     => 'user_id',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			$tasks[] = EmundusHelperUpdate::createTable('jos_emundus_contacts', $columns, $foreign_keys)['status'];

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