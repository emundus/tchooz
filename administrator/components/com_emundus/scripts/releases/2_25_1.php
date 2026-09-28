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

class Release2_25_1Installer extends ReleaseInstaller
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
			$query = $this->db->createQuery();
			$query->clear()
				->update($this->db->quoteName('#__emundus_chatroom'))
				->set($this->db->quoteName('status') . ' = 1')
				->where($this->db->quoteName('status') . ' IS NULL');

			$this->tasks[] = ($this->db->setQuery($query))->execute();

			$result = EmundusHelperUpdate::alterColumn('jos_emundus_chatroom', 'status', 'TINYINT', 1, 0, 1);
			$result['message'] .=  $result['message'] . "\n";
			$this->tasks[] = $result['status'];

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}