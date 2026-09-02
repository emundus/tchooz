<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

class Release2_24_1Installer extends ReleaseInstaller
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
			$query->select('id, execution_rules, cron_rules, next_execution')
				->from($this->db->qn('#__scheduler_tasks'))
				->where($this->db->qn('type') . ' = ' . $this->db->q('plg_task_inactiveaccounts_task_get'));
			$this->db->setQuery($query);
			$inactiveAccountsTask = $this->db->loadObject();
			if(!empty($inactiveAccountsTask) && !empty($inactiveAccountsTask->id))
			{
				$inactiveAccountsTask->execution_rules = json_encode(
					[
						'rule-type' => 'interval-days',
						'interval-days' => 1,
						'exec-day' => '22',
						'exec-time' => '10:00'
					]
				);
				$inactiveAccountsTask->cron_rules = json_encode(
					[
						'type' => 'interval',
						'exp' => 'P1D'
					]
				);
				$inactiveAccountsTask->next_execution = (new \DateTime())->add(new \DateInterval('P1D'))->setTime(8,0)->format('Y-m-d H:i:s');

				$this->tasks[] = $this->db->updateObject('#__scheduler_tasks', $inactiveAccountsTask, 'id');
			}


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