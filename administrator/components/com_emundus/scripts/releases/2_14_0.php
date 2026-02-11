<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

class Release2_14_0Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query = $this->db->createQuery();

		try
		{
			$query->update($this->db->quoteName('#__emundus_plugin_events'))
				->set($this->db->quoteName('available') . ' = 1')
				->where($this->db->quoteName('label') . ' = ' . $this->db->quote('onAfterSignRequestCompleted'));

			$this->db->setQuery($query);
			$updated = $this->db->execute();

			$this->tasks[] = $updated;
			if (!$updated)
			{
				$result['message'] .= 'Failed to update "onAfterSignRequestCompleted" event availability. ';
			}

			$columnResult = \EmundusHelperUpdate::addColumn('#__emundus_task', 'priority', 'TINYINT', 1, 0, 2);
			$this->tasks[] = $columnResult['status'];

			if (!$columnResult['status'])
			{
				$result['message'] .= 'Failed to add "priority" column to "#__emundus_tasks" table. ';
			}

			// add a new widget for tasks
			$addWidget = \EmundusHelperUpdate::addWidget('COM_EMUNDUS_DASHBOARD_TASKS',  [
				'name' => 'tasks_status',
				'label' => 'COM_EMUNDUS_DASHBOARD_TASKS',
				'size' => '10',
				'size_small' => '12',
				'eval' => null,
				'published' => 1,
				'type' => 'chart',
				'chart_type' => 'column2d',
			]);

			$this->tasks[] = $addWidget['status'];
			if (!$addWidget['status'])
			{
				$result['message'] .= $addWidget['message'];
			}
			else if (!empty($addWidget['id']))
			{
				\EmundusHelperUpdate::addWidgetToProfile((int)$addWidget['id'], 1, 1, 4);

				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__emundus_setup_dashboard'))
					->where($this->db->quoteName('profile') . ' = ' . $this->db->quote(1));

				$dashboards = $this->db->setQuery($query)->loadColumn();
				foreach ($dashboards as $dashboard)
				{
					$object = (object) [
						'parent_id' => $dashboard,
						'widget' => (int)$addWidget['id'],
						'position' => 4,
					];
					$this->tasks[] = $this->db->insertObject('jos_emundus_setup_dashbord_repeat_widgets', $object);
				}
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
