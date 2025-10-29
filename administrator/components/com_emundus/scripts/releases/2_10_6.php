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

class Release2_10_6Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$tasks = [];

		try
		{
			$query = $this->db->getQuery(true);

			$response         = EmundusHelperUpdate::addCustomEvents(
				[
					['label' => 'onAfterCreateBtoBFile', 'description' => '', 'category' => 'BToB', 'published' => 1],
					['label' => 'onBeforeStoreAmmonTask', 'description' => '', 'category' => 'Task', 'published' => 0]
				]
			);
			$tasks[]          = $response['status'];

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__fabrik_lists'))
				->where($this->db->quoteName('db_table_name') . ' = ' . $this->db->quote('jos_emundus_setup_emails_trigger'));
			$this->db->setQuery($query);
			$triggers_fabrik_lists = $this->db->loadColumn();

			foreach ($triggers_fabrik_lists as $triggersFabrikList)
			{
				// Unpublish admin menu with this list
				$query->clear()
					->update($this->db->quoteName('#__menu'))
					->set($this->db->quoteName('published') . ' = 0')
					->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('adminmenu'))
					->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_fabrik&view=list&listid=' . (int) $triggersFabrikList));
				$this->db->setQuery($query);
				$tasks[] = (bool) $this->db->execute();
			}


			$result['status']  = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}