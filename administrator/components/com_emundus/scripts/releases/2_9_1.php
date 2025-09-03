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
use Joomla\CMS\Component\ComponentHelper;

class Release2_9_1Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query  = $this->db->createQuery();
		$tasks = [];

		try
		{
			$fnum_column = EmundusHelperUpdate::addColumn('jos_emundus_fnums_status_date', 'fnum', 'VARCHAR', 28, 0);
			$tasks[] = $fnum_column['status'];

			$status_column = EmundusHelperUpdate::addColumn('jos_emundus_fnums_status_date', 'status', 'INT', 11, 0);
			$tasks[] = $status_column['status'];

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