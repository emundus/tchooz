<?php

namespace scripts;

class Release2_4_2Installer extends ReleaseInstaller
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
			$event_result = \EmundusHelperUpdate::addCustomEvents([['label' => 'onAfterRender', 'category' => 'Joomla', 'published' => 1]]);
			$tasks[] = $event_result['status'];

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