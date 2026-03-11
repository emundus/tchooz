<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

class Release2_15_6Installer extends ReleaseInstaller
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
			// Update undefined override translation to empty string
			$query->clear()
				->update($this->db->quoteName('#__emundus_setup_languages'))
				->set($this->db->quoteName('override') . ' = ' . $this->db->quote(''))
				->where($this->db->quoteName('override') . ' = ' . $this->db->quote('undefined'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('override'));
			$this->db->setQuery($query);
			$this->tasks[] = $this->db->execute();

			$published_column = \EmundusHelperUpdate::addColumn('#__emundus_setup_events', 'published', 'TINYINT', 1, 0, 1);
			$this->tasks[] = $published_column['status'];

			if (!$published_column['status'])
			{
				$result['message'] .= 'Failed to add "published" column into "jos_emundus_setup_events" table.';
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
