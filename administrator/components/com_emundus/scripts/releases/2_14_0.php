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
