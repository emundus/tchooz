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
use Tchooz\Enums\Workflow\WorkflowStepDateRelativeToEnum;

class Release2_9_0Installer extends ReleaseInstaller
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
			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_campaigns_step_dates', 'relative_date', 'TINYINT(1) NOT NULL DEFAULT 0');
			$tasks[] = $result['status'];

			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_campaigns_step_dates', 'relative_to', 'VARCHAR(55) NOT NULL DEFAULT "' . WorkflowStepDateRelativeToEnum::STATUS->value . '"');
			$tasks[] = $result['status'];

			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_campaigns_step_dates', 'relative_start_date_value', 'INT(11) NOT NULL DEFAULT 0');
			$tasks[] = $result['status'];

			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_campaigns_step_dates', 'relative_start_date_unit', 'VARCHAR(55) NOT NULL DEFAULT \'day\'');
			$tasks[] = $result['status'];

			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_campaigns_step_dates', 'relative_end_date_value', 'INT(11) NOT NULL DEFAULT 0');
			$tasks[] = $result['status'];

			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_campaigns_step_dates', 'relative_end_date_unit', 'VARCHAR(55) NOT NULL DEFAULT \'day\'');

			$columns = [
				[
					'name' => 'date_time',
					'type' => 'DATETIME',
					'null' => 0,
				],
				[
					'name' => 'status',
					'type' => 'INT(11) NOT NULL',
					'null' => 0,
				],
				[
					'name' => 'fnum',
					'type' => 'VARCHAR(28) NOT NULL',
					'null' => 0,
				]
			];
			EmundusHelperUpdate::createTable('#__emundus_fnums_status_date', $columns);

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