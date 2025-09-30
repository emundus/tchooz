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

class Release2_9_6Installer extends ReleaseInstaller
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
			// Be sure that gantry mode next execution is set to the future
			$query = $this->db->getQuery(true);

			$query->select('id, next_execution')
				->from($this->db->quoteName('#__scheduler_tasks'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plg_task_checkgantrymode_task_get'));
			$this->db->setQuery($query);
			$gantry_task = $this->db->loadObject();

			if(!empty($gantry_task) && !empty($gantry_task->id) && empty($gantry_task->next_execution))
			{
				$next_execution = new \Joomla\CMS\Date\Date('now');
				// Add 2 hours to now
				$next_execution->add(new \DateInterval('PT2H'));

				$update = (object) [
					'id' => $gantry_task->id,
					'next_execution' => $next_execution->toSql()
				];
				$tasks[] = $this->db->updateObject('#__scheduler_tasks', $update, 'id');
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